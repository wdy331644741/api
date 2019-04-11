<?php

namespace App\Console\Commands;

use App\Models\HdPerbai;
use App\Service\PerBaiService;
use Illuminate\Console\Command;
use App\Jobs\SendPushJob;
use Illuminate\Foundation\Bus\DispatchesJobs;

class PertenRemind extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PertenRemind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日14:00全量PUSH提醒获得抽奖号码用户';

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
            $count = $data = HdPerbai::where(['period'=>$activity['id']])->where('status', '>', 0)->distinct('user_id')->count('user_id');
            $perPage = 100;
            $num = ceil($count / $perPage);
            for ($i=0; $i<$num; $i++) {
                $offset = $i * $perPage;
                $data = HdPerbai::selectRaw('user_id, count(*) c')->where(['period'=>$activity['id']])->where('status', '>', 0)->groupBy('user_id')->offset($offset)->limit($perPage)->get()->toArray();
                foreach ($data as $v) {
                    $pushTpl = "有人@你，已获得的抽奖号码{".$v['c']."}个，每日万元股指现金奖励和 100 元京东卡可领取，立即去查看！";
                    $this->dispatch(new SendPushJob($v['user_id'], 'custom', $pushTpl));
                }
            }
            return true;
        } catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.PertenRemind.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
