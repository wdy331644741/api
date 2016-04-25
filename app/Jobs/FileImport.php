<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Models\CouponCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        //打开文件
        if(file_exists($this->file)){
            $handle = fopen($this->file, 'r');
            while(!feof($handle)){
                $conn = fgets($handle, 1024);
                //替换字符串
                $conn = trim(str_replace("rn","<br/>",$conn));
                //判断是否添加过
                $isExist = CouponCode::where('code',$conn)->get()->count();
                if($isExist){
                    continue;
                }else{
                    //添加到数据库
                    $data['coupon_id'] = intval($this->insertID);
                    $data['code'] = $conn;
                    CouponCode::insertGetId($data);
                }
            }
            fclose($handle);
        }else{
            echo "file not find!";
        }
    }
}
