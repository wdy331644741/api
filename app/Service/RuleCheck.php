<?php
namespace App\Service;

use App\Models\Activity;
use App\Models\Rule;
use Lib\JsonRpcClient;
use Config;
use Predis\Client;
use App\Service\Func;

class RuleCheck
{
    private static $inside_api_url;

    private static $account_reward_url;
    
    private static $trade_api_url;

    private function __construct(){
        self::$inside_api_url = env('INSIDE_HTTP_URL');
        self::$account_reward_url = env('REWARD_HTTP_URL');
        self::$trade_api_url = env('TRADE_HTTP_URL');
    }

    //规则验证
    public static function check($activity_id,$userId,$sqsmsg){
        new self();
        $activity = Rule::where('activity_id',$activity_id)->get();
        if(count($activity) < 1){
            return array('send'=>true);
        }
        $userBase = Func::globalUserBasicInfo($userId);
        $res = array('send'=>true);
        foreach ($activity as $value){
            switch (true){
                case $value->rule_type === 0:
                    $res = self::_register($userBase,$value);
                    break;
                case $value->rule_type === 1:
                    $res = self::_channel($userBase,$value);
                    break;
                case $value->rule_type === 2:
                    $res = self::_invite($userBase,$value);
                    break;
                case $value->rule_type === 3:
                    $res = self::_inviteNum($userId,$value);
                    break;
                case $value->rule_type === 4:
                    $res = self::_userLevel($userBase,$value);
                    break;
                case $value->rule_type === 5:
                    $res = self::_userCredit($userBase,$value);
                    break;
                case $value->rule_type === 6:
                    $res = self::_balance($userBase,$value);
                    break;
                case $value->rule_type === 7:
                    $res = self::_cast($value,$sqsmsg);
                    break;
                case $value->rule_type === 8:
                    $res = self::_recharge($value,$sqsmsg);
                    break;
                case $value->rule_type === 9:
                    $res = self::_payment($value,$sqsmsg);
                    break;
                case $value->rule_type === 10:
                    $res = self::_castAll($userId,$value);
                    break;
                case $value->rule_type === 11:
                    $res = self::_rechargeAll($userId,$value);
                    break;
                case $value->rule_type === 12:
                    $res = self::_castName($value,$sqsmsg);
                    break;
                case $value->rule_type === 13:
                    $res = self::_ChannelBlist($userBase,$value);
                    break;
                case $value->rule_type === 14:
                    $res = self::_castType($value,$sqsmsg);
                    break;
                case $value->rule_type === 15:
                    $res = self::_joinNum($activity_id,$value);
                    break;
                case $value->rule_type === 16:
                    $res = self::_paymentDate($value,$sqsmsg);
                    break;
                case $value->rule_type === 17:
                    $res = self::_signDay($value,$sqsmsg);
                    break;
                case $value->rule_type === 18:
                    $res = self::_castNum($value,$userId);
                    break;
                case $value->rule_type === 19:
                    $res = self::_paymentNum($value,$userId);
                    break;
                default :
                    $res = array('send'=>false,'errmsg'=>'未知规则');
                    break;
            }
            if(!$res['send']){
                return array('send'=>false,'errmsg'=>$res['errmsg']);
            }
        }
        return $res;
    }

    //注册时间
    private static function _register($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $register_time = $userBase['result']['data']['create_time'];
        if($register_time >= $rules['min_time'] && $register_time <= $rules['max_time']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'注册规则验证不通过');
    }

    //用户渠道
    private static function  _channel($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $user_channel = $userBase['result']['data']['from_channel'];
        $channel_arr = explode(';',strtolower($rules['channels']));
        if(in_array(strtolower($user_channel),$channel_arr)){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'渠道白名单规则验证不通过');
    }

    //是否邀请用户
    private static function _invite($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $from_user_id = $userBase['result']['data']['from_user_id'];
        if(boolval($from_user_id) === boolval($rules['is_invite'])){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'邀请规则验证不通过');
    }

    //邀请人数
    private static function _inviteNum($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$account_reward_url);
        $res = $client->getInviteList(array('uid'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $inviteNum = count($res['result']['data']);
        if($inviteNum >= $rules['min_invitenum'] && $inviteNum <= $rules['max_invitenum']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'邀请规则验证不通过');
    }

    //用户等级
    private static function _userLevel($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $user_level = $userBase['result']['data']['level'];
        if($user_level >= $rules['min_level'] && $user_level <= $rules['max_level'] ){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'等级规则验证不通过');
    }


    #TODO  //用户积分(用户表没有积分字段)
    private static function _userCredit($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $user_credit = @$userBase['result']['data']['积分'];
        if($user_credit >= $rules['min_credit'] && $user_credit <= $rules['max_credit']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'等级规则验证不通过');
    }

    //用户余额
    private static function _balance($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $user_balance = $userBase['result']['data']['avaliable'];
        if($user_balance >= $rules['min_balance'] && $user_balance <= $rules['max_balance']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'用户余额规则验证不通过');
    }

    //用户投资
    private static function _cast($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $isfirst = $sqsmsg['is_first'];
        $cast_meony = ceil(floatval($sqsmsg['Investment_amount']));
        if ($rules['isfirst'] == 1){
            if($isfirst && $cast_meony >= $rules['min_cast'] && $cast_meony <= $rules['max_cast']){
                return array('send'=>true);
            }
        }elseif ($rules['isfirst'] == 2){
            if(!$isfirst && $cast_meony >= $rules['min_cast'] && $cast_meony <= $rules['max_cast']){
                return array('send'=>true);
            }
        }else{
            if($cast_meony >= $rules['min_cast'] && $cast_meony <= $rules['max_cast']){
                return array('send'=>true);
            }
        }
        return array('send'=>false,'errmsg'=>'单笔投资规则验证不通过');
    }

    //用户单笔充值
    private static function _recharge($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $isfirst = $sqsmsg['is_first'];
        $recharge_meony = ceil(floatval($sqsmsg['money']));
        if ($rules['isfirst'] == 1){
            if($isfirst && $recharge_meony >= $rules['min_recharge'] && $recharge_meony <= $rules['max_recharge']){
                return array('send'=>true);
            }
        }elseif ($rules['isfirst'] == 2){
            if(!$isfirst && $recharge_meony >= $rules['min_recharge'] && $recharge_meony <= $rules['max_recharge']){
                return array('send'=>true);
            }
        }else{
            if($recharge_meony >= $rules['min_recharge'] && $recharge_meony <= $rules['max_recharge']){
                return array('send'=>true);
            }
        }

        return array('send'=>false,'errmsg'=>'单笔充值规则验证不通过');
    }

    //用户回款(本金)
    private static function _payment($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);

        $payment_meony = ceil(floatval($sqsmsg['principal']));
        if($payment_meony >= $rules['min_payment'] && $payment_meony <= $rules['max_payment']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'回款规则验证不通过');
    }

