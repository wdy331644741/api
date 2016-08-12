<?php

namespace App\Jobs;
use App\Models\RedeemCode;
use App\Models\RedeemAward;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Excel;
class RedeemExport extends Job implements ShouldQueue
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
        $where['rel_id'] = $this->id;
        $list = RedeemCode::where($where)->get()->toArray();
        $cellData = array();
        foreach($list as $key => $item){
            if($key == 0){
                $cellData[$key] = array('ID','名称','兑换码','是否可用','添加时间','修改时间');
            }
            $cellData[$key+1] = array($item['id'],$this->name,$item['code'],$item['is_use'] == 1 ? '可用' : '不可用',$item['created_at'],$item['updated_at']);
        }
        $fileName = date("YmdHis").mt_rand(1000,9999);
        $typeName = "xls";
        Excel::create($fileName,function($excel) use ($cellData){
            $excel->sheet('score', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->store($typeName);
        RedeemAward::where('id',$this->id)->update(array('file_name'=>$fileName.".".$typeName));
        //修改导出状态为导出成功
        RedeemAward::where('id',$this->id)->update(array('export_status'=>2));
    }
}
