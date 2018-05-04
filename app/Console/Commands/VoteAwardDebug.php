<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityVote;
use Illuminate\Support\Facades\Redis;
use App\Service\ActivityService;
use App\Models\Activity;
use App\Exceptions\OmgException;
use App\Service\SendAward;
use App\Jobs\VoteSendAward;
use Illuminate\Foundation\Bus\DispatchesJobs;

class VoteAwardDebug extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'VoteAwardDebug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    //快乐大本营
    private static $planAAward = [
        //返现奖励
    ];
    
    //极限挑战
    private static $planBAward = [
        //返现奖励
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $this->comment(PHP_EOL.Inspiring::quote().PHP_EOL);
        $activityName = 'vote_time2.0';
        // 活动是否存在
        // if(!ActivityService::isExistByAlias($activityName)) {
        //     throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        // }
        $activityTime = ActivityService::GetActivityedInfoByAlias($activityName);

        //$victoryOptioin = ($planAview > $planBview )?'planA':'planB';
        // $lostOptioin = ($planA>$planB)?'planB':'planA';// 败的不发奖
        //$victorylist = Redis::zRange($victoryOptioin."_list" , 0 ,-1);
        // $lostlist = Redis::zRange($lostOptioin."_list" , 0 ,-1);
        $planAlist = Redis::zRange("planA_list" , 0 ,-1);
        foreach ($planAlist as $v) {
            $this->dispatch((new VoteSendAward($v))->onQueue('lazy'));
            
            // $this->sendAward($v,$activityTime,'planA');
        }

    }


}
