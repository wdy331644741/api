<?php

namespace App\Console\Commands;

use App\Jobs\SendPushJob;
use App\Models\HdPerHundredConfig;
use App\Service\PerBaiService;
use App\Service\SendMessage;
use Illuminate\Console\Command;
use Config;
use Illuminate\Foundation\Bus\DispatchesJobs;
class SendPush extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SendPush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push';

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
            $beforeTen = strtotime('-10 minute', strtotime($activity->start_time));
            $endTime = strtotime($activity->start_time);
            $time = time();
            if ($time > $beforeTen && $time < $endTime) {
                $node = PerBaiService::$nodeType . $activity['id'];
                $where = ['status'=>0, 'type'=> $node];
                $count = \App\Models\SendPush::where($where)->count();
                if (!$count) {
                    throw new \Exception('没有要提醒的用户');
                }
                $perPage = 100;
                $num = ceil($count / $perPage);
                $id = \App\Models\SendPush::where($where)->value('id');
                for ($i=0; $i<$num; $i++) {
                    $data = \App\Models\SendPush::select('id','user_id')->where('id', '>=', $id)->where($where)->limit($perPage)->get()->toArray();
                    $userIds = [];
                    foreach ($data as $v) {
                        $userIds[] = $v['user_id'];
                    }
                    if ($userIds) {
                        $last = array_pop($data);
                        $id = $last['id'];
                        \App\Models\SendPush::where($where)->where('id', '<=', $id)->where($where)->update(['status'=>1]);
                        $this->dispatch(new SendPushJob($userIds, 'custom', '您的 Apple Watch 4 已安排，10:00即将开抢，提前登录准备好姿势，等你来抢！'));
                    } else {
                        return fasle;
                    }
                }
            }
            return false;
        }catch (\Exception $e) {
            $log = '[' . date('Y-m-d H:i:s') . '] crontab error:' . $e->getMessage() . "\r\n";
            $filepath = storage_path('logs' . DIRECTORY_SEPARATOR . 'console.SendPush.log');
            file_put_contents($filepath, $log, FILE_APPEND);
            return false;
        }
    }
}