    //回款期限
    private static function _paymentDate($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $payment_date = 0;
        if($sqsmsg['scatter_type'] == 1){
            $payment_date = intval($sqsmsg['period_day']);
        }elseif ($sqsmsg['scatter_type'] == 2){
            $payment_date = intval($sqsmsg['period'] * 30);
        }else{
            return array('send'=>false,'errmsg'=>'回款期限不正确');
        }
        if($payment_date >= $rules['min_paymentdate'] && $payment_date < $rules['max_paymentdate']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'回款期限规则验证不通过');
    }

    //连续签到天数
    private static function _signDay($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $signDay = $sqsmsg['days'];
        if(isset($signDay)){
            return array('send'=>false,'errmsg'=>'触发参数不正确');
        }
        if($signDay >= $rules['day_min'] && $signDay < $rules['day_max']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'签到天数规则验证不通过');
    }

    //用户投资总金额
    private static function _castAll($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$inside_api_url);
        $res = $client->userTradeCount(array('userId'=>$userId,'startTime'=>$rules['start_time'],'endTime'=>$rules['end_time']));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $cast_all_money = ceil(floatval($res['result']['data']));
        if($cast_all_money >= $rules['min_cast_all'] && $cast_all_money <= $rules['max_cast_all']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'投资总金额规则验证不通过');
    }

    //用户充值总金额
    private static function _rechargeAll($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$inside_api_url);
        $res = $client->userRechargeCount(array('userId'=>$userId,'startTime'=>$rules['start_time'],'endTime'=>$rules['end_time']));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $cast_all_money = ceil(floatval($res['result']['data']));
        if($cast_all_money >= $rules['min_recharge_all'] && $cast_all_money <= $rules['max_recharge_all']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'充值总金额规则验证不通过');
    }

    //投资 标名称+期名
    private static function _castName($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $name = trim($sqsmsg['name']);
        $short_name = trim($sqsmsg['short_name']);
        if($rules['name']){
            if(substr_count($name.$short_name,$rules['name'])){
                return array('send'=>true);
            }
        }
        return array('send'=>false,'errmsg'=>'投资标名称规则验证不通过');
    }


    //投资次数
    private static function _castNum($rule,$userId){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $secret = hash('sha256',$userId.'3d07dd21b5712a1c221207bf2f46e4ft');
        $res =  $client->getTradeAndRepamentTimes(array('user_id'=>$userId,'secret'=>$secret));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>'获取累计投资次数失败');
        }
        $castNum = $res['result']['tradeTimes'];
        if($castNum > $rules['min_num'] && $castNum <= $rules['max_num']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'投资次数规则验证不通过');
    }

    //回款次数
    private static function _paymentNum($rule,$userId){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $secret = hash('sha256',$userId.'3d07dd21b5712a1c221207bf2f46e4ft');
        $res =  $client->getTradeAndRepamentTimes(array('user_id'=>$userId,'secret'=>$secret));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>'获取累计回款次数失败');
        }
        $paymentNum = $res['result']['repaymentTimes'];
        if($paymentNum > $rules['min_num'] && $paymentNum <= $rules['max_num']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'回款次数规则验证不通过');
    }

    //用户渠道黑名单
    private static function _ChannelBlist($userBase,$rule){
        $rules = (array)json_decode($rule->rule_info);
        if(isset($userBase['error'])){
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $user_channel = $userBase['result']['data']['from_channel'];
        $channel_arr = explode(';',strtolower($rules['channels']));
        if(!in_array(strtolower($user_channel),$channel_arr)){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'渠道黑名单规则验证不通过');
    }

    //投资标期类型规则验证（验证参数）
    private static function _castType($rule,$sqsmsg) {
        $rules = (array)json_decode($rule->rule_info);
        $scatter_type = $sqsmsg['scatter_type'];
        if(!in_array(strval($scatter_type),array('0','1','2'))){
            return array('send'=>false,'errmsg'=>'非法标期类型');
        }
        if($scatter_type){
            if($scatter_type == $rules['type']){
                return array('send'=>true);
            }
        }else{
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'投资标期类型规则验证不通过');
    }

    //参与人数规则验证(验证参数)
    private static function _joinNum($activity_id,$rule) {
        $rules = (array)json_decode($rule->rule_info);
        $join_num = Activity::find($activity_id)->value('join_num');
        $join_max = $rules['join_max'];
        if($join_num < $join_max){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'活动参与人数超过限制');
    }
}