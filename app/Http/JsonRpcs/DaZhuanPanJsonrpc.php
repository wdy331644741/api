<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\DaZhuanPan;
use App\Models\UserAttribute;
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
        $result = ['login' => false, 'available' => 0, 'number' => 0];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        $activityInfo = Activity::where(['enable' => 1, 'alias_name' => $config['alias_name']])->first();;
        if(isset($activityInfo->id) && $activityInfo->id > 0) {
            $startTime = isset($activityInfo->start_at) && !empty($activityInfo->start_at) ? strtotime($activityInfo->start_at) : 0;
            $endTime = isset($activityInfo->end_at) && !empty($activityInfo->end_at) ? strtotime($activityInfo->end_at) : 0;
            //活动正在进行
            if(empty($startTime) && empty($endTime)){
                $result['available'] = 1;
            }
            if(empty($startTime) && !empty($endTime)){
                if(time() > $endTime){
                    //活动结束
                    $result['available'] = 2;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            }
            if(!empty($startTime) && empty($endTime)){
                //活动未开始
                if(time() < $startTime){
                    $result['available'] = 0;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            }
            if(!empty($startTime) && !empty($endTime)){
                if(time() > $startTime){
                    //活动正在进行
                    $result['available'] = 1;
                }
                if(time() > $endTime){
                    $result['available'] = 2;
                }
            }
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
        if($num != 1 && $num != 10){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('dazhuanpan');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_total_key'])->lockForUpdate()->get();

        $number = $this->getUserNum($userId,$config);
        if($number <= 0) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        if($num > $number){
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }

        // 循环获取奖品
        $awardArr = [];
        for($i = 1;$i <= $num; $i++){
            $award = $this->getAward($config);
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
        $data = DaZhuanPan::select('user_id', 'type', 'award_name', 'alias_name', 'created_at')->where('type', '!=', 'empty')->where('user_id',$userId)->orderBy('id', 'desc')->take($num)->get();
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
                $newData[0] = $joinData;
                for($i=1;$i<=count($data);$i++){
                    $newData[$i] = $data[$i-1];
                }
                $data = $newData;
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
                $usedNumber = GlobalAttributes::getNumberByDay($globalKey);
                // 单个奖品送完
                if($usedNumber >= $award['num']) {
                    //谢谢参与&100元红包
                    $round = mt_rand(0,1);
                    return $awardList[$round];
                }
                GlobalAttributes::incrementByDay($globalKey);
                return $award;
            }
        }
        //谢谢参与&100元红包
        $round = mt_rand(0,1);
        return $awardList[$round];
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
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);

        //获取每次免费次数是否有
        $loginNum = Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        if($loginNum <= 0){
            //将每日的免费次数改成已使用
            Attributes::incrementByDay($userId, $config['drew_daily_key']);
            $num -= $num;
        }
        if($num <= 0){
            return false;
        }
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

