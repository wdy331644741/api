<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


class CarnivalSendListRedMoney extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userIdList;
    private $activity = 'carnivalRank';//排行榜前20红包
    private $_awardConfig = [];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userIdList)
    {   
        $this->_awardConfig = config('carnival');
        $this->userIdList = $userIdList;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // var_dump($this->userIdList);exit;
        $actInfo = ActivityService::GetActivityInfoByAlias($this->activity);
        
        CarnivalRedMoneyService::sendListAward($this->userIdList,$actInfo);
    }
}
