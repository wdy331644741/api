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


    private static $voteAward = [
        
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
        // 活动是否结束
        $activityTime = ActivityService::GetActivityedInfoByAlias($activityName);
        if($activityTime['end_at'] > date('Y-m-d H:i:s') ){
            //活动未结束  不发奖
            echo "活动未结束  不发奖";
            die();
        }
        //获取 发奖hash表数据
        $sendList = Redis::hGetAll('voteSendMoney');
        if(empty($sendList)){
            //发奖列表为空  不发奖
            echo "奖列表为空  不发奖";
            die();
        }

        foreach ($sendList as $k => $v) {
            if($v == 0){
                echo "$k : $v has done!".PHP_EOL;
                continue;
            }else{
                $mark = Redis::hSet('voteSendMoney',$k,0);//发过的 标记为0
                // $this->dispatch((new VoteSendAward($k,$v))->onQueue('lazy'));
                $this->dispatch( new VoteSendAward($k,$v) );
                echo "$k : $v put in queue done!".PHP_EOL;
            }
            
        }

    }


}
