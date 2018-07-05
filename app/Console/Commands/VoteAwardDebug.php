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
    const VERSION = '11.0';
    const ACT_NAME = 'vote_time11.0';//vote_time
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
    protected $description = '极限挑战11.0 现金发奖';


    private static $voteAward = [
        
    ];


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL.self::ACT_NAME.' 返现金放入battle队列'.PHP_EOL.'记录日志：logs/vote_cash'.date('Y-m-d').'.log');
        // $max = $this->ask('一次性放入队列多少条?');
        $activityName = self::ACT_NAME;
        // 活动是否结束
        $activityTime = ActivityService::GetActivityedInfoByAlias($activityName);
        if($activityTime['end_at'] > date('Y-m-d H:i:s') ){
            //活动未结束  不发奖
            $this->error('活动未结束  不发奖');
            die();
        }
        //获取 发奖hash表数据
        $sendList = Redis::hGetAll('voteSendMoney'.self::VERSION);
        if(empty($sendList)){
            //发奖列表为空  不发奖
            $this->error('奖列表为空  不发奖');
            die();
        }

        $fp = fopen(storage_path('logs/vote_cash'.date('Y-m-d').'.log'), 'a');
        $bar = $this->output->createProgressBar(count($sendList));
        //计数
        $eachSend = 0;
        foreach ($sendList as $k => $v) {
            if($v == 0){
                // $this->error("$k : $v has done!");
                fwrite($fp, "$k : $v has done!".PHP_EOL);
                continue;
            }else{
                $mark = Redis::hSet('voteSendMoney'.self::VERSION,$k,0);//发过的 标记为0
                $this->dispatch((new VoteSendAward($k,$v))->onQueue('battle'));
                // $this->dispatch( new VoteSendAward($k,$v) );
                // $this->info("$k : $v put in queue-battle done!");
                // if($eachSend++ > $max)
                //     break;
                fwrite($fp, "$k : $v--put in queue-battle done!".PHP_EOL);
                $bar->advance();
            }
            
        }
        fclose($fp);
        $bar->finish();

    }


}
