<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\DaZhuanPan;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\ActivityService;
use App\Service\Func;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\DazhuanpanBatch;

use Config, Request, Cache;

class DaZhuanPanJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanInfo() {
        global $userId;

        $config = Config::get('dazhuanpan');
        $result = ['login' => false, 'available' => false, 'number' => 0];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = true;
        }

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
    public function dazhuanpanDraw($params) {
        global $userId;

        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取抽奖次数
        $num = isset($params->num) ? $params->num : 0;
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $config = Config::get('dazhuanpan');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $number = $this->getUserNum($userId,$config);
        if($number <= 0) {
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }
        if($num > $number){
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

        // 循环获取奖品
        $awardArr = [];
        for($i = 1;$i <= $num; $i++){
            $awardArr[] = $this->getAward( $config);
        }
        //放入队列
        $this->dispatch(new DazhuanpanBatch($userId,$config,$awardArr));
        //格式化后返回
        foreach($awardArr  as &$item){
            unset($item['num']);
            unset($item['weight']);
            $item['created_at'] = date("Y-m-d H:i:s");
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $awardArr,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanMyList($params) {
        global $userId;

        $num = isset($params->num) ? $params->num : 0;
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = DaZhuanPan::select('user_id', 'award_name')->where('type', '!=', 'empty')->where('user_id',$userId)->orderBy('id', 'desc')->take($num)->get();
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
    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanList() {
        $list = Cache::remember('dazhuanpan_list', 2, function() {
            $data = DaZhuanPan::select('user_id', 'award_name')->where('type', '!=', 'empty')->orderBy('id', 'desc')->take(20)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                }
            }
            return $data;
        });

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config){
        $loginNum = Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        if($loginNum <= 0){
            $loginNum = $config['draw_number'];
        }
        $userNum = Attributes::getNumber($userId, $config['drew_user_key']);
        return $loginNum + $userNum;
    }
}

