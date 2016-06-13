<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 17:48
 */
class MessageCenterController extends Controller{

    public function postCallback(Request $request){
        $value = json_decode($request->value,1);
        //触发的事件
        $triggerName = isset($value['trigger']) ? trim($value['trigger']) : '';
        //触发的用户ID
        $userID = isset($value['user_id']) ? intval($value['user_id']) : 0;
        if(empty($triggerName) || empty($userID)){
            return ;
        }
        //查询出该用户触发匹配的活动信息
        $where['name'] = $triggerName;
        $activityInfo = Activity::where($where)->get()->toArray();
        //队列
        if(!empty($activityInfo)){
            foreach($activityInfo as $item){
                $this->dispatch(new SendReward($item['id'],$userID,$triggerName));
            }
        }
        print_r($activityInfo);exit;
    }
    private function connect() {
        $this->redis = new \Redis();
        $this->redis->connect("192.168.10.36",6379);
        return $this->redis;
    }
    public function getSend(Request $request){
        $tag = $request->tag;
        $value = $request->value;
        $this->ip = '192.168.10.36';
        $this->port = 6379;
        $redis = $this->connect($this->ip,$this->port);
        $json  =json_encode(["tag" => $tag, "value" => $value]);
//        echo $json;exit;
        $res =  $redis->LPUSH('msg_queue', $json);
        var_dump($res);
        return json_encode(["result" => "ok"]);
    }

}