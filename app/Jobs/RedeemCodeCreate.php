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
        for($i=0;$i<$this->number;$i++){
            $code['rel_id'] = $this->insertID;
            $code['code'] = $this->initCode($this->insertID, $i);
            $code['is_use'] = 1;
            $code['created_at'] = date("Y-m-d H:i:s");
            RedeemCode::insertGetId($code);
        }
        RedeemAward::where($where)->update(array('status'=>2));
    }
    
    private function initCode($typeId, $id)
    {
        $arr = array('n', 'w', 't', 'b', 's', '9', '8', 'g', '6', 'd', 'k', 'm', '2', '5', 'p', 'q', 'y', 'r', 'u', 'e', '4', '7', 'c', 'j', 'z', 'f', 'h', 'y', 'a', '3', 'x');
        $length = count($arr);
        $codeArr = array();
        for ($i = 0, $int = $typeId; $i < 2; $i++) {
            $index = $int % $length;
            $codeArr[] = $arr[$index];
            $int = $int / $length;
        }
        for ($i = 0, $int = $id; $i < 4; $i++) {
            $index = $int % $length;
            $codeArr[] = $arr[$index];
            $int = $int / $length;
        }

        for ($i = 0; $i < 4; $i++) {
            $index = rand(0, $length - 1);
            $codeArr[] = $arr[$index];
        }
        return strtoupper(join($codeArr, ''));
    }

}
