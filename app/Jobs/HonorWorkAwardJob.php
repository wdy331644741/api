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
use Illuminate\Support\Facades\Redis;



class HonorWorkAwardJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $userId;
    private $welfare_alais_act;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$alais)
    {
        $this->userId = $userId;
        $this->welfare_alais_act = $alais;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $res = SendAward::ActiveSendAward($this->userId,$this->welfare_alais_act);
        dd($res);
    }


}
