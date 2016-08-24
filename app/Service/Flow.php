<?php
namespace App\Service;
use Config;
use Lib\JsonRpcClient;

class Flow
{
    //公共购买方法
    public  static function buy($recordJson){
        //购买url
        $url = Config::get('flow.bug_url');
        //参数json拼接
        $data = array();
        $data['cId'] = '';
        $data['bId'] = '';
        $data['recordList'] = $recordJson;
        $data['ext'] = '';
        $data['v'] = '';
        $data['sign'] = '';
        return self::curlPost($url,$data);
    }

    //购买2G流量
    public static function buy2G($user_id,$type){
        //根据user_id获取手机号
        $phone = '18701656515';
        $code = Config::get('flow.code');
        $recordList = array();
        if($type == 'yd'){
            for($i=1;$i<=2;$i++){
                $recordList[] = array(
                    'cOrderId'=>date("YmdHis").mt_rand(1000000000,9999999999),
                    'cProductId'=>$code['yd']['1024'],
                    'receiver'=>$phone,
                    'ext'=>'',
                );
            }
        }elseif($type == 'lt'){
            for($i=1;$i<=10;$i++){
                $recordList[] = array(
                    'cOrderId'=>date("YmdHis").mt_rand(1000000000,9999999999),
                    'cProductId'=>$code['lt']['200'],
                    'receiver'=>$phone,
                    'ext'=>'',
                );
            }
        }elseif($type == 'dx'){
            for($i=1;$i<=4;$i++){
                $recordList[] = array(
                    'cOrderId'=>date("YmdHis").mt_rand(1000000000,9999999999),
                    'cProductId'=>$code['dx']['500'],
                    'receiver'=>$phone,
                    'ext'=>'',
                );
            }
        }
        $recordJson = json_encode($recordList);
        echo $recordJson;exit;
        self::buy2G($recordJson);
    }
    //接收回调
    public static function callBack($userID,$template,$arr){
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
    public static function curlPost($url,$data){
        $post_data = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        return $output;
    }
}