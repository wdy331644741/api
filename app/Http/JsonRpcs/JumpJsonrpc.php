<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\Func;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;

use Config, Request, Cache,DB;

class JumpJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function jumpInfo() {
        global $userId;

        $config = Config::get('jump');
        $result = ['login' => false, 'available' => 0, 'number' => 0];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        $activityInfo = ActivityService::GetActivityInfoByAlias('jump');
        if( $activityInfo ) {
            //活动正在进行
            $result['available'] = 1;
        }
        $result['start_time'] = $activityInfo->start_at;
        $result['end_time'] = $activityInfo->end_at;
        // 剩余抽奖次数
        if($result['available'] && $result['login']) {
            $number = $this->getUserNum($userId,$config);
            $result['number'] = $number < 0 ? 0 : $number;
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function jumpDraw($params) {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取抽奖次数
        $num = isset($params->num) ? $params->num : 0;
        if($num != 1){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //获取抽奖次数
        $id = isset($params->id) ? $params->id : 0;
        if($id < 1 || $id>18){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('jump');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_user_key'])->lockForUpdate()->get();
        $number = $this->getUserNum($userId,$config);
        if($number <= 0) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $award = $this->getAward($id, $config);
        //保存数据库 todo
        //跳到哪一块和对应奖品
        //减少用户抽奖次数
        $this->reduceUserNum($userId,$config,1);
        //事务提交结束
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $award,
        ];
    }

    /**
     * 获取我的奖品列表
     *
     * @JsonRpcMethod
     */
    public function jumpMyList($params) {
        global $userId;

        $num = isset($params->num) ? $params->num : 5;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = KbDaZhuanPan::select('award_name', 'created_at')->where('user_id',$userId)->paginate($num)->toArray();
        
//        $rData['total'] = $data['total'];
//        $rData['per_page'] = $data['per_page'];
//        $rData['current_page'] = $data['current_page'];
        $rData['last_page'] = $data['last_page'];
//        $rData['from'] = $data['from'];
//        $rData['to'] = $data['to'];
        $rData['list'] = $data['data'];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $rData,
        ];
    }
    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function jumpList() {
        $data = KbDaZhuanPan::select('user_id', 'award_name', 'created_at')->orderBy('id', 'desc')->take(20)->get();
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    //获取奖品
    private function getAward($id, $config) {
        $awardList = $config['awards'];
        foreach ($awardList as $k=>$v) {
            if ($id == $v['id']) {
                if (!isset($v['award'])) {
                    return $v;
                } {
                    $awardList = $v['award'];
                    break;
                }
            }
        }
        // 获取权重总值
        $weight = 0;
        foreach($awardList as $award) {
            $weight += $award['weight'];
        }

        $target = rand(1, $weight);
        foreach($awardList as $award) {
            $target = $target - $award['weight'];
            if($target <= 0) {
                return $award;
            }
        }
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config){
        $userNum = Attributes::getNumber($userId, $config['drew_user_key']);
        if($userNum > 0){
            return $userNum;
        }
        return 0;
    }

    //减少用户次数
    private function reduceUserNum($userId,$config,$num){
        if($num <= 0){
            return false;
        }
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config['drew_user_key'],$num);
        return true;
    }
}

