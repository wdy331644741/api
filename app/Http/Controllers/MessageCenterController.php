<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use App\Models\TriggerLog;
use DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;
use Config;
use Lib\McQueue;
use Lib\JsonRpcClient;
use App\Models\ActivityJoin;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 17:48
 */
class MessageCenterController extends Controller{
    /**
     * 接受触发
     * @param Request $request
     * @return string
     */
    public function postCallback(Request $request){
        $requests = $request->all();
        //触发的事件
        $event = $request->tag;
        //获取trigger_type
        $trigger_type = $this->_getRuleFunc($event);
        $trigger_type = isset($trigger_type) && $trigger_type !== false ? trim($trigger_type) : null;
        //触发的用户ID
        $userID = isset($requests['user_id']) ? intval($requests['user_id']) : 0;
        if($trigger_type === null || empty($userID)){
            $logName = base_path()."/storage/logs/callBack_".date("Y-m-d").".log";
            file_put_contents($logName,date("Y-m-d H:i:s").json_encode($requests)."\n",FILE_APPEND);
            return '参数错误';
        }
        //查询出该用户触发匹配的活动信息
        $where['trigger_type'] = $trigger_type;
        $where['enable'] = 1;
        $activityInfo = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where($where)->get()->toArray();
        //队列
        if(!empty($activityInfo)){
            //记录触发消息日志
            $insertData = [
                'user_id'=>$userID,
                'trigger_type'=>$event,
                'info'=>json_encode($requests),
                'day'=>date("Y-m-d"),
                'created_at'=>date("Y-m-d H:i:s"),
            ];
            $res = DB::select("show tables like 'trigger_log'");
            if(!empty($res)){//表存在就添加
                TriggerLog::insertGetId($insertData);
            }
            foreach($activityInfo as $item){
                if(!empty($item['id'])){
                    //放入队列
                    if($event == 'payment'){//如果是回款触发
                        $this->dispatch((new SendReward($item,$userID,$requests))->onConnection("redis")->onQueue('lazy'));
                    }else{
                        $this->dispatch(new SendReward($item,$userID,$requests));
                    }
                }
            }
        }else{
            return '没有配置该触发的活动';
        }
        return json_encode($activityInfo);
    }
    public function getSend(Request $request){
        $mcQueue = new McQueue;
        $data =  ['user_id' => 296];
        $putStatus = $mcQueue->put($request->tag,$data);
        if(!$putStatus)
        {
            $error = $mcQueue->getErrMsg();
            dump($error);
        }
        return "success";
    }
    public function _getRuleFunc($event){
        $trigger = Config::get("trigger.trigger");
        if(empty($trigger)){
            return false;
        }
        foreach($trigger as $key => $val){
            if($val['model_name'] == $event){
                return $key;
            }
        }
        return false;
    }

}