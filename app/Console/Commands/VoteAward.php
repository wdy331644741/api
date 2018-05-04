<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityVote;
use Illuminate\Support\Facades\Redis;
use App\Service\ActivityService;
use App\Models\Activity;
use App\Exceptions\OmgException;
use App\Service\SendAward;

class VoteAward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'VoteAward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    //快乐大本营
    private static $planAAward = [
        "id" => 0,
        "name" => "2%加息券",
        "rate_increases" => 0.02,
        "rate_increases_type" => 1,
        "rate_increases_start" => null,
        "rate_increases_end" => null,
        "effective_time_type" => 1,
        "effective_time_day" => 10,
        "effective_time_start" => null,
        "effective_time_end" => null,
        "investment_threshold" => 10000,
        "project_duration_type" => 2,
        "project_type" => 0,
        "product_id" => "",
        "platform_type" => 0,
        "limit_desc" => "10000元起投，限6月标",
        "created_at" => "",
        "updated_at" => "",
        "rate_increases_time" => 0,
        "project_duration_time" => 6,
        "message" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "mail" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "source_id" => "",
        "source_name" => "",
        "trigger" => 4,
        "user_id" => "",
    ];
    
    //极限挑战
    private static $planBAward = [
        "id" => 0,
        "name" => "1.2%加息券",
        "rate_increases" => 0.012,
        "rate_increases_type" => 1,
        "rate_increases_start" => null,
        "rate_increases_end" => null,
        "effective_time_type" => 1,
        "effective_time_day" => 10,
        "effective_time_start" => null,
        "effective_time_end" => null,
        "investment_threshold" => 10000,
        "project_duration_type" => 2,
        "project_type" => 0,
        "product_id" => "",
        "platform_type" => 0,
        "limit_desc" => "10000元起投，限6月标",
        "created_at" => "",
        "updated_at" => "",
        "rate_increases_time" => 0,
        "project_duration_time" => 6,
        "message" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "mail" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "source_id" => "",
        "source_name" => "",
        "trigger" => 4,
        "user_id" => "",
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $this->comment(PHP_EOL.Inspiring::quote().PHP_EOL);
        $activityName = 'vote_time';
        // 活动是否存在
        // if(!ActivityService::isExistByAlias($activityName)) {
        //     throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        // }
        $activityTime = ActivityService::GetActivityedInfoByAlias($activityName);
        if(date('Y-m-d H:i:s') < $activityTime['end_at']){
            return 0;
        }
        // $planA = Redis::zCard('planA_list');
        // $planB = Redis::zCard('planB_list');

        // if(!$planA){
        //     $planA = ActivityVote::where(['vote'=> 'planA'])->count();
        // }
        // if(!$planB){
        //     $planB = ActivityVote::where(['vote'=> 'planB'])->count();
        // }
        //获取两个平台的播放量
        //固定死格式
        $moveData = explode(',', $activityTime['des']);
        $mangguoTV = explode(':', $moveData[0]);//芒果TV快乐大本营 A在前
        $kuaileTV = explode(':', $moveData[1]);//优酷TV 极限挑战 B
        if(mb_substr($kuaileTV[1], -1 ,1 ,"utf-8") == '万'){
            $planBview = floatval($kuaileTV[1])*10000;
        }else if (mb_substr($kuaileTV[1], -1 ,1,"utf-8") == '亿'){
            $planBview = floatval($kuaileTV[1])*100000000;
        }else{
            $planBview = (int)$kuaileTV[1];
        }
        
        if(mb_substr($mangguoTV[1], -1 ,1 ,"utf-8") == '万'){
            $planAview = floatval($mangguoTV[1])*10000;
        }else if (mb_substr($mangguoTV[1]), -1 ,1,"utf-8") == '亿'){
            $planAview = floatval($mangguoTV[1])*100000000;
        }else{
            $planAview = (int)$mangguoTV[1];
        }
        

        $victoryOptioin = ($planAview > $planBview )?'planA':'planB';
        // $lostOptioin = ($planA>$planB)?'planB':'planA';// 败的不发奖
        $victorylist = Redis::zRange($victoryOptioin."_list" , 0 ,-1);
        // $lostlist = Redis::zRange($lostOptioin."_list" , 0 ,-1);
        foreach ($victorylist as $v) {
            $this->sendAward($v,$activityTime,$victoryOptioin);
        }
        // foreach ($lostlist as $v) {
        //     $this->sendAward($v,$activityTime,'lost');
        // }
        // dd($list);
        // dd($activityTime['end_at']);
    }

    private function sendAward($userId ,$activity,$type){
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 1, json_encode(array('err_msg' => 'pass frequency')));
        }
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');
        //直抵红包相关参数
        if($type == 'planA'){
            self::$planAAward['source_id'] = $activity['id'];
            self::$planAAward['source_name'] = $activity['name'];
            self::$planAAward['user_id'] = $userId;
            $result = SendAward::increases(self::$planAAward);
        }else{
            self::$planBAward['source_id'] = $activity['id'];
            self::$planBAward['source_name'] = $activity['name'];
            self::$planBAward['user_id'] = $userId;
            $result = SendAward::increases(self::$planBAward);
        }

        //添加活动参与记录
        if($result['status']){
            SendAward::addJoins($userId,$activity,3);
            ActivityVote::where(['user_id' => $userId])->update(['status' => 1 ,'remark'=> json_encode($result)]);

            
        }
    }


}
