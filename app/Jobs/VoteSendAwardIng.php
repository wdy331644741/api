<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\ActivityVote;
use App\Models\Activity;
use Illuminate\Queue\SerializesModels;
use Log;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;



class VoteSendAwardIng extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $activityName = 'vote_time3.0';
    private $activityInfo;
    
    //红包
    private $_redpack = [
        "id" => 0,
        "name" => "投票3.0",
        "red_type" => 1,
        "red_money" => 50,
        "percentage" => 0.0,
        "effective_time_type" => 1,
        "effective_time_day" => 10,
        "effective_time_start" => null,
        "effective_time_end" => null,
        "investment_threshold" => 10000,
        "project_duration_type" => 3,
        "project_type" => 0,
        "product_id" => "",
        "platform_type" => 0,
        "limit_desc" => "10000元起投，限6月及以上标",
        "created_at" => "",
        "updated_at" => "",
        "project_duration_time" => 6,
        "message" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "mail" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "source_id" => 232,
        "source_name" => "testredMoney",
        "trigger" => 4,
        "user_id" => ''
    ];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->activityInfo = ActivityService::GetActivityedInfoByAlias($this->activityName);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        //验证频次   一天5次
        // $where = array();
        // $where['user_id'] = $userId;
        // $where['activity_id'] = $activity['id'];
        // $where['status'] = 3;
        // $date = date('Y-m-d');
        // $count = ActivityJoin::where($where)->whereRaw("date(created_at) = '{$date}'")->get()->count();

        $this->sendRedpack($this->userId,$this->activityInfo);
    }

    //积分满5次 发放红包-50元的直抵红包
    private function sendRedpack($userId,$activity){

        // $this->_redpack['user_id'] = $userId;
        // $this->_redpack['source_name'] = $activity['name'];
        // $this->_redpack['source_id'] = $activity['id'];
        // SendAward::redMoney($this->_redpack );
        // SendAward::addJoins($userId,$activity,3,'send-redPack');

    }
    
}
