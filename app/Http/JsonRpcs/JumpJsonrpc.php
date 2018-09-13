<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\HdJump;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\Func;
use App\Service\JumpService;
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
        $result = [
            'login' => false,
            'available' => 0,
            'number' => 0,
            'id' => 0,//格子数
        ];

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
        $result['start_time'] = isset($activityInfo->start_at) ? $activityInfo->start_at : '';
        $result['end_time'] = isset($activityInfo->end_at) ? $activityInfo->end_at : '';
        // 剩余抽奖次数
        if($result['available'] && $result['login']) {
            $number = $this->getUserNum($userId,$config);
            $result['number'] = $number < 0 ? 0 : $number;
            $jumpInfo = HdJump::select('number')->where(['user_id'=>$userId])->orderBy('id', 'desc')->first();
            if (isset($jumpInfo['number'])) {
                $result['id'] = $jumpInfo['number'];
            }
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
        $config = Config::get('jump');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_user_key'])->lockForUpdate()->first();
        $number = $this->getUserNum($userId,$config);
        if($number <= 0 || $num > $number) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //取出用户当前在哪一格
        $jump_model = HdJump::select('number')->where(['user_id'=>$userId])->orderBy('id', 'desc')->first();
        $number = isset($jump_model['number']) ? $jump_model['number'] : 0;
        //随机骰子值
        $dice = rand(1, 6);
        $award = $this->getAward($config, $number, $dice);
        $award['dice'] = $dice;
        //发奖
        JumpService::sendAward($userId, $award);
//        $this->dispatch(new JumpJob($userId,$award));
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
    public function jumpMyList() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = HdJump::select('award_name', 'created_at')->where('user_id',$userId)->get();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }
    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function jumpList() {
        $data = HdJump::select('user_id', 'award_name', 'created_at')->orderBy('id', 'desc')->take(20)->get();
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

    /**
     * @param $config
     * @param $number 格子id
     * @param $dice 骰子值
     * @return $data array
     */
    private function getAward($config, $number, $dice) {
        $id = intval($dice + $number);
        if ($id > 18) {
            $id = 18;
        }
        $awardList = $config['awards'];
        foreach ($awardList as $k=>$v) {
            //取出对应奖品
            if ($id == $v['id']) {
                if (!isset($v['award'])) {
                    return $v;
                } else {
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

