<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Examine;
use Validator;

class ExamineController extends Controller
{
    function getConfigList(Request $request){
        $type = intval($request->type);
        $data = Examine::where("type",$type)->paginate(20);
        return $this->outputJson(0,$data);
    }
    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|min:1',
            'versions' => 'required|min:1|max:32',
            'company_name' => 'required|min:1|max:64',
            'disclosure_click' => 'required|integer|min:0|max:1',
            'bottom_click' => 'required|integer|min:0|max:1',
            'novice_click' => 'required|integer|min:0|max:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //版本号
        $data['app_name'] = trim($request['app_name']);
        //版本号
        $data['type'] = intval($request['type']);
        //版本号
        $data['versions'] = trim($request['versions']);
        //现公司名称显示
        $data['company_name'] = trim($request['company_name']);
        //信息披露是否可点击0否，1是
        $data['disclosure_click'] = intval($request['disclosure_click']);
        //底部信息区是否可点击0否，1是
        $data['bottom_click'] = intval($request['bottom_click']);
        //新手指引图标是否可点0否，1是
        $data['novice_click'] = intval($request['novice_click']);
        //首页上线活动图
        $data['home_banner'] = trim($request['home_banner']);
        //发现页上线活动图
        $data['discover_banner'] = trim($request['discover_banner']);
        //状态0禁用1启用
        $data['status'] = intval($request['status']);
        if(isset($request->id) && $request->id > 0){
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $res = Examine::where('id',$request->id)->update($data);
            return $this->outputJson(0, $res);
        }
        //判断已经添加多少条
        $count = Examine::where("type",1)->count();
        if($data['type'] == 1 && $count >= 1){
            return $this->outputJson(10001,array('error_msg'=>'最多添加一条'));
        }
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        $id = Examine::insertGetId($data);
        return $this->outputJson(0, $id);
    }

    public function postUpdateStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'status' => 'required|integer|min:0'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }

        $upData = [];
        $upData['status'] = $request->status;
        $upData['updated_at'] = date("Y-m-d H:i:s");
        if(isset($request->id) && $request->id > 0){
            $res = Examine::where('id',$request->id)->update($upData);
            return $this->outputJson(0, $res);
        }
        return $this->outputJson(10002,array('error_msg'=>"Database Error"));
    }
}
