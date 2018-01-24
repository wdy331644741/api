<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;

use Config, Request, Cache,DB;

class RobRateCouponJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function robratecouponInfo() {
        global $userId;
        $result = ['login' => false, 'available' => 0, 'rate_coupon'=> 0, 'invite_code'=>''];
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }
        $config = Config::get('robratecoupon');
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = 1; //活动开始
        }
        if($result['available'] && $result['login']) {
            //获取用户当前加息券数值
            $rateCoupon = $this->getUserRateCoupon($userId,$config);
            $result['rate_coupon'] = $rateCoupon;
            //分享链接
            $result['invite_code'] = base64_encode($userId);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 好友助力加息
     *
     * @JsonRpcMethod
     */
    public function robratecouponFriendhelp($params) {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $invitecode = isset($params->invitecode) ? $params->invitecode : '';
        if(empty($invitecode)){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('robratecoupon');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        $amount = $this->getUserRateCoupon($userId,$config);
        if($amount > $config['max']){
            $amount = $config['max'];
        }

        $award = $this->getAward($config);
        //放入队列
        $this->dispatch(new DazhuanpanBatch($userId,$config,$award));
        //格式化后返回
        unset($award['num']);
        unset($award['weight']);
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
    public function dazhuanpanMyList($params) {
        global $userId;

        $num = isset($params->num) ? $params->num : 10;
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
        $data = DaZhuanPan::select('user_id', 'type', 'award_name', 'alias_name', 'created_at')
            ->where('type', '!=', 'empty')
            ->where('user_id',$userId)
            ->orderBy('id', 'desc')->paginate($num)->toArray();
        foreach ($data['data'] as &$item){
//            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
            if(!empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
            $item['effective_time_day'] = '';
            if($item['type'] == 'activity'){
                $item['effective_time_day'] = date('Y-m-d H:i:s', strtotime($item['created_at']) + 7 * 24 * 3600);
            }
        }
        
        $rData['total'] = $data['total'];
        $rData['per_page'] = $data['per_page'];
        $rData['current_page'] = $data['current_page'];
        $rData['last_page'] = $data['last_page'];
        $rData['from'] = $data['from'];
        $rData['to'] = $data['to'];
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
    public function dazhuanpanList() {
        $list = Cache::remember('longyinhuxiao_list', 2, function() {
            $data = DaZhuanPan::select('user_id', 'award_name')->where('type', '!=', 'empty')->orderBy('id', 'desc')->take(20)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                }
            }
            //获取随机加入的奖品
//            $joinData = $this->joinData();
//            if(!empty($joinData)){
//                $newData[0] = $joinData;
//                for($i=1;$i<=count($data);$i++){
//                    $newData[$i] = $data[$i-1];
//                }
//                $data = $newData;
//            }
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
        $rateList = $config['rate'];
        // 获取权重总值
        $totalWeight = $config['weight'];
        $target = rand(1, $weight);
        //todo
        //明天继续
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

    //获取用户的加息券数值
    private function getUserRateCoupon($userId,$config, $default=0){
        $item = Attributes::getItem($userId, $config['drew_user_key']);
        if($item && $item->string) {
            $default = floor($rateCoupon * 10) / 10;
        }
        return $default;
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

