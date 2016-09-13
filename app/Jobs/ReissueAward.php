<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;
use App\Models\SendRewardLog;
use App\Models\Activity;

class ReissueAward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        //循环发奖
        for($i=0;$i<100;$i++){
            //获取出发送失败的奖品列表
            $item = SendRewardLog::where('status',0)->where('activity_id','!=','0')->where('award_id','!=','0')->first();
            if(empty($item['award_id']) || empty($item['award_type']) || empty($item['user_id']) || empty($item['id']) || empty($item['activity_id'])){
                continue;
            }
            $activityName = Activity::where('id',$item['activity_id'])->select("name")->first();
            $activityName = isset($activityName['name']) ? $activityName['name'] : '';
            if(empty($activityName)){
                continue;
            }
            SendAward::sendDataRole($item['user_id'], $item['award_type'], $item['award_id'], $item['activity_id'], $activityName,0,$item['id']);
        }
        return true;
    }
}
