<?php

namespace App\Console\Commands;

use App\Jobs\SendPushJob;
use App\Models\HdPerHundredConfig;
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
        //
        $activityConfig = HdPerHundredConfig::where(['status' => 1])->first();
        if ($activityConfig) {
            $beforeTen = strtotime('-15 minute', strtotime($activityConfig->start_time));
            $endTime = strtotime($activityConfig->start_time);
            $time = time();
            if ($time > $beforeTen && $time < $endTime) {
                $node = Config::get('perbai.node');
                $where = ['status'=>0, 'type'=> $node];
                $count = \App\Models\SendPush::where($where)->count();
                if (!$count) {
                    return false;
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
                        $this->dispatch(new SendPushJob($userIds, 'activity_remind'));
                    } else {
                        return fasle;
                    }
                }
            }
        }
        return false;
    }
}
