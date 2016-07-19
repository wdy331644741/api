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
        $logUrl = $path = base_path().'/storage/logs/messageCenter.log';
        //记录日志
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t".$request->tag."\t**\t".$request->value."\n",FILE_APPEND);
        //获取参数
        $value = json_decode($request->value,1);
        //触发的事件
        $event = $request->tag;
        if($event == 'login'){
            $name = "登陆";
        }else if($event == 'register'){
            $name = "注册";
        }
        $triggerName = isset($name) ? trim($name) : '';
        //触发的用户ID
        $userID = isset($value['user_id']) ? intval($value['user_id']) : 0;
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t".$triggerName."\t**\t".$userID."\n",FILE_APPEND);
        exit;
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
        $json  = json_encode(["tag" => $tag, "value" => $value]);
        $res =  $redis->LPUSH('msg_queue', $json);
        var_dump($res);exit;
        return json_encode(["result" => "ok"]);
    }

}