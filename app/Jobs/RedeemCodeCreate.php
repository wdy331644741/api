<?php

namespace App\Jobs;
use App\Models\RedeemCode;
use App\Models\RedeemAward;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RedeemCodeCreate extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $number;
    private $insertID;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($insertID,$number)
    {
        $this->number = $number;
        $this->insertID = $insertID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $where['id'] = $this->insertID;
        RedeemAward::where($where)->update(array('status'=>1));
        //根据数量生成兑换码后添加
        $pc = mt_rand(1000,9999);//生成批次
        for($i=1;$i<=$this->number;$i++){
            $code['rel_id'] = $this->insertID;
            $code['code'] = $pc."-".mt_rand(1000,9999)."-".mt_rand(1000,9999);
            $code['is_use'] = 1;
            $code['created_at'] = date("Y-m-d H:i:s");
            RedeemCode::insertGetId($code);
        }
        RedeemAward::where($where)->update(array('status'=>2));
    }
}
