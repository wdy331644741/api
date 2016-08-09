<?php
namespace App\Service;
use Config;
use Lib\JsonRpcClient;

class SendMessage
{

    public static function Mail($userID,$template,$arr){
        if(empty($template)){
            return false;
        }
        $content = self::msgTemplate($template,$arr);
        $params = array();
        $params['user_id'] = $userID;
        $params['nodeName'] = "message_custom";
        $params['tplParam'] = array();
        $params['customTpl'] = array('title'=>'奖品','content'=>$content,'url'=>'','jump_type'=>0);
        $url = Config::get('cms.message_http_url');
        $client = new JsonRpcClient($url);
        $res = $client->send($params);
        if(isset($res['result']['code']) && $res['result']['code'] === 0){
            return true;
        }
        return false;
    }

    public static function Message($userID,$template,$arr){
        if(empty($template)){
            return false;
        }
        $content = self::msgTemplate($template,$arr);
        //根据用户ID获取手机号
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $userBase = $client->userBasicInfo(array('userId'=>$userID));
        $phone = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : '';
        if(empty($phone)){
            return false;
        }
        $params = array();
        $params['phone'] = $phone;
        $params['node_name'] = "custom";
        $params['tplParam'] = array();
        $params['customTpl'] = $content;
        $url = Config::get('cms.message_http_url');
        $client = new JsonRpcClient($url);
        $res = $client->sendSms($params);
        if(isset($res['result']['code']) && $res['result']['code'] === 0){
            return true;
        }
        return false;
    }
    public static function msgTemplate($template,$arr){
        if(empty($template)){
            return false;
        }
        $newTemplate = $template;
        foreach($arr as $key => $value) {
            $newTemplate = str_replace("{{".$key."}}",$value,$newTemplate);
        }
        return $newTemplate;
    }
}