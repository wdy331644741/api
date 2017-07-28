<?php

namespace App\Http\Controllers;

use App\Models\LifePrivilege;
use App\Models\LifePrivilegeConfig;

use App\Service\FeeAndFlowBasic;
use App\Service\Func;
use Illuminate\Http\Request;
use App\Http\Requests;
use Validator,Excel,Response;

class FeeAndFlowController extends Controller
{
    function getTypeList(Request $request){
        $type = isset($request->type) ? $request->type : 1;
        $data = LifePrivilegeConfig::where("type",$type)
            ->orderBy('status', 'desc')
            ->orderBy('operator_type', 'asc')
            ->orderBy('price', 'asc')
            ->paginate(10);
        return $this->outputJson(0,$data);
    }

    function postAddType(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'type' => 'required|numeric|between:0.1,100',
            'operator_type' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0.1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }

        $addData = [];
        $addData['name'] = $request->name;
        $addData['type'] = $request->type;
        $addData['operator_type'] = $request->operator_type;
        $addData['price'] = $request->price;
        $addData['created_at'] = date("Y-m-d H:i:s");
        $addData['updated_at'] = date("Y-m-d H:i:s");
        if(isset($request->id) && $request->id > 0){
            $res = LifePrivilegeConfig::where('id',$request->id)->update($addData);
            return $this->outputJson(0, $res);
        }
        $insertId = LifePrivilegeConfig::insertGetId($addData);
        return $this->outputJson(0, $insertId);
    }

    function postUpdateType(Request $request){
        $id = isset($request->id) && $request->id > 0 ? $request->id : 0;
        $type = isset($request->type) && $request->type > 0 ? $request->type : 0;
        if($id <= 0 || $type <= 0){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'参数有误'));
        }
        $data = LifePrivilegeConfig::where("id",$id)->first();
        if(isset($data['id']) && $data['id'] > 0){
            if($type == 1){
                $status = 1;
            }else{
                $status = 0;
            }
            $res = LifePrivilegeConfig::where("id",$id)->update(['status'=>$status]);
            if($res){
                return $this->outputJson(0);
            }
        }
        return $this->outputJson(10002,array('error_msg'=>"Database Error"));
    }

    /**
     * 获取订单列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function getOrderList(Request $request){
        $params = [];
        if(isset($request->user_id) && !empty($request->user_id)){
            $params['user_id'] = $request->user_id;
        }
        if(isset($request->order_id) && !empty($request->order_id)){
            $params['order_id'] = $request->order_id;
        }
        if(isset($request->phone) && !empty($request->phone)){
            $params['phone'] = $request->phone;
        }
        if(isset($request->debit_status) && $request->debit_status >= 0 ){
            $params['debit_status'] = $request->debit_status;
        }
        if(isset($request->order_status) && $request->order_status >= 0 ){
            $params['order_status'] = $request->order_status;
        }
        if(isset($request->repair_status) && $request->repair_status >= 0 ){
            $params['repair_status'] = $request->repair_status;
        }
        if(isset($request->type) && $request->type >= 1 ){
            $params['type'] = $request->type;
        }
        $data = LifePrivilege::where($params)->OrderBy('id','desc')->paginate(20);
        if(isset($request->start_time) && isset($request->end_time) && !empty($request->start_time) && !empty($request->end_time)){
            $data = LifePrivilege::where($params)
                ->where('updated_at','>=',$request->start_time)
                ->where('updated_at','<=',$request->end_time)
                ->OrderBy('id','desc')->paginate(20);
        }
        if(!empty($request->start_time)){
            $data = LifePrivilege::where($params)->where('updated_at','>=',$request->start_time)->OrderBy('id','desc')->paginate(20);
        }
        if(!empty($request->end_time)){
            $data = LifePrivilege::where($params)->where('updated_at','<=',$request->end_time)->OrderBy('id','desc')->paginate(20);
        }
        return $this->outputJson(0,$data);
    }

    /**
     * 修改订单状态
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postOrderStatusUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'debit_status' => 'required|integer|min:0',
            'order_status' => 'required|integer|min:0',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }

        $upData = [];
        $upData['debit_status'] = $request->debit_status;
        $upData['order_status'] = $request->order_status;
        $upData['updated_at'] = date("Y-m-d H:i:s");
        if(isset($request->id) && $request->id > 0){
            $res = LifePrivilege::where('id',$request->id)->update($upData);
            return $this->outputJson(0, $res);
        }
        return $this->outputJson(10002,array('error_msg'=>"Database Error"));
    }

    /**
     * 补单
     * @param Request $request
     * @return bool
     */
    function postOrderRepair(Request $request){
        $id = isset($request->id) && !empty($request->id) ? $request->id : 0;
        if(empty($id)){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'参数错误'));
        }
        //获取订单信息
        $orderInfo = LifePrivilege::where('id',$id)->first();
        if(
            isset($orderInfo['repair_status']) && $orderInfo['repair_status'] == 0 &&
            isset($orderInfo['debit_status']) && $orderInfo['debit_status'] == 1 &&
            isset($orderInfo['order_status']) && $orderInfo['order_status'] != 3
        ){
            //原订单设置为已补单
            LifePrivilege::where('id',$id)->update(['repair_status'=>1]);
            //补单
            $FeeAndFlowBasic = new FeeAndFlowBasic;
            $res = $FeeAndFlowBasic->CreateOrders(
                $orderInfo['user_id'],
                $orderInfo['phone'],
                $orderInfo['name'],
                $orderInfo['amount_of'],
                $orderInfo['amount'],
                $orderInfo['type'],
                $orderInfo['operator_type'],
                1,
                $orderInfo['id']);
            if(isset($res['code']) && $res['code'] == 0){
                return $this->outputJson(0,array('error_msg'=>"成功"));
            }
        }
        return $this->outputJson(10002,array('error_msg'=>"补单失败"));
    }

    /**
     * 导出结果
     */
    function getOrderExport(Request $request){
        $params = [];
        if(isset($request->user_id) && !empty($request->user_id)){
            $params['user_id'] = $request->user_id;
        }
        if(isset($request->order_id) && !empty($request->order_id)){
            $params['order_id'] = $request->order_id;
        }
        if(isset($request->phone) && !empty($request->phone)){
            $params['phone'] = $request->phone;
        }
        if(isset($request->debit_status) && $request->debit_status >= 0 ){
            $params['debit_status'] = $request->debit_status;
        }
        if(isset($request->order_status) && $request->order_status >= 0 ){
            $params['order_status'] = $request->order_status;
        }
        if(isset($request->repair_status) && $request->repair_status >= 0 ){
            $params['repair_status'] = $request->repair_status;
        }
        if(isset($request->type) && $request->type >= 1 ){
            $params['type'] = $request->type;
        }
        $data = LifePrivilege::where($params)->OrderBy('id','desc')->get()->toArray();
        if(isset($request->start_time) && isset($request->end_time) && !empty($request->start_time) && !empty($request->end_time)){
            $data = LifePrivilege::where($params)
                ->where('updated_at','>=',$request->start_time)
                ->where('updated_at','<=',$request->end_time)
                ->OrderBy('id','desc')->get()->toArray();
        }
        if(!empty($request->start_time)){
            $data = LifePrivilege::where($params)->where('updated_at','>=',$request->start_time)->OrderBy('id','desc')->get()->toArray();
        }
        if(!empty($request->end_time)){
            $data = LifePrivilege::where($params)->where('updated_at','<=',$request->end_time)->OrderBy('id','desc')->get()->toArray();
        }
        //导出excel
        $cellData = array();
        foreach($data as $key => $item){
            if($key == 0){
                $cellData[$key] = array(
                    '序号',
                    '订单ID',
                    '用户ID',
                    '商品名称',
                    '充值手机号',
                    '扣款状态',
                    '订单状态',
                    '补单状态',
                    '用户扣款金额',
                    '订单金额',
                    '补单金额',
                    '操作时间'
                );
            }
            $cellData[$key+1] = array(
                $item['id'],
                $item['order_id'],
                $item['user_id'],
                $item['name'],
                $item['phone'],
                $item['debit_status'],
                $item['order_status'],
                $item['repair_status'],
                $item['amount'],
                $item['amount_of'],
                $item['amount_of'],
                $item['updated_at']);
        }
        $fileName = date("YmdHis").mt_rand(1000,9999);
        $typeName = "xls";
        if(is_dir(storage_path('excel/exports'))){
            $fileArr = glob(storage_path('excel/exports/*.xls'));
            for($i=0; $i<count($fileArr); $i++){
                unlink($fileArr[$i]);
            }
        }
        Excel::create($fileName,function($excel) use ($cellData){
            $excel->sheet('score', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->store($typeName, storage_path('excel/exports'));
        return $this->outputJson(0,['url'=>env('FILE_HTTP_URL').$fileName.'.'.$typeName]);
    }
}
