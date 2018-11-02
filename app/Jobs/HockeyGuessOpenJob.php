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
        $openName = explode(',',$this->data->draw_info);
        if(empty($openName)){
            return false;
        }
        if($this->data->open_status == 3){
            return false;
        }
        $res = [];
        if($this->data->champion_status == 1){
            if(count($openName) == 1){
                $amount = 50000;
            }
            if(count($openName) == 2){
                $amount = 25000;
            }
        }
        if($this->data->champion_status == 0){
            $amount = 10000;
        }
        foreach($openName as $value){
            $res[] = Hockey::openGuess($value,$amount);
        }
        foreach($res as $item){
            if($item == false){
                $this->data->open_status = 4;//状态0未开奖，1已公布结果，2开奖中，3已发送奖励,4已发送有未猜中
                $this->data->remark = json_encode($res);
                $this->data->save();
                return false;
                break;
            }
        }
        //修改状态为成功
        $this->data->open_status = 3;//状态0未开奖，1已公布结果，2开奖中，3已发送奖励,4发送失败
        $this->data->remark = json_encode($res);
        $this->data->save();
        return true;
    }
}
