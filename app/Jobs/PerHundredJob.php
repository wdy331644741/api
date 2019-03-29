<?php

namespace App\Jobs;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Service\PerBaiService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Cache;

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
        $rand = range(0,$this->number);
        $insertArr = [];
        $count = HdPerbai::where("period",$this->insertID)->count();
        if($count > 0){
            return '已生成';
        }
        foreach($rand as $key => $item){
            $insertArr[] = array('period'=>$this->insertID,'draw_number'=>$item);
            if(($key+1)%100 == 0){
                //执行insert
                HdPerbai::insert($insertArr);
                $insertArr = [];
            }
        }
        //执行insert不够100条的
        if ($insertArr) {
            HdPerbai::insert($insertArr);
        }
        $ret = HdPerHundredConfig::where('id',$this->insertID)->update(["insert_status"=>2]);
//        if ($ret) {
//            $key = PerBaiService::$pertenKey;
//            if (Cache::has($key)) {
//                Cache::forget($key);
//            }
//            Cache::forever($key, ($this->number + 1) );
//        }
    }

}
