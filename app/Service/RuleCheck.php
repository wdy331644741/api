<?php
namespace App\Service;

use App\Models\Activity;
use App\Models\Rule;
use Lib\JsonRpcClient;
use Config;
use Predis\Client;

class RuleCheck
{
    private static $user_api_url = 'http://sunfeng.wlpassport.dev.wanglibao.com/service.php?c=account';

    private static $trade_api_url = 'http://sunfeng.wlpassport.dev.wanglibao.com/service.php?c=trade';
    //规则验证
    public static function check($activity_id,$userId,$sqsmsg){
        $url = Config::get('award.rulecheck_user_http_url');
        $activity = Rule::where('activity_id',$activity_id)->get();
        $res = array('send'=>true);
        foreach ($activity as $value){
            switch ($value->rule_tupe){
                case 0:
                    $res = self::_register($userId,$value);
                    break;
                case 1:
                    $res = self::_channel($userId,$value);
                    break;
                case 2:
                    $res = self::_invite($userId,$value);
                    break;
                case 3:
                    $res = self::_inviteNum($userId,$value);
                    break;
                case 4:
                    $res = self::_userLevel($userId,$value);
                    break;
                case 5:
                    $res = self::_userCredit($userId,$value);
                    break;
                case 6:
                    $res = self::_balance($userId,$value);
                    break;
                case 7:
                    $res = self::_cast($userId,$value,$activity_id,$sqsmsg);
                    break;
                case 8:
                    $res = self::_recharge($userId,$value,$activity_id,$sqsmsg);
                    break;
                case 9:
                    $res = self::_payment($userId,$value);
                    break;
                case 10:
                    $res = self::_castAll($userId,$value,$activity_id);
                    break;
                case 11:
                    $res = self::_rechargeAll($userId,$value,$activity_id);
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
    private static function _register($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $register_time = $res['result']['data']['create_time'];
        if($register_time >= $rules['min_time'] && $register_time <= $rules['max_time']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'注册规则验证不通过');
    }

    //用户渠道
    private static function  _channel($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $user_channel = $res['result']['data']['from_channel'];
        if($user_channel == $rules['channels']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'渠道规则验证不通过');
    }

    //是否邀请用户
    private static function _invite($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $from_user_id = $res['result']['data']['from_user_id'];
        if(boolval($from_user_id) === boolval($rules['is_invite'])){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'邀请规则验证不通过');
    }

    //邀请人数
    private static function _inviteNum($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $from_user_id = $res['result']['data']['from_user_id'];
        if(boolval($from_user_id) === boolval($rules['invite_num'])){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'邀请规则验证不通过');
    }

    //用户等级
    private static function _userLevel($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $user_level = $res['result']['data']['level'];
        if($rules['user_level'] >= $user_level){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'等级规则验证不通过');
    }


    #TODO  //用户积分(用户表没有积分字段)
    private static function _userCredit($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $user_credit = @$res['result']['data']['积分'];
        if($user_credit >= $rules['min_credit'] && $user_credit <= $rules['max_credit']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'等级规则验证不通过');
    }

    //用户余额
    private static function _balance($userId,$rule){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$user_api_url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $user_balance = $res['result']['data']['avaliable'];
        if($user_balance >= $rules['min_balance'] && $user_balance <= $rules['max_balance']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'用户余额规则验证不通过');
    }

    #TODO //用户投资（cast消息通知未添加）
    private static function _cast($userId,$rule,$activity_id,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $activity = Activity::find($activity_id);
        $res = null;
        if(empty($activity->id)){
            return array('send'=>false,'errmsg'=>'活动不存在');
        }
        $res = null;
        if(empty($activity->start_at) && empty($activity->ent_at)){
            $res = $client->userTradeInfo(array('userId'=>$userId));
        }else{
            $res = $client->userTradeInfo(array('userId'=>$userId,'startTime'=>$activity->start_at,'endTime'=>$activity->end_at));
        }
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        if(count($res['result']['data']) == 1){
            $isfirst = true;
        }
        $cast_meony = $sqsmsg['单笔投资金额'];
        if($rules['isfirst']){
            if($isfirst && $cast_meony >= $rules['min_cast'] && $cast_meony <= $rules['max_cast']){
                return array('send'=>true);
            }
        }else{
            if($cast_meony >= $rules['min_cast'] && $cast_meony <= $rules['max_cast']){
                return array('send'=>true);
            }
        }

        return array('send'=>false,'errmsg'=>'单笔投资规则验证不通过');
    }

    #TODO //用户单笔充值（recharge消息通知未添加）
    private static function _recharge($userId,$rule,$activity_id,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $activity = Activity::find($activity_id);
        $res = null;
        if(empty($activity->id)){
            return array('send'=>false,'errmsg'=>'活动不存在');
        }
        $res = null;
        if(empty($activity->start_at) && empty($activity->ent_at)){
            $res = $client->userRechargeInfo(array('userId'=>$userId));
        }else{
            $res = $client->userRechargeInfo(array('userId'=>$userId,'startTime'=>$activity->start_at,'endTime'=>$activity->end_at));
        }
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        if(count($res['result']['data']) == 1){
            $isfirst = true;
        }
        $recharge_meony = $sqsmsg['单笔充值金额'];
        if ($rules['isfirst']){
            if($isfirst && $recharge_meony >= $rules['min_recharge'] && $recharge_meony <= $rules['min_recharge']){
                return array('send'=>true);
            }
        }else{
            if($recharge_meony >= $rules['min_recharge'] && $recharge_meony <= $rules['max_recharge']){
                return array('send'=>true);
            }
        }

        return array('send'=>false,'errmsg'=>'单笔充值规则验证不通过');
    }

    #TODO //用户回款（payment消息通知未添加）
    private static function _payment($rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);

        $payment_meony = $sqsmsg['回款金额'];
        if($payment_meony >= $rules['min_payment'] && $payment_meony <= $rules['max_payment']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'回款规则验证不通过');
    }

    //用户投资总金额
    private static function _castAll($rule,$userId,$activity_id){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $activity = Activity::find($activity_id);
        if(empty($activity->id)){
            return array('send'=>false,'errmsg'=>'活动不存在');
        }
        $res = null;
        if(empty($activity->start_at) && empty($activity->ent_at)){
            $res = $client->userRechargeCount(array('userId'=>$userId));
        }else{
            $res = $client->userRechargeCount(array('userId'=>$userId,'startTime'=>$activity->start_at,'endTime'=>$activity->end_at));
        }
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $cast_all_money = intval($res['result']['data']);
        if($cast_all_money >= $rules['min_cast_all'] && $cast_all_money <= $rules['max_cast_all']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'投资总金额规则验证不通过');
    }

    //用户充值总金额
    private static function _rechargeAll($rule,$userId,$activity_id){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $activity = Activity::find($activity_id);
        $res = null;
        if(empty($activity->start_at) && empty($activity->ent_at)){
            $res = $client->userTradeCount(array('userId'=>$userId));
        }else{
            $res = $client->userTradeCount(array('userId'=>$userId,'startTime'=>$activity->start_at,'endTime'=>$activity->end_at));
        }
        $res = $client->userTradeCount(array('userId'=>$userId,'startTime'=>$activity->start_at,'endTime'=>$activity->end_at));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $cast_all_money = intval($res['result']['data']);
        if($cast_all_money >= $rules['min_recharge_all'] && $cast_all_money <= $rules['max_recharge_all']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'投资总金额规则验证不通过');
    }
}