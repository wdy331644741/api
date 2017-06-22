<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\DaZhuanPan;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\DazhuanpanBatch;

use Config, Request, Cache,DB;

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
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        if($num > $number){
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //事务开始
        DB::beginTransaction();

        // 循环获取奖品
        $awardArr = [];
        for($i = 1;$i <= $num; $i++){
            $award = $this->getAward($config);
            if($award == false){
                throw new OmgException(OmgException::NUMBER_IS_NULL);
            }
            $awardArr[] = $award;
        }
        //放入队列
        $this->dispatch(new DazhuanpanBatch($userId,$config,$awardArr));
        //格式化后返回
        foreach($awardArr  as &$item){
            unset($item['num']);
            unset($item['weight']);
        }
        //减少用户抽奖次数
        $this->reduceUserNum($userId,$config,count($awardArr));

        //事务提交结束
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $awardArr,
        ];
    }

    /**
     * 获取我的奖品列表
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
        $data = DaZhuanPan::select('user_id', 'award_name', 'created_at')->where('type', '!=', 'empty')->where('user_id',$userId)->orderBy('id', 'desc')->take($num)->get();
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
            //获取随机加入的奖品
            $joinData = $this->joinData();
            if(!empty($joinData)){
                $data[] = $joinData;
            }
            return $data;
        });

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    //获取奖品
    private function getAward($config) {
        $awardList = $config['awards'];
        // 获取权重总值
        $weight = 0;
        foreach($awardList as $award) {
            $weight += $award['weight'];
        }

        $target = rand(1, $weight);
        foreach($awardList as $award) {
            $target = $target - $award['weight'];
            if($target <= 0) {
                $globalKey = $config['alias_name'] . '_' . $award['alias_name'] . '_' . date('Ymd');
                $usedNumber = GlobalAttributes::incrementByDay($globalKey);
                // 奖品送完
                if($usedNumber > $award['num']) {
                    return false;
                }
                return $award;
            }
        }
        return false;
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config){
        $loginNum = Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        if($loginNum <= 0){
            $loginNum = $config['draw_number'];
        }else{
            $loginNum = 0;
        }
        $userNum = Attributes::getNumber($userId, $config['drew_user_key']);
        return $loginNum + $userNum;
    }

    //减少用户次数
    private function reduceUserNum($userId,$config,$num){
        if($num <= 0){
            return false;
        }
        $loginNum = Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        if($loginNum <= 0){
            //将每日的免费次数改成已使用
            Attributes::incrementByDay($userId, $config['drew_daily_key']);
            $num -= $num;
        }
        if($num <= 0){
            return false;
        }
        //奖总共的抽奖次数累加
        Attributes::increment($userId, $config['drew_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config['drew_user_key'],$num);
        return true;
    }

    //获取每5天加入的奖品和每2天加入的奖品
    private function joinData(){
        $config = Config::get('dazhuanpan');
        //获取活动开始时间&计算活动开始的天数
        $activityInfo = ActivityService::GetActivityInfoByAlias($config['alias_name']);
        $activityDay = isset($activityInfo->start_at) ? strtotime($activityInfo->start_at) : strtotime('2017-07-03 00:00:00');
        $nowDay = strtotime(date("Y-m-d"));
        $diffDay = ($nowDay - $activityDay) / (60 * 60 * 24);

        if($diffDay > 0){
            $twoDay = $diffDay%2;
            $fiveDay = $diffDay%5;
            if($twoDay == 0){
                return [
                    'user_id' => 13888888888,
                    'award_name' => 'Apple MacBookPro 13英寸（i5/8G/256G）',
                    'phone' => '138******88'
                ];
            }
            if($fiveDay == 0){
                return [
                    'user_id' => 18888888888,
                    'award_name' => '普吉岛10日游',
                    'phone' => '188******88'
                ];
            }
        }
        return [];
    }

}

