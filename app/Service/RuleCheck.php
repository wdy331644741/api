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

    private static $account_reward_url = 'http://account.dev.wanglibao.com/service.php?c=reward';

    //规则验证
    public static function check($activity_id,$userId,$sqsmsg){
        $url = Config::get('award.rulecheck_user_http_url');
        $activity = Rule::where('activity_id',$activity_id)->get();
        $client = new JsonRpcClient(self::$user_api_url);
        $userBase = $client->userBasicInfo(array('userId'=>$userId));
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
                    $res = self::_cast($userId,$value,$sqsmsg);
                    break;
                case $value->rule_type === 8:
                    $res = self::_recharge($userId,$value,$sqsmsg);
                    break;
                case $value->rule_type === 9:
                    $res = self::_payment($userId,$value);
                    break;
                case $value->rule_type === 10:
                    $res = self::_castAll($userId,$value);
                    break;
                case $value->rule_type === 11:
                    $res = self::_rechargeAll($userId,$value);
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
        if($user_channel == $rules['channels']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'渠道规则验证不通过');
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
        $res = $client->getInviteList(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>false,'errmsg'=>$res['error']['message']);
        }
        $inviteNum = count($res['result']['data']);
        if($inviteNum >= $rules['invite_num']){
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

    #TODO //用户投资（cast消息通知未添加）
    private static function _cast($userId,$rule,$sqsmsg){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $res = $client->userTradeInfo(array('userId'=>$userId));
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
        $res = $client->userRechargeInfo(array('userId'=>$userId));
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
    private static function _castAll($rule,$userId){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $res = $client->userRechargeCount(array('userId'=>$userId,'startTime'=>$rules['start_time'],'endTime'=>$rules['end_time']));
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
    private static function _rechargeAll($rule,$userId){
        $rules = (array)json_decode($rule->rule_info);
        $client = new JsonRpcClient(self::$trade_api_url);
        $res = $client->userTradeCount(array('userId'=>$userId,'startTime'=>$rules['start_time'],'endTime'=>$rules['end_time']));
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