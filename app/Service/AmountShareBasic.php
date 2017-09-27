<?php
namespace App\Service;
use App\Models\HdAmountShareEleven;
use App\Models\Activity;
use App\Models\HdAmountShareElevenInfo;
use App\Models\UserAttribute;
use App\Service\Func;
use Lib\JsonRpcClient;
use Config;
use Validator;
use DB;

class AmountShareBasic
{
    /**
     *  生成现金分享数据
     *
     */
    static function amountShareCreate($triggerData) {
        $level = $triggerData['level'] <= 1 ? 1 : $triggerData['level'];
        $multiple = 0;
        if(isset($triggerData['user_id']) && isset($triggerData['Investment_amount']) && isset($triggerData['scatter_type']) && isset($triggerData['period'])){
            //新手标不生成红包
            if(isset($triggerData['novice_exclusive']) && $triggerData['novice_exclusive'] == 1){
                return 'not create';
            }
            if(($triggerData['scatter_type'] == 1 && $triggerData['period'] == 30) || ($triggerData['scatter_type'] == 2 && $triggerData['period'] == 1)){
                $multiple = 0.0005;
                $triggerData['period'] = 1;
            }
            if($triggerData['scatter_type'] == 2 && $triggerData['period'] == 3){
                $multiple = 0.001;
            }
            if($triggerData['scatter_type'] == 2 && $triggerData['period'] >= 6){
                $multiple = 0.0015;
            }
            if($triggerData['user_id'] <= 0 || $triggerData['Investment_amount'] < 100 || $multiple == 0){
                return 'params error';
            }
        }else{
            return 'params error';
        }

        //生成的现金红包金额
        $amountShare = $triggerData['Investment_amount']*$multiple*$level;
        if($amountShare < 0.1){
           return 'amount error';
        }

        //根据别名查询该活动是否开启
        $where['alias_name'] = "amount_share";
        $where['enable'] = 1;
        $isExist = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where($where)->count();

        if(!$isExist){
            return 'activity is end';
        }

        //添加到红包分享表
        $param['user_id'] = $triggerData['user_id'];
        $param['money'] = $amountShare;
        $param['total_num'] = 10;
        $param['min'] = 0.01;
        $param['investment_amount'] = $triggerData['Investment_amount'];
        $param['period'] = $triggerData['period'];
        $param['multiple'] = $multiple;
        $param['level'] = $level;
        return self::addAmountShare($param);
    }
    /**
     * 添加到现金分享表中
     * @param $param
     * @return bool
     */
    static function addAmountShare($param){
        if($param['user_id'] <= 0 || $param['money'] <= 0){
            return false;
        }
        $endTime = ActivityService::GetActivityInfoByAlias('amount_share');
        $endTime = isset($endTime['end_at']) && !empty($endTime['end_at']) ? $endTime['end_at'] : "";
        //用户ID
        $data['user_id'] = $param['user_id'];
        //总金额
        $data['total_money'] = $param['money'];
        //总数量
        $data['total_num'] = $param['total_num'];
        //最小值
        $data['min'] = $param['min'];
        //最大值
        $data['max'] = 0;
        //红包标示
        $data['identify'] = "amount_share_".Func::randomStr(15);
        //投资金额
        $data['investment_amount'] = $param['investment_amount'];
        //标期
        $data['period'] = $param['period'];
        //红包倍数
        $data['multiple'] = $param['multiple'];
        //用户vip等级
        $data['level'] = $param['level'];
        //开始时间
        $data['start_time'] = date("Y-m-d H:i:s");
        //结束时间
        $data['end_time'] = $endTime;
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        //生成的uri
        $inviteCode = Func::getUserBasicInfo($param['user_id'],true);
        $inviteCode = !empty($inviteCode) && isset($inviteCode['invite_code']) ? $inviteCode['invite_code'] : "";
        $data['uri'] = self::getAmountShareURI($data['identify'],$inviteCode);
        $id = HdAmountShareEleven::insertGetId($data);
        return array('id'=>$id,'result'=>$data);
    }
    static function getAmountShareURI($identify,$inviteCode){
        $callbackURI = urlencode(env("APP_URL")."/active/share_red/receive.html?k=".$identify."&invite_code=".$inviteCode);
        return env("MONEY_SHARE_WECHAT_URL").$callbackURI;
    }


    /**
     * 红包算法
     * 
     * @param $remain int 剩余金额
     * @param $remainNumber int 剩余数量
     * @param $min int 最小值
     * @param $max int 最大值
     * @return int
     */
    static function getRandomMoney($remain, $remainNumber, $min, $max) {
        if($remainNumber == 1) {
            return $remain;
        }
        $randomRemain = $remain - $remainNumber*$min;
        if($randomRemain <= 0 ) {
            return $min;
        }

        $average =  floor($randomRemain/$remainNumber);
        $randomMax = $max-$min;
        
        // 最大值最低为平均值2倍
        $randomMax = $average*2 > $randomMax ? $average*2 : $randomMax;
        // 最大值不能大于剩余
        $randomMax = $randomMax > $randomRemain ? $randomRemain : $randomMax;
        
        $goodLuck =  rand(0, $randomMax);
        if($goodLuck < $average) {
            return rand($average, $randomMax)+$min;   
        }else{
            return rand(0, $average)+$min; 
        }
    }

    /**
     * 是否是活动期间的新用户
     * @param $userId
     */
    static function isActivityNewUser($userId,$fromUserId,$activityInfo){
        //获取活动时间
        $startTime = 0;
        $endTime = 0;
        if(!empty($activityInfo->start_at)){
            $startTime = strtotime($activityInfo->start_at);
        }
        if(!empty($activityInfo->end_at)){
            $endTime = strtotime($activityInfo->end_at);
        }
        if($startTime <= 0 || $endTime <= 0){
            return 0;
        }
        //获取用户注册时间
        $userInfo = Func::getUserBasicInfo($userId,true);
        $registerTime = isset($userInfo['create_time']) && !empty($userInfo['create_time']) ? strtotime($userInfo['create_time']) : 0;
        $thisFromUserId = isset($userInfo['from_user_id']) && !empty($userInfo['from_user_id']) ? intval($userInfo['from_user_id']) : 0;
        if($thisFromUserId == $fromUserId){
            $count = HdAmountShareElevenInfo::where('user_id',$userId)->where('is_new',1)->count();
            if($count >= 1){
                return 0;
            }
            if($registerTime <= 0){
                return 0;
            }
            if($registerTime >= $startTime && $registerTime <= $endTime){
                return 1;
            }
        }
        return 0;
    }
    //获取新用户应该注册应该获取的金额
    static function getNewUserMoney($mallInfo){
        $myNewUserCount = HdAmountShareElevenInfo::where('main_id',$mallInfo['id'])->where('is_new',1)->count();
        if($mallInfo['period'] == 1){
            $multiple = 0.0003;
        }elseif($mallInfo['period'] == 3){
            $multiple = 0.0006;
        }elseif($mallInfo['period'] >= 6){
            $multiple = 0.0009;
        }else{
            $multiple = 0;
        }
        $money = 0;
        if($mallInfo['status'] == 1){
            $money = $mallInfo['investment_amount'] * $multiple * $myNewUserCount;
        }
        return $money;
    }
}