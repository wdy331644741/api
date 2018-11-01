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
        $res = [];
        $amount = $this->data->champion_status == 0 ? 10000 : 50000;
        foreach($openName as $value){
            $res[] = Hockey::openGuess($value,$amount);
        }
        //修改状态为成功
        $this->data->open_status = 2;//状态0未开奖1已开奖2已发送开奖结果
        $this->data->remark = json_encode($res);
        $this->data->save();
        return true;
    }
}
