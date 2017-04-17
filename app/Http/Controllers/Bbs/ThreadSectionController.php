<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Models\Bbs\ThreadSection;

class ThreadSectionController extends Controller
{
    //添加版块
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'sort'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $section = new ThreadSection();
        $section->name = $request->name;
        $section->isuse = $request->isuse ? $request->isuse : 0;
        $section->sort = isset($request->sort) ? $request->sort : 0;
        $section->description = isset($request->description) ? $request->description : NULL;
        $section->save();
        if($section->id){
            return $this->outputJson(0,array('insert_id'=>$section->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除版块
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = ThreadSection::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //修改版块
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
            'name'=>'required',
            'sort'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'name'=>$request->name,
            'isuse'=> $request->isuse ? $request->isuse : 0,
            'sort'=>$request->sort,
            'description'=>isset($request->description) ? $request->description : NULL
        ];
        $res = ThreadSection::find($request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //版块列表
    public function getList(){
        $data = ThreadSection::where('isuse',1)->orderBy('sort asc')->get();
        return $this->outputJson(0,$data);
    }

    //开启版块

    //关闭版块
}
