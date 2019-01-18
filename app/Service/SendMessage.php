<?php
namespace App\Service;
use Config;
use Lib\JsonRpcClient;

class SendMessage
{

    public static function Mail($userID,$template,$arr=array(),$title = ''){
        if(empty($template)){
            return false;
        }
        $title = empty($title) ? '奖品' : $title ;
        $content = self::msgTemplate($template,$arr);
        $params = array();
        $params['user_id'] = $userID;
        $params['nodeName'] = "message_custom";
        $params['tplParam'] = array();
        $params['customTpl'] = array('title'=>$title,'mtype'=>'activity','content'=>$content,'url'=>'','jump_type'=>0);
        $url = Config::get('cms.message_http_url');
        $client = new JsonRpcClient($url);
        $res = $client->send($params);
        if(isset($res['result']['code']) && $res['result']['code'] === 0){
            return true;
        }
        return false;
    }

    public static function Message($userID,$template,$arr=array()){
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
    //指定模板短信
    public static function MessageByNode($userID,$nodeName, $arr=array()){
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
        $params['node_name'] = $nodeName;
        $params['tplParam'] = $arr;
//        $params['customTpl'] = $content;
        $url = Config::get('cms.message_http_url');
        $client = new JsonRpcClient($url);
        $res = $client->sendSms($params);
        if(isset($res['result']['code']) && $res['result']['code'] === 0){
            return true;
        }
        return false;
    }

    public static function msgTemplate($template,$arr=array()){
        if(empty($template)){
            return false;
        }
        if(empty($arr)){
            return $template;
        }
        $newTemplate = $template;
        foreach($arr as $key => $value) {
            $newTemplate = str_replace("{{".$key."}}",$value,$newTemplate);
        }
        return $newTemplate;
    }

    public static function sendPush($userID,$nodeName,$arr=[]){
        $params = new \stdClass();
        $params->user_id = $userID;
        $params->node_name = $nodeName;
        //$params['user_id'] = $userID;
        //$params['node_name'] = $nodeName;
        //$params['tplParam'] = $arr;
        $url = Config::get('cms.message_http_url');
        $client = new JsonRpcClient($url);
        $res = $client->sendJpush($params);
        if(isset($res['result']['code']) && $res['result']['code'] === 0){
            return true;
        }
        if(isset($res['error']['message'])){
            return $res['error']['message'];
        }
        return false;
    }
}