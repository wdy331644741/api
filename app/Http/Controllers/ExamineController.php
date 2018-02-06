<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Examine;
use Validator;

class ExamineController extends Controller
{
    function getConfigList(Request $request){
        $type = isset($request->type) ? $request->type : 1;
        $data = LifePrivilegeConfig::where("type",$type)
            ->orderBy('status', 'desc')
            ->orderBy('operator_type', 'asc')
            ->orderBy('price', 'asc')
            ->paginate(10);
        return $this->outputJson(0,$data);
    }
    public function postAdd(Request $request) {
        $validator = Validator::make($request->all(), [
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
        $data['versions'] = $request['position'];
        //现公司名称显示
        $data['company_name'] = $request['name'];
        //信息披露是否可点击0否，1是
        $data['disclosure_click'] = trim($request['img_path']);
        //底部信息区是否可点击0否，1是
        $data['bottom_click'] = trim($request['url']);
        //新手指引图标是否可点0否，1是
        $data['novice_click'] = trim($request['short_des']);
        //首页上线活动图
        $data['home_banner'] = $request['short_desc'];
        //发现页上线活动图
        $data['discover_banner'] = $request['type'];
        //状态0禁用1启用
        $data['can_use'] = 0;
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        if(isset($request->id) && $request->id > 0){
            $res = Examine::where('id',$request->id)->update($data);
            return $this->outputJson(0, $res);
        }
        //判断已经添加多少条
        $count = Examine::count();
        if($count >= 1){
            return $this->outputJson();
        }
        $id = Examine::insertGetId($data);
        return $this->outputJson(0, $id);
    }

    public function getUpdateStatus(Request $request) {
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
