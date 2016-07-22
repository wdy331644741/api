<?php
namespace App\Service;

use App\Models\Rule;
use Lib\JsonRpcClient;
use Config;
class RuleCheck
{
    public static function register($activity_id,$userId){
        $url = Config::get('award.rulecheck_user_http_url');
        $activity = Rule::where('activity_id',$activity_id)->first();
        $rules = (array)json_decode($activity->rule_info);
        $client = new JsonRpcClient($url);
        $res = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($res['error'])){
            return array('send'=>true,'errmsg'=>$res['error']['message']);
        }
        $register_time = $res['result']['data']['create_time'];
        if($register_time >= $rules['min_time'] && $register_time <= $rules['max_time']){
            return array('send'=>true);
        }
        return array('send'=>false,'errmsg'=>'规则验证不通过');

    }
}