<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 17:48
 */
class SendRewardController extends Controller{
    public function postTrigger(Request $request){
        //触发的用户ID
        $userID = isset($request->user_id) ? intval($request->user_id) : 0;
        if(empty($userID)){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'用户id不能为空'));
        }
        //触发的事件
        $triggerName = isset($request->trigger_name) ? trim($request->trigger_name) : '';
        if(empty($triggerName)){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'用户的触发事件不能为空'));
        }
        //查询出该用户触发匹配的活动信息
        $where['name'] = $triggerName;
        $activityInfo = Activity::where($where)->get()->toArray();
        if(!empty($activityInfo)){
            //判断规则是否符合

            //如果符合那么就将活动下的所有符合触发条件的奖品发送到
        }
        print_r($activityInfo);exit;
    }
}