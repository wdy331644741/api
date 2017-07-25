<?php

namespace App\Http\Controllers;

use App\Models\LifePrivilegeConfig;

use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;

class FeeAndFlowController extends Controller
{
    function getTypeList(Request $request){
        $type = isset($request->type) ? $request->type : 1;
        $data = LifePrivilegeConfig::where("type",$type)->order('')->get();
        return $this->outputJson(0,$data);
    }

    function postAddType(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'type' => 'required|numeric|between:0.1,100',
            'operator_type' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0.1',
        ]);
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
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
            return $this->outputJson(10001,array('error_msg'=>'参数有误'));
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

}
