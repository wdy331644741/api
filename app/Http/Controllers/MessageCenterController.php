<?php
namespace App\Http\Controllers;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Jobs\SendReward;

use Lib\JsonRpcClient;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 17:48
 */
class MessageCenterController extends Controller{

    public function postCallback(Request $request){
        //触发的事件
        $triggerName = isset($request->trigger) ? trim($request->trigger) : '';
        //触发的用户ID
        $userID = isset($request->user_id) ? intval($request->user_id) : 0;
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
}