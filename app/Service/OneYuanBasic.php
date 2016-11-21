<?php
namespace App\Service;
use App\Models\OneYuanUserInfo;
use App\Models\OneYuanBuyInfo;
class OneYuanBasic
{
    /**
     * 给用户添加抽奖次数
     * @param $userId
     * @param $num
     * @return array
     */
    static function addNum($userId,$num){
        if(empty($userId) || empty($num)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        $count = OneYuanUserInfo::where("user_id",$userId)->count();
        //判断是否存在
        if(!$count){
            //插入一条数据
            $data = array();
            $data['user_id'] = $userId;
            $data['num'] = $num;
            $data['updated_at'] = date("Y-m-d H:i:s");
            $data['created_at'] = date("Y-m-d H:i:s");
            $id = OneYuanUserInfo::insertGetId($data);
            if($id){
                return array("status"=>true,"msg"=>"添加成功","data"=>$id);
            }
            return array("status"=>true,"msg"=>"添加失败");
        }
        $status = OneYuanUserInfo::where('user_id',$userId)->increment('num', $num,array('updated_at'=>date("Y-m-d H:i:s")));
        if($status){
            return array("status"=>true,"msg"=>"添加成功","data"=>$status);
        }
        return array("status"=>true,"msg"=>"添加失败");
    }
    /**
     * 给用户减少抽奖次数
     * @param $userId
     * @param $num
     * @return array
     */
    static function reduceNum($userId,$num){
        if(empty($userId) || empty($num)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        $count = OneYuanUserInfo::where("user_id",$userId)->count();
        //判断是否存在
        if(!$count){
            return array("status"=>true,"msg"=>"该用户没有抽奖次数");
        }
        $status = OneYuanUserInfo::where('user_id',$userId)->decrement('num', $num,array('updated_at'=>date("Y-m-d H:i:s")));
        if($status){
            return array("status"=>true,"msg"=>"扣除抽奖次数成功","data"=>$status);
        }
        return array("status"=>true,"msg"=>"扣除抽奖次数失败");
    }
    /**
     * 获取该用户的抽奖次数
     * @param $userID
     * @param $template
     * @param $arr
     * @return bool
     */
    static function getUserLuckNum($userId){
        if(empty($userId)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        //获取用户抽奖次数
        $data = OneYuanUserInfo::where("user_id",$userId)->first();
        $num = isset($data['num']) ? $data['num'] : 0;
        return array("status"=>true,"msg"=>"获取成功","data"=>$num);
    }
    /**
     * 添加到抽奖记录表中
     * @param $userID,$mallId,$num
     * @param $template
     * @param $arr
     * @return bool
     */
    static function insertBuyInfo($userId,$mallId,$num){
        if(empty($userId) || empty($mallId) || empty($num)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        //获取该商品的最大值
        $max = OneYuanBuyInfo::where("mall_id",$mallId+1)->select('end')->orderBy("end","desc")->first();
        $end = isset($max['end']) ? $max['end'] : 0;
        //添加的数据
        $data = array();
        $data['user_id'] = $userId;
        $data['mall_id'] = $mallId;
        $data['num'] = $num;
        $data['buy_time'] = time();
        $data['created_at'] = date("Y-m-d H:i:s");
        $data['updated_at'] = date("Y-m-d H:i:s");
        $data['start'] = $end+1;
        $data['end'] = $end+$num;
        if(empty($max)){
            $data['start'] = 1;
            $data['end'] = $num;
        }
        $id = OneYuanBuyInfo::insertGetId($data);
        if($id){
            return array("status"=>true,"msg"=>"抽奖成功","data"=>$id);
        }
        return array("status"=>false,"msg"=>"抽奖失败");
    }

}