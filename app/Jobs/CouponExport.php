<?php

namespace App\Jobs;
use App\Models\CouponCode;
use App\Models\Coupon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Excel;
class CouponExport extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $id;
    private $name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id,$name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //获取优惠码
        $where['coupon_id'] = $this->id;
        $list = CouponCode::where($where)->get()->toArray();
        $cellData = array();
        foreach($list as $key => $item){
            if($key == 0){
                $cellData[$key] = array('id','name','code','is_use');
            }
            $cellData[$key+1] = array($item['id'],$this->name,$item['code'],$item['is_use']);
        }
        $fileName = date("YmdHis").mt_rand(1000,9999);
        $typeName = "xls";
        Excel::create($fileName,function($excel) use ($cellData){
            $excel->sheet('score', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->store($typeName);
        Coupon::where('id',$this->id)->update(array('file'=>$fileName.".".$typeName));
        //修改导出状态为导出成功
        Coupon::where('id',$this->id)->update(array('export_status'=>2));
    }
}
