<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;


class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityID;
    private $userID;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityID,$userID)
    {
        $this->activityID = intval($activityID);
        $this->userID = intval($userID);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        SendAward::addAwardByActivity($this->userID,$this->activityID);
    }
}
