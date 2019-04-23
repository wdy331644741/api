<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\UserAttribute;
use App\Models\ActivityJoin;
use App\Service\ActivityService;
use Config,DB;

class HonorWorkUpdateJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $user_id;
    private $config = [];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->config = Config::get('honor_work');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //统计用户签到数据
        $check_in_alias = $this->config['rule']['check_in_alias'];
        //统计 邀请注册
        $check_invite = $this->config['rule']['check_invite'];
        $activityId = ActivityService::GetActivityInfoByAlias($check_in_alias);
        $activityId_invite = ActivityService::GetActivityInfoByAlias($check_invite);

        $activityId = isset($activityId['id']) ? $activityId['id'] : 0;
        $activityId_invite = isset($activityId_invite['id']) ? $activityId_invite['id'] : 0;
        if($activityId <= 0 || $activityId_invite <= 0) {
            return 0;
        }
        //签到数
        $check_in = ActivityJoin::select('created_at', 'user_id')
            ->where('user_id', $this->user_id)
            ->where('activity_id', $activityId)
            ->where('status',3)
            ->orderBy('id', 'desc')->count();
        //邀请数
        $check_is_invite = ActivityJoin::select('created_at', 'user_id')
            ->where('user_id', $this->user_id)
            ->where('activity_id', $activityId_invite)
            ->where('status',3)
            ->orderBy('id', 'desc')->count();

        DB::beginTransaction();
        $res = UserAttribute::where(['key'=> $this->config['key'],'user_id'=>$this->user_id])
            ->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);
            if($check_in >= 1){
                $userAttrData['badge']['xianfeng'] = 1;//发放先锋勋章
            }
            if($check_in >= 3){
                $userAttrData['badge']['qinlao'] = 1;//发房 勤劳勋章
            }
            if($check_is_invite >= 1){
                $userAttrData['badge']['tashi'] = 1;//发房 踏实勋章
            }
            $updatestatus = UserAttribute::where(['key'=>$this->config['key'],'user_id'=>$this->user_id])
                ->update(['text'=>json_encode($userAttrData)]);
            if(isset($updatestatus)){
                DB::commit();
                return 1;
            }
        }

        DB::rollBack();
        return 0;

    }

}
