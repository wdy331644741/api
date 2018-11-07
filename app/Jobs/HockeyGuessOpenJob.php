<?php

namespace App\Jobs;
use App\Service\Hockey;
use Illuminate\Queue\SerializesModels;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;


class HockeyGuessJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(empty($this->data)){
            return false;
        }
        $openName = explode(',',$this->data->draw_info);//中奖信息拆开
        if(empty($openName)){
            return false;
        }
        if($this->data->open_status >= 3){//已发送就不会发送
            return false;
        }
        $res = [];
        if($this->data->champion_status == 1){//冠军场50000
            if(count($openName) == 1){//不是平50000
                $amount = 50000;
            }
            if(count($openName) == 2){//平的话25000
                $amount = 25000;
            }
        }
        if($this->data->champion_status == 0){//普通场10000
            $amount = 10000;
        }
        foreach($openName as $value){//循环按场次发奖
            $res[] = Hockey::openGuess($value,$amount);
        }
        foreach($res as $item){//循环修改状态
            if($item == false){
                $this->data->open_status = 4;//状态0未开奖，1已公布结果，2开奖中，3已发送奖励,4已发送有未猜中
                $this->data->remark = json_encode($res);
                $this->data->save();
                return false;
                break;
            }
        }
        //修改状态为成功
        $this->data->open_status = 3;//状态0未开奖，1已公布结果，2开奖中，3已发送奖励,4已发送有未猜中
        $this->data->remark = json_encode($res);
        $this->data->save();
        return true;
    }
}
