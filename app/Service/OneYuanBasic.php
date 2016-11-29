<?php
namespace App\Service;

use App\Models\OneYuan;
use App\Models\OneYuanUserInfo;
use App\Models\OneYuanJoinInfo;
use App\Models\OneYuanUserRecord;
use App\Service\SendAward;
use DB;
class OneYuanBasic
{
    /**
     * 给用户添加抽奖次数
     * @param $userId
     * @param $num
     * @return array
     */
    static function addNum($userId,$num,$source,$snapshot = array()){
        if(empty($userId) || empty($num) || empty($snapshot)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        $count = OneYuanUserInfo::where("user_id",$userId)->count();
        //记录表数据
        $operation = array();
        $operation['user_id'] = $userId;
        $operation['num'] = $num;
        $operation['source'] = $source;
        $operation['snapshot'] = json_encode($snapshot);
        $operation['type'] = 0;
        $operation['operation_time'] = date("Y-m-d H:i:s");
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
                //添加到记录表中
                OneYuanUserRecord::insertGetId($operation);
                return array("status"=>true,"msg"=>"添加成功","data"=>$id);
            }
            return array("status"=>true,"msg"=>"添加失败");
        }
        $status = OneYuanUserInfo::where('user_id',$userId)->increment('num', $num,array('updated_at'=>date("Y-m-d H:i:s")));
        if($status){
            //添加到记录表中
            OneYuanUserRecord::insertGetId($operation);
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
    static function reduceNum($userId,$num,$source,$snapshot = array()){
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
            //记录表数据
            $operation = array();
            $operation['user_id'] = $userId;
            $operation['num'] = $num;
            $operation['source'] = $source;
            $operation['snapshot'] = json_encode($snapshot);
            $operation['type'] = 1;
            $operation['operation_time'] = date("Y-m-d H:i:s");
            OneYuanUserRecord::insertGetId($operation);
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
    static function insertJoinInfo($userId,$mallId,$num){
        if(empty($userId) || empty($mallId) || empty($num)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        //获取该商品的最大值
        $max = OneYuanJoinInfo::where("mall_id",$mallId)->select('end')->orderBy("end","desc")->first();
        $end = isset($max['end']) ? $max['end'] : 0;
        //添加的数据
        $time = time();
        $data = array();
        $data['user_id'] = $userId;
        $data['mall_id'] = $mallId;
        $data['num'] = $num;
        $data['buy_time'] = date('His',$time);
        $data['created_at'] = date("Y-m-d H:i:s",$time);
        $data['updated_at'] = date("Y-m-d H:i:s");
        $data['start'] = $end+1;
        $data['end'] = $end+$num;
        if(empty($max)){
            $data['start'] = 1;
            $data['end'] = $num;
        }
        $id = OneYuanJoinInfo::insertGetId($data);
        if($id){
            return array("status"=>true,"msg"=>"抽奖成功","data"=>$id);
        }
        return array("status"=>false,"msg"=>"抽奖失败");
    }
    //根据商品id和开奖码抽奖
    static function luckDraw($mall_id,$code){
        //验证参数
        $mall_id = intval($mall_id);
        $code = intval($code);
        if(empty($mall_id) || empty($code)){
            return array("status"=>false,"msg"=>"参数有误");
        }
        //判断抽奖次数是否已满
        $where = array();
        $where['id'] = $mall_id;
        $where['user_id'] = 0;
        $where['buy_id'] = 0;
        $where['code'] = 0;
        $where['luck_code'] = 0;
        $where['total_times'] = 0;
        $where['join_users'] = 0;
        $where['luck_time'] = 0;
        $isFull = OneYuan::where($where)->first();
        if(empty($isFull)){
            return array("status"=>false,"msg"=>"奖品不存在");
        }
        if($isFull->total_num != $isFull->buy_num){
            return array("status"=>false,"msg"=>"奖品还没参与满");
        }
        //开始抽奖
        //获取最后50个用户的时间和
        $times = OneYuanJoinInfo::where('mall_id',$mall_id)->select(DB::raw('SUM(buy_time) as times'))
            ->orderBy('id','desc')
            ->take(50)
            ->first();
        if(empty($times)){
            return array("status"=>false,"msg"=>"数据有误");
        }
        //修改数组
        $up = array();
        $up['code'] = $code;
        $up['total_times'] = $times['times'];
        //获取总人次
        $users = OneYuanJoinInfo::where('mall_id',$mall_id)->select(DB::raw('COUNT(distinct(user_id)) as count'))->first();
        if(empty($users)){
            return array("status"=>false,"msg"=>"数据有误");
        }
        $up['join_users'] = $users['count'];
        $up['luck_code'] = ($up['total_times']+$up['code'])%$isFull['total_num'];
        if(empty($up['luck_code'])){
            return array("status"=>false,"msg"=>"中奖码没算出来");
        }
        $joinInfo = OneYuanJoinInfo::where('mall_id',$mall_id)
            ->where('start','<=',$up['luck_code'])
            ->where('end','>=',$up['luck_code'])
            ->first();
        if(empty($joinInfo)){
            return array("status"=>false,"msg"=>"找不到抽奖记录");
        }
        $up['user_id'] = $joinInfo['user_id'];
        $up['buy_id'] = $joinInfo['id'];
        $up['luck_time'] = $joinInfo['created_at'];
        $status = OneYuan::where('id',$mall_id)->update($up);
        if($status){
            //发送站内信
            $template = "恭喜您在夺宝奇兵活动中获得'{{award_name}}'，我们的客服人员会及时联系您，请保持手机畅通。";
            $arr = array('award_name'=>$isFull['name']);
            SendMessage::Mail($up['user_id'],$template,$arr);
            return array("status"=>true,"msg"=>"开奖成功");
        }
        return array("status"=>false,"msg"=>"开奖失败");
    }
}