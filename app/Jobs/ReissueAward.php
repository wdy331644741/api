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
        //获取出发送失败的奖品列表
        $unSendList = SendRewardLog::where('status',0)->get()->toArray();
        //循环发奖
        $i = 0;
        foreach($unSendList as $item){
            if(empty($item['award_id']) || empty($item['award_type']) || empty($item['user_id']) || empty($item['id']) || empty($item['activity_id'])){
                continue;
            }
            $activityName = Activity::where('id',$item['activity_id'])->select("name")->get()->toArray();
            $activityName = isset($activityName[0]['name']) ? $activityName[0]['name'] : '';
            if(empty($activityName)){
                continue;
            }
            $i++;
            if($i >= 100){
                return '完成';
            }
            SendAward::sendDataRole($item['user_id'], $item['award_type'], $item['award_id'], $item['activity_id'], $activityName,0,$item['id']);
        }
        return true;
    }
}
