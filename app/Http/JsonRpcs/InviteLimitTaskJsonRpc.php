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

        $activit_all_doing = $server->getTaskingByDay();
        return $activit_all_doing;
        $user_data = $server->userActivitData();
// return $data;
        $done_task_array = [];//已经完成的任务
        $doing_task_array = [];//正在进行的任务
        $task_done_num = 0;

        foreach ($user_data as $key => $value) {
            if($value['status'] == 1){
                array_push($done_task_array, $value['alias_name']);
                $task_done_num++;
            }
            if($value['limit_time'] > date('Y-m-d H:i:s')){
                $limit_time = strtotime($value['limit_time']) - time();
                array_push($doing_task_array, [$value['alias_name']=> $limit_time ]);
            }

        }

        
        return $doing_task_array;
        return array(
            'message' => 'success',
            'data' => [
                'is_login' => $userId?true:false,
                'task_done_num' => 0,
                'invite_num' => 0,
                'task_info' => [
                    [
                        'status' => 0,//任务领取1234
                        'over_num' => 100,
                        'over_time' => 360,
                    ],
                    [
                        'status' => 0,//任务领取1234
                        'over_num' => 100,
                        'over_time' => 360,
                    ],
                    [
                        'status' => 0,//任务领取1234
                        'over_num' => 100,
                        'over_time' => 360,
                    ]
                ]
            ]
        );


    }

}