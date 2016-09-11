<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Admin;
use Validator;

class AdminController extends Controller
{
    //管理员列表
    public function getList(){
        $data = Admin::orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    //添加管理员
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:11',
            'level'=>'required|digits_between:0,256',
            'name' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $is_exists = Admin::where(['mobile'=>$request->mobile])->count();
        if($is_exists){
            return $this->outputJson(10001,array('error_msg'=>'用户已存在'));
        }
        $admin = new Admin();
        $admin->mobile = $request->mobile;
        $admin->level = $request->level;
        $admin->name = $request->name;
        $res = $admin->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$admin->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除管理员
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:admins,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Admin::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //修改管理员权限
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'level'=>'required|digits_between:0,256',
            'id' => 'required|exists:admins,id',
            'name' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $update = [
            'level' => $request->level,
            'name' => $request->name,
        ];
        $res = Admin::where('id',$request->id)->update($update);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }
}
