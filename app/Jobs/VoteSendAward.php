<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;



class VoteSendAward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $amount;
    private $activity = 'vote_time2.0';//瓜分红包

    //快乐大本营
    private $planAAward = [
        //返现
    ];

    //极限挑战
    private $planBAward = [
        //返现
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $actInfo = ActivityService::GetActivityInfoByAlias($this->activity);
        $this->sendAwardDebug($this->userId,$actInfo,'planA');
        // CarnivalRedMoneyService::sendAward($this->amount, $this->userId,$actInfo);

    }

    private function sendAwardDebug($userId ,$activity,$type){
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 1, json_encode(array('err_msg' => 'pass frequency')));
        }
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');
        // //直抵红包相关参数
        // if($type == 'planA'){
        //     $this::$planAAward['source_id'] = $activity['id'];
        //     $this::$planAAward['source_name'] = $activity['name'];
        //     $this::$planAAward['user_id'] = $userId;
        //     $result = SendAward::increases($this::$planAAward);
        // }else{
        //     $this::$planBAward['source_id'] = $activity['id'];
        //     $this::$planBAward['source_name'] = $activity['name'];
        //     $this::$planBAward['user_id'] = $userId;
        //     $result = SendAward::increases($this::$planBAward);
        // }

        //添加活动参与记录
        // if($result['status']){
        //     SendAward::addJoins($userId,$activity,3);
        //     ActivityVote::where(['user_id' => $userId])->update(['status' => 1 ,'remark'=> json_encode($result)]);

            
        // }
    }

}
