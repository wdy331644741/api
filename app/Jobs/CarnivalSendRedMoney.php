<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;



class CarnivalSendRedMoney extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $amount;
    private $activity = 'carnivalDivide';//瓜分红包
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($amount,$userId)
    {
        $this->amount = $amount;
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
        CarnivalRedMoneyService::sendAward($this->amount, $this->userId,$actInfo);

    }
}
