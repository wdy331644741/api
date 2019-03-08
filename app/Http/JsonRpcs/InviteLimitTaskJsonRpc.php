<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\InviteTaskService;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Validator, Config, Request;

class InviteLimitTaskJsonRpc extends JsonRpc
{

    /* 好友邀请3.0 限时任务 */

    public static $shareTaskName = 'invite_limit_task_exp';


    public static $strToNumKey = [
            'invite_limit_task_exp'=>1,
            'invite_limit_task_bind'=>2,
            'invite_limit_task_invest'=>3,
    ];

	/**
     * 领取任务
     *
     * @JsonRpcMethod
     */
    public function limitTaskDraw($params){
        //任务编号
        switch ($params->task) {
            case 1:
                $task = 'invite_limit_task_exp';
                break;
            case 2:
                $task = 'invite_limit_task_bind';
                break;
            case 3:
                $task = 'invite_limit_task_invest';
                break;

            default:
                throw new OmgException(OmgException::PARAMS_ERROR);
                break;
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

        return array(
	            'message' => 'success',
	            'data' => $res
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
        $activit_all_done = $server->getTaskedByDay();

        $activit_all_doing_obj = $server->getTaskingByDay();
        $activit_all_doing = array_column($activit_all_doing_obj->ToArray(), 'user_count','alias_name');



        $user_data = $server->userActivitData();//用户所有的数据
        $done_task_array = [];//当前已经完成的任务[1,2,3]
        $doing_task_array = [];//当前正在进行的任务[ [alias_name=>time] ]
        
        $task_done_num = $server->getAllDoneTaskByUser();//活动期间内 用户完成的任务数

        //遍历 当天用户各个任务的状态
        foreach ($user_data as $key => $value) {
            if($value['status'] == 1){
                array_push($done_task_array, $value['alias_name']);
            }else if($value['limit_time'] > date('Y-m-d H:i:s')){
                $limit_time = strtotime($value['limit_time']) - time();
                $doing_task_array[$value['alias_name']] = $limit_time;
                // array_push($doing_task_array, [$value['alias_name']=> $limit_time ]);
            }
        }
        $newArray = [];
        foreach ($server->tasks_total as $key => $value) {
            //任务剩余
            $over_num = $value 
                    - (isset($activit_all_done[$key])?$activit_all_done[$key]:0 )
                    - (isset($activit_all_doing[$key])?$activit_all_doing[$key]:0 );

            //0领取  1立即前往 2已完成 3已抢光

            // if(array_key_exists($key,$doing_task_array)){
            //     $task_status = 1;
            // }else{
            //     if(in_array($key, $done_task_array)){
            //         $task_status = 2;
            //     }else{
            //         if($over_num){
            //             $task_status = 0;
            //         }else{
            //             $task_status = 3;
            //         }
            //     }
            // }
            //js
            //$task_status = array_key_exists($key,$doing_task_array)?1:in_array($key, $done_task_array)?2:$over_num?0:3;
            $task_status = !array_key_exists($key,$doing_task_array)?!in_array($key, $done_task_array)?!$over_num?3:0:2:1;

            $over_time = $task_status!=1?0:isset($doing_task_array[$key])?$doing_task_array[$key]:0;
            $newArray[self::$strToNumKey[$key]] = ['status'=>$task_status,'over_num'=>$over_num,'over_time'=>$over_time];
        }
        return array(
            'message' => 'success',
            'data' => [
                'is_login' => $userId?true:false,
                'task_done_num' => $task_done_num,
                'invite_num' => 0,
                'task_info' => $newArray
            ]
        );


    }

}