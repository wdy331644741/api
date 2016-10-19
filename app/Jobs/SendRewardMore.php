<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;

class SendRewardMore extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $awardType;
    private $awardId;
    private $sourceName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $awardType, $awardId, $sourceName)
    {
        $this->awardType = $awardType;
        $this->userId = $userId;
        $this->awardId = $awardId;
        $this->sourceName = $sourceName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //验证规则和发奖
        return SendAward::sendDataRole($this->userId, $this->awardType, $this->awardId, 0, $this->sourceName);
    }
}
