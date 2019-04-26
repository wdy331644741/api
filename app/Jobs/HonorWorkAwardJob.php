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
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\MsgPushJob;



class HonorWorkAwardJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    use DispatchesJobs;
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
        //按照活动别名发奖  福利三、四。不实时发放（只发站内信）。活动结束后 统计发放
        if($this->welfare_alais_act == 'honor_work_welfare3'){
            //发放站内信 并 存入轮播数据
            $message_str = '嘿，您在“劳动最光荣”活动中已成功兑换100元京东卡，我们将在活动结束后15个工作日以站内信的形式将京东卡卡密统一发放至您的个人账户，请注意查收！';
            //站内信
            $this->dispatch(new MsgPushJob($this->userId,$message_str,'mail'));
            //获取用户手机号
            $phone = protectPhone(Func::getUserPhone($this->userId) );
            if(strlen($phone) == 11){
                array_push($_data,['username'=> $phone,'award'=>'100元京东卡']);
                $str = "{$phone},100元京东卡";
                //放入redis 列表
                Redis::LPUSH('honor_work_welfare',$str);
            }
        }else if($this->welfare_alais_act == 'honor_work_welfare4'){
            //发放站内信 并 存入轮播数据
            $message_str = '嘿，您在“劳动最光荣”活动中已成功兑换501元现金，我们将在活动结束后7个工作日统一发放至您的账户，请注意查收！';
            //站内信
            $this->dispatch(new MsgPushJob($this->userId,$message_str,'mail'));
            //获取用户手机号
            $phone = protectPhone(Func::getUserPhone($this->userId) );
            if(strlen($phone) == 11){
                array_push($_data,['username'=> $phone,'award'=>'501元现金']);
                $str = "{$phone},501元现金";
                //放入redis 列表
                Redis::LPUSH('honor_work_welfare',$str);
            }
        }else{

            $res = SendAward::ActiveSendAward($this->userId,$this->welfare_alais_act);
            if(isset($res[0]) && !empty($res[0]) && $res[0]['status']){
                //获取用户手机号
                $phone = protectPhone(Func::getUserPhone($this->userId) );
                if(strlen($phone) == 11){
                    array_push($_data,['username'=> $phone,'award'=>$res[0]['award_name']]);
                    $str = "{$phone},{$res[0]['award_name']}";
                    //放入redis 列表
                    Redis::LPUSH('honor_work_welfare',$str);
                }

            }

        }
        dd($_data);
    }


}
