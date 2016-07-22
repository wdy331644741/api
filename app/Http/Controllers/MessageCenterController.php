<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;
use App\Service\RuleCheck;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 17:48
 */
class MessageCenterController extends Controller{

    public function postCallback(Request $request){
        $date = date("Ymd");
        $logUrl = $path = base_path().'/storage/logs/messageCenter'.$date.'.log';
        //记录日志
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t requestInfo \t".$request->tag."\t *** \t".json_encode($request->value)."\n",FILE_APPEND);
        //获取参数
        $value = $request->value;
        //触发的事件
        $event = $request->tag;
        $rule = "";
        if($event == 'register'){
            //注册
            $trigger_type = 1;
            $rule = "register";
        }else if($event == 'login'){
            //登陆
            $trigger_type = 2;
            $rule = "login";
        }
        $trigger_type = isset($trigger_type) ? trim($trigger_type) : '';
        //触发的用户ID
        $userID = isset($value['user_id']) ? intval($value['user_id']) : 0;
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t trigger_type&userID \t".$trigger_type."\t**\t".$userID."\n",FILE_APPEND);
        if(empty($trigger_type) || empty($userID) || empty($rule)){
            file_put_contents($logUrl,date("Y-m-d H:i:s")."\t trigger_type&userID \t"."参数错误"."\n",FILE_APPEND);
            return ;
        }
        //查询出该用户触发匹配的活动信息
        $where['trigger_type'] = $trigger_type;
        $activityInfo = Activity::where($where)->get()->toArray();
        //队列
        if(!empty($activityInfo)){
            foreach($activityInfo as $item){
                if(!empty($item['id'])){
                    file_put_contents($logUrl,date("Y-m-d H:i:s")."\t activityID \t".$item['id']."\n",FILE_APPEND);
                    //验证规则
                    $status = RuleCheck::$rule($item['id'],$userID);
                    if($status['send'] === true){
                        //调用发奖队列
                        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t"."开始发奖 活动ID:".$item['id']."  用户ID:".$userID."\n",FILE_APPEND);
                        $this->dispatch(new SendReward($item['id'],$userID));
                    }else{
                        //记录规则错误日志
                        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t ruleErrorMsg \t".$status['errmsg']."\n",FILE_APPEND);
                    }
                }
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