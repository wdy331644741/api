<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;
use Config;
use Lib\McQueue;
use Lib\JsonRpcClient;
use App\Models\ActivityJoin;
use App\Jobs\SendRewardMore;
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
            foreach($activityInfo as $item){
                if(!empty($item['id'])){
                    //放入队列
                    $this->dispatch(new SendReward($item,$userID,$requests));
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
    /**
     * 王小春那批量发送奖品放入队列
     *
     * @param $userId,$awardType,$awardId,$sourceName
     * @return json
     */
    function _putRewardMore($userId,$awardType,$awardId,$sourceName) {
        return $this->dispatch(new SendRewardMore($userId,$awardType,$awardId,$sourceName));
    }
}