<?php

namespace App\Jobs;
use App\Service\Attributes;
use App\Service\CarnivalRedMoneyService;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\ActivityVote;
use App\Models\Activity;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use App\Service\Func;



class HonorWorkAwardJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $userId;
    private $welfare_alais_act;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$alais)
    {
        $this->userId = $userId;
        $this->welfare_alais_act = $alais;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $_data = [];
        $res = SendAward::ActiveSendAward($this->userId,$this->welfare_alais_act);
        if(isset($res[0]) && !empty($res[0]) && $res[0]['status']){
            //获取用户手机号
            $phone = protectPhone(Func::getUserPhone($this->userId) );
            array_push($_data,['username'=> $phone,'award'=>$res[0]['award_name']]);
            $str = "{$phone},{$res[0]['award_name']}";
            //放入redis 列表
            Redis::LPUSH('honor_work_welfare',$str);
        }
        dd($_data);
    }


}
