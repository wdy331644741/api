<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;

class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityInfo;
    private $userID;
    private $triggerData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityInfo,$userID,$triggerData)
    {
        $this->activityInfo = $activityInfo;
        $this->userID = $userID;
        $this->triggerData = $triggerData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //验证规则和发奖
        return SendAward::ruleCheckAndSendAward($this->activityInfo,$this->userID,$this->triggerData);
    }
}
