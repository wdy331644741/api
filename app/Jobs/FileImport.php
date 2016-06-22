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
            //获取出code和isuse的key
            $codeKey = '';
            $isUseKey = '';
            foreach($data[0] as $k=>$v){
                if($v == "code"){
                    $codeKey = $k;
                }
                if($v == "is_use"){
                    $isUseKey = $k;
                }
            }
            //如果没有code和isusekey就不插入
            if(empty($codeKey) || empty($isUseKey)){
                return ;
            }
            foreach($data as $key => $item){
                //第一行不插入
                if($key === 0){
                    continue;
                }
                //如果大于等于1就不插入
                if($item[$isUseKey] >= 1){
                    continue;
                }
                //替换字符串
                $conn = trim(str_replace("rn","<br/>",$item[$codeKey]));
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
