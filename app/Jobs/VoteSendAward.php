<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\ActivityVote;
use App\Models\Activity;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;



class VoteSendAward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $amount; //返现金额
    private $activityName = 'vote_time2.0';
    private $activityInfo;

    private $voteAward = [
        //返现奖励
        "id" => 0,
        "name" => "2分钱",//null
        "money" => "0.02",//null
        "type" => "2分钱",// ?
        "mail" => "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。",
        "message" => "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。",
        "created_at" => "2017-05-18 11:39:21",
        "updated_at" => "2017-05-18 11:39:21",
        "source_id" => "",
        "source_name" => "",
        "trigger" => 4,
        "user_id" => "",
    ];


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$amount)
    {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->activityInfo = ActivityService::GetActivityedInfoByAlias($this->activityName);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->sendAwardCash($this->userId,$this->activityInfo);
        // CarnivalRedMoneyService::sendAward($this->amount, $this->userId,$actInfo);

    }

    private function sendAwardCash($userId ,$activity){
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 1, json_encode(array('err_msg' => 'pass frequency')));
        }
        $this->voteAward['user_id'] = $userId;
        $this->voteAward['source_name'] = $activity['name'];
        $this->voteAward['source_id'] = $activity['id'];
        $this->voteAward['money'] = $this->amount;
        $result = SendAward::cash($this->voteAward);
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');


        //添加活动参与记录
        if($result['status']){
            SendAward::addJoins($userId,$activity,3);
            ActivityVote::where(['user_id' => $userId , 'status' => 2])->update(['status' => 3 ,'remark'=> json_encode($result)]);

            
        }
    }

}
