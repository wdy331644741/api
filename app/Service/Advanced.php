<?php
namespace App\Service;
use Lib\JsonRpcClient;
use App\Service\Func;
use App\Models\UserAttribute;

class Advanced
{
    //获取老用户的任务完成状态
    static function updateAdvancedStatus($userId){
        //注册实名状态
        $userStatus = self::getUserStatus($userId);
        if(!$userStatus['advanced_register']){
            return false;
        }
        $Attributes = new Attributes();
        //投标状态
        $markStatus = self::getMarkStatus($userId);
        foreach($userStatus as $k => $v){
            if($v == 1){
                $Attributes->advanced($userId,"advanced",$k.":1");
            }
        }
        foreach($markStatus as $key => $val){
            if($val >= 1){
                $Attributes->advanced($userId, "advanced", "advanced_target_term_".$key.":1");
            }
        }
        return true;
    }

    /**
     * 获取老用户注册和实名状态
     * @param $userId
     * @return array
     */
    static function getUserStatus($userId){
        $return = ['advanced_register'=>0,'advanced_real_name'=>0];
        if(empty($userId)){
            return $return;
        }
        $userInfo = Func::getUserBasicInfo($userId);
        if(empty($userInfo)){
            return $return;
        }
        if(isset($userInfo['id']) && !empty($userInfo['id'])){
            $return['advanced_register'] = 1;
        }
        if(isset($userInfo['realname']) && !empty($userInfo['realname'])){
            $return['advanced_real_name'] = 1;
        }
        return $return;
    }
    /**
     * 获取老用户的投标
     * @param $userId
     * @return array
     */
    static function getMarkStatus($userId){
        $return = [1=>0, 3=>0, 6=>0, 12=>0];
        if(empty($userId)){
            return $return;
        }
        $url = env('TRADE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $param['user_id'] = $userId;
        $param['secret'] = hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft");
        $result = $client->investProductStatus($param);
        if(isset($result['result']) && !empty($result['result'])){
            return $result['result'];
        }
        return $return;
    }
}