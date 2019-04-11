<?php

namespace App\Console\Commands;

use App\Models\UserAttribute;
use App\Service\PerBaiService;
use Illuminate\Console\Command;
use App\Jobs\SendPushJob;
use Illuminate\Foundation\Bus\DispatchesJobs;

class PertenGuess extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PertenGuess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日 11:00 全量提醒获得抽奖号码用户（大盘预言）';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $activity = PerBaiService::getActivityInfo();
            if (!$activity) {
                throw new \Exception('活动不存在');
            }
            //活动未开始
            if ( time() < strtotime($activity['start_time']) ) {
                throw new \Exception('活动未开始');
            }
            //活动已结束 ,号码发完
            if ( 0 >= PerBaiService::getRemainNum() ) {
                throw new \Exception('号码已发完');
            }
            $guessKey = PerBaiService::$guessKeyUser . $activity['id'];
            $count = $data = UserAttribute::where(['key'=>$guessKey])->count();
            $perPage = 100;
            $num = ceil($count / $perPage);
            for ($i=0; $i<$num; $i++) {
                $offset = $i * $perPage;
                $data = UserAttribute::select('user_id', 'number')->where(['key'=>$guessKey])->offset($offset)->limit($perPage)->get()->toArray();
                foreach ($data as $v) {
                    if ($v['number'] <= 0) {
                        continue;
                    }
                    $pushTpl = "有人@你，你在大盘猜涨跌活动中剩余".$v['number']."个预言注数，今日奖励为 500 万体验金，竞猜截止 13:00，立即去参加";
                    $this->dispatch(new SendPushJob($v['user_id'], 'custom', $pushTpl));
                }
            }
            return true;
        } catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.PertenGuess.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
