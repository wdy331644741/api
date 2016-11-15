<?php
namespace App\Service;
use Config;
use Lib\JsonRpcClient;
use App\Models\OneYuanUserInfo;

class OneYuan
{
    /**
     * 给用户添加积分
     * @param $userId
     * @param $num
     * @return array
     */
    function addNum($userId,$num){
        if(empty($userId) || empty($num)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        $count = OneYuanUserInfo::where("user_id",$userId)->count();
        if(!$count){
            //插入一条数据

        }
    }
    /**
     * 获取该用户的抽奖次数
     * @param $userID
     * @param $template
     * @param $arr
     * @return bool
     */
    public static function getUserLuckNum($userId){
        if(empty($userId)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        //获取用户抽奖次数
        $data = OneYuanUserInfo::where("user_id",$userId)->first();
        $num = isset($data['num']) ? $data['num'] : 0;
        return array("status"=>true,"msg"=>"获取成功","data"=>$num);
    }


}