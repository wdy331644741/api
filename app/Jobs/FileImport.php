<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Models\CouponCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Excel;

class FileImport extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $file;
    private $insertID;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($insertID,$file)
    {
        $this->file = trim($file);
        $this->insertID = intval($insertID);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!file_exists($this->file)){
            return "file not find!";
        }
        Excel::load($this->file,function($reader) {
            $reader = $reader->getSheet(0);
            $data = $reader->toArray();
            if(empty($data[0][0])){
                return "file empty!";
            }
            foreach($data as $item){
                //替换字符串
                $conn = trim(str_replace("rn","<br/>",$item[0]));
                //判断是否添加过
                $isExist = CouponCode::where('code',$conn)->get()->count();
                if($isExist){
                    continue;
                }else{
                    if(empty($conn)){
                        continue;
                    }
                    //添加到数据库
                    $insert['coupon_id'] = intval($this->insertID);
                    $insert['code'] = $conn;
                    CouponCode::insertGetId($insert);
                }
            }
        });
    }
}
