<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\InviteTaskService;
use App\Service\SendMessage;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Validator, Config, Request;

class InviteLimitTaskJsonRpc extends JsonRpc
{

    /* 好友邀请3.0 限时任务 */

    public static $shareTaskName = 'invite_limit_task_exp';

    //固定 活动名字
    public static $strToNumKey = [
            'invite_limit_task_exp'    =>1,
            'invite_limit_task_bind'   =>2,
            'invite_limit_task_invest' =>3,
    ];

	/**
     * 领取任务
     *
     * @JsonRpcMethod
     */
    public function limitTaskDraw($params){
        if(empty($params->task)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //任务编号
        $task = array_search($params->task, self::$strToNumKey);
        if(!$task){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        
        //用户ID
    	global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

    	// 活动是否存在
        if(ActivityService::isExistByAlias($task)) {
            
        }else{
        	throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $ser = new InviteTaskService($userId);
        $res = $ser->addTaskByUser($task);

        //领取成功发送 推送站内信
        SendMessage::Mail($userId,'恭喜您在“邀友赚赏金”限时活动中抢到 50 元现金奖励任务，24 小时内完成任务则现金实时发放至您网利宝账户中。');
        //发送成功 //return true/false

        return array(
                'message' => 'success',
                'data'    => $res
	        );
    }


    /**
     *  任务1 分享成功获得体验金
     * invite_limit_task_exp
     *
     * @JsonRpcMethod
     */
    public function shareInviteTask(){
        //用户ID
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        // 活动是否存在
        if(ActivityService::isExistByAlias(self::$shareTaskName)) {
            
        }else{
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $ser = new InviteTaskService($userId);
        $res = $ser->updateExpTask();
        return $res;
    }

    /**
     *  前端数据
     *
     * @JsonRpcMethod
     */
    public function friendLimitTaskInfo(){
        //用户ID
        global $userId;

        // 活动是否存在
        if(ActivityService::isExistByAlias(self::$shareTaskName)) {
            
        }else{
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $server = new InviteTaskService($userId);
        $activit_all_done = $server->getTaskedByDay();//查询的 属性表里面的‘完成数’

        $activit_all_doing_obj = $server->getTaskingByDay(); //select count(*) as user_count, alias_name from  where 当天，status，任务过期时间 > now() group by alias_name;
        //转换数据结构  以活动名为键值
        $activit_all_doing = array_column($activit_all_doing_obj->ToArray(), 'user_count','alias_name');



        $user_data = $server->userActivitData();//该用户当天所有的数据
        $done_task_array = [];//该用户今天 已经完成的任务[1,2,3]
        $doing_task_array = [];//该用户今天 正在进行的任务[ [alias_name=>time] ]
        
        $task_done_num = $server->getAllDoneTaskByUser();//活动期间内 用户完成的任务数

        //遍历 当天用户各个任务的状态  （显示给前端）
        foreach ($user_data as $key => $value) {
            if($value['status'] == 1){
                array_push($done_task_array, $value['alias_name']);
            }else if($value['limit_time'] > date('Y-m-d H:i:s')){
                $limit_time = strtotime($value['limit_time']) - time();
                $doing_task_array[$value['alias_name']] = $limit_time;
            }
        }
        $newArray = [];//task_info  数据
        foreach ($server->tasks_total as $key => $value) {
            //任务剩余
            $over_num = $value 
                    - (isset($activit_all_done[$key])?$activit_all_done[$key]:0 )
                    - (isset($activit_all_doing[$key])?$activit_all_doing[$key]:0 );

            //0领取  1立即前往 2已完成 3已抢光
            $task_status = !array_key_exists($key,$doing_task_array)?!in_array($key, $done_task_array)?!$over_num?3:0:2:1;
            //任务倒计时
            $over_time = $task_status!=1?0:isset($doing_task_array[$key])?$doing_task_array[$key]:0;


            $newArray[self::$strToNumKey[$key]] = [
                'status'=>$task_status,
                'over_num'=>$over_num,
                'over_time'=>$over_time
            ];
        }
        return array(
            'message' => 'success',
            'data'    => [
                'is_login'      => $userId?true:false,
                'task_done_num' => $task_done_num,
                'invite_num'    => 0,
                'task_info'     => $newArray
            ]
        );


    }

}