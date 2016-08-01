<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;
use Config;
use Lib\McQueue;
use Lib\JsonRpcClient;
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
        $requests = $request->all();
        //记录日志
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t requestInfo \t".$requests['tag']."\t *** \t".json_encode($requests)."\n",FILE_APPEND);;
        //触发的事件
        $event = $request->tag;
        //获取trigger_type
        $trigger_type = $this->_getRuleFunc($event);
        $trigger_type = isset($trigger_type) && $trigger_type !== false ? trim($trigger_type) : null;
        //触发的用户ID
        $userID = isset($requests['user_id']) ? intval($requests['user_id']) : 0;
        file_put_contents($logUrl,date("Y-m-d H:i:s")."\t trigger_type&userID \t".$trigger_type."\t**\t".$userID."\n",FILE_APPEND);
        if($trigger_type === null || empty($userID)){
            file_put_contents($logUrl,date("Y-m-d H:i:s")."\t trigger_type&userID&rule \t"."参数错误"."\n",FILE_APPEND);
            return '参数错误';
        }
        //查询出该用户触发匹配的活动信息
        $where['trigger_type'] = $trigger_type;
        $where['enable'] = 1;
        $activityInfo = Activity::where('start_at','<',date("Y-m-d H:i:s"))->where('end_at','>',date("Y-m-d H:i:s"))->where($where)->get()->toArray();
        //队列
        if(!empty($activityInfo)){
            foreach($activityInfo as $item){
                if(!empty($item['id'])){
                    file_put_contents($logUrl,date("Y-m-d H:i:s")."\t activityID&userID \t".$item['id']."\t**\t".$userID."\t放入队列"."\n",FILE_APPEND);
                    //放入队列
                    $this->dispatch(new SendReward($item['id'],$userID,$logUrl,$requests));
                }
            }
        }else{
            file_put_contents($logUrl,date("Y-m-d H:i:s")."\t notAward \t"."没有配置该触发的奖品"."\n",FILE_APPEND);
            return '没有配置该触发的奖品';
        }
        print_r($activityInfo);exit;
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