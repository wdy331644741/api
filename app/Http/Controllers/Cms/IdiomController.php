<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Models\Cms\Idiom;

class IdiomController extends Controller
{
    //添加语句
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'contents' => 'required',
            'start_at' =>'required|date',
            'end_at'=>'required|date',
            'priority'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $idiom = new Idiom();
        $idiom->title = $request->title;
        $idiom->contents = $request->contents;
        $idiom->start_at = $request->start_at;
        $idiom->end_at = $request->end_at;
        $idiom->priority = $request->priority;
        $res = $idiom->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$idiom->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改语句
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_idioms,id',
            'title' => 'required',
            'contents' => 'required',
            'start_at' =>'required|date',
            'end_at'=>'required|date',
            'priority'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putdata = array(
            'title'=>$request->title,
            'contents'=>$request->contents,
            'start_at'=>$request->start_at,
            'end_at'=>$request->end_at,
            'priority'=> $request->priority
        );
        $res = Idiom::where('id',$request->id)->update($putdata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除语句
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_idioms,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Idiom::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //语句列表
    public function getList($pagenum = 30){
        $data = Idiom::orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //语句详情
    public function getDetail($id){
        $data = Idiom::find($id);
        return $this->outputJson(0,$data);
    }
}
