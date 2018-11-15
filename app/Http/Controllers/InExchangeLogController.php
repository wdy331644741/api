<?php

namespace App\Http\Controllers;

use App\Models\InExchangeLog;
use Illuminate\Http\Request;
use App\Http\Traits\BasicDatatables;
use App\Service\Func;
use Validator;
use DB;

class InExchangeLogController extends Controller
{

    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','created_at'];
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
