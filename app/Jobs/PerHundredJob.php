<?php

namespace App\Jobs;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PerHundredJob extends Job implements ShouldQueue
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
        HdPerHundredConfig::where('id',$this->insertID)->update(["insert_status"=>1]);
        $rand = range(1,$this->number-2);
        shuffle($rand);
        $insertArr = [];
        $count = HdPerbai::where("period",$this->insertID)->count();
        if($count > 0){
            return '已生成';
        }
        foreach($rand as $key => $item){
            if($key == 0){//第一个数放到最前
                $insertArr[] = array('period'=>$this->insertID,'draw_number'=>0);
            }
            $insertArr[] = array('period'=>$this->insertID,'draw_number'=>$item);
            if($key == $this->number-3){//最后一个数放到最后
                $insertArr[] = array('period'=>$this->insertID,'draw_number'=>$this->number-1);
            }
            if(($key+1)%100 == 0){
                //执行insert
                HdPerbai::insert($insertArr);
                $insertArr = [];
            }
        }
        //执行insert不够100条的
        HdPerbai::insert($insertArr);
        HdPerHundredConfig::where('id',$this->insertID)->update(["insert_status"=>2]);
    }

}
