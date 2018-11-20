<?php

namespace App\Http\Controllers;

use App\Models\InExchangeLog;
use Illuminate\Http\Request;
use App\Http\Traits\BasicDatatables;
use App\Service\Func;
use Validator;
use DB;
use Excel;
use Response;

class InExchangeLogController extends Controller
{

    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id','realname','pname','pid','type_id','track_status','number','phone','address','status','track_num','track_name','created_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:in_exchange_logs,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:in_exchange_logs,id',
    ];

    function __construct() {
        $this->model = new InExchangeLog();
    }

    //----------------app 3.7.1 积分商城-----------------------//

    //实物商品兑换列表
    public function getList(Request $request){
        $res = Func::freeSearch($request,new InExchangeLog(),$this->fileds,['prizetypes','prizes']);
        return response()->json(array('error_code'=> 0, 'data'=>$res));
    }

    //导出结果
    public function postExport(Request $request){
        $res = Func::freeSearch($request,new InExchangeLog(),$this->fileds,['prizetypes','prizes']);
        $list = $res['data'];

        foreach($list as $key => $item){
            if($key == 0){
                $cellData[$key] = array('id','user_id','realname','pname','type_id','number','phone','address','track_status','track_name','track_num','created_at');
            }
            $cellData[$key+1] = array($item['id'],$item['user_id'],$item['realname'],$item['pname'],$item['type_id'],$item['number'],$item['phone'],$item['address'],$item['track_status'],$item['track_name'],$item['track_num'],$item['created_at']);
        }
        $fileName = date("YmdHis").mt_rand(1000,9999);
        $typeName = "xls";
        Excel::create($fileName,function($excel) use ($cellData){
            $excel->sheet('RecordOfExchangeList', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->store($typeName);
        $appUrl = env('APP_URL');
        $downUrl = $appUrl."/exchange/download/".$fileName.".".$typeName;
        if($appUrl != "http://api-omg.wanglibao.com"){
            $downUrl = $appUrl."/yunying/exchange/download/".$fileName.".".$typeName;
        }
        return response()->json(array('error_code'=> 0, 'data'=>$downUrl));
    }

    //导入
    public function postImport(Request $request){
        $path = base_path().'/storage/imports/';
        if ($request->hasFile('file')) {
            //验证文件上传中是否出错
            if ($request->file('file')->isValid()){
                $mimeTye = $request->file('file')->getClientOriginalExtension();
                if($mimeTye == 'xlsx' || $mimeTye == 'xls'){
                    $fileName = date('YmdHis').mt_rand(1000,9999).'.'.$mimeTye;
                    //保存文件到路径
                    $request->file('file')->move($path,$fileName);
                    $file = $path.$fileName;
                }else{
                    return array('code'=>404,'params'=>'file','error_msg'=>'文件格式错误');
                }
            }else{
                return array('code'=>404,'params'=>'file','error_msg'=>'文件错误');
            }
        }else{
            return array('code'=>404,'params'=>'file','error_msg'=>'文件不能为空');
        }

        if(!file_exists($file)){
            return array('code'=>404,'params'=>'file','error_msg'=>'文件错误');
        }
        Excel::load($file,function($reader) {
            $reader = $reader->getSheet(0);
            $data = $reader->toArray();
            foreach($data as $key => $item){
                //第一行不插入
                if($key === 0 || $item[0] == null){
                    continue;
                }
                $exObj = InExchangeLog::where('id',intval($item[0]))->first();
                $exObj->phone = $item[6];
                $exObj->address = $item[7];
                $exObj->track_status  = intval($item[8]);
                $exObj->track_name  = $item[9];
                $exObj->track_num  = $item[10];
                $exObj->save();
            }
        });
        return $this->outputJson(0);
    }

    public function getDownload($fileName){
        return Response::download(base_path()."/storage/exports/".$fileName);
    }

    //添加积分商城奖品
    public function postOperation(Request $request)
    {
        $prize_id = intval($request->id);
        $validator = Validator::make($request->all(), [
            'type_id' => 'required|exists:in_prizetypes,id',
            'name' => 'required|string|min:1',
            'price' => 'required|integer|min:1',
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required_unless:award_type,5|integer|min:1',
            'stock' => 'required|string|min:1',
            'list_img' => 'required',
            'detail_img' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $data['type_id'] = $request->type_id;
        $data['name'] = $request->name;
        $data['price'] = $request->price;
        $data['award_type'] = $request->award_type;
        $data['award_id'] = $request->awaed_type == 5 ? 0 : $request->award_id;
        $data['stock'] = $request->stock;
        $data['list_img'] = $request->list_img;
        $data['detail_img'] = $request->detail_img;
        $data['des_img'] = !empty($request->des_img) ? $request->des_img : null;
        $data['des'] = !empty($request->des) ? $request->des : null;
        $data['ex_note'] = !empty($request->ex_note) ? $request->ex_note : null;
        $data['disclaimer'] = !empty($request->disclaimer) ? $request->disclaimer : null;
        //判断是添加还是修改
        if($prize_id != 0){
            //查询该信息是否存在
            $where = array();
            $where['id'] = $prize_id;
            $isExist = InPrize::where($where)->count();
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = InPrize::where('id',$prize_id)->update($data);
                if($status){
                    return $this->outputJson(0, array('error_msg'=>'修改成功'));
                }else{
                    return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
                }
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
            }
        }else{
            $data['created_at'] = date("Y-m-d H:i:s");
            $id = InPrize::insertGetId($data);
            return $this->outputJson(0, array('insert_id'=>$id));
        }
    }

    //批量删除
    public function postBatchDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $idArr = array_filter(explode('-',$request->id));

        $res = InPrize::whereIn('id',$idArr)->delete();
        if(empty($res)){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
