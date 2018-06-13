<?php

namespace App\Jobs;

use App\Models\HdWorldCup;
use App\Models\HdWorldCupSupport;
use App\Models\HdWorldCupExtra;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\Activity;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;



class WorldCupSendAward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $userId;
    private $amount; //返现金额
    private $activityName = 'world_cup_cash';

    //返现奖励
    private $award = [
        "name" => "",//null
        "money" => "0",//null
        "type" => "world_cup_cash",// ?
        "mail" => "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。",
        "message" => "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。",
        "created_at" => "",
        "updated_at" => "",
        "source_id" => "",
        "source_name" => "",
//        "trigger" => 4,
        "user_id" => "",
        "id" => 999,
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->userId;
        $activity = ActivityService::GetActivityedInfoByAlias($this->activityName);
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 2, json_encode(array('err_msg' => 'pass frequency')));
        }
        $this->award['user_id'] = $userId;
        $this->award['source_name'] = $activity->name;
        $this->award['source_id'] = $activity->id;
        $this->award['money'] = $this->amount;
        $this->award['name'] = $this->amount.'元';
        $result = SendAward::cash($this->award);
        //*****活动参与人数加1*****
        Activity::where('id',$activity->id)->increment('join_num');
        //添加活动参与记录
        if($result['status']){
            HdWorldCup::create([
                'user_id'=>$userId,
                'amount' => $this->amount,
                'status' => 1,
                'remark' => json_encode($result)
            ]);
            HdWorldCupSupport::where(['user_id' => $userId])->update(['status'=>1]);
            HdWorldCupExtra::where(['user_id' => $userId])->update([ 'status'=> 1]);
            SendAward::addJoins($userId,$activity,3);
        } else {
            HdWorldCup::create([
                'user_id'=>$userId,
                'amount' => $this->amount,
                'status' => 0,
                'remark' => json_encode($result)
            ]);
        }

    }

}
