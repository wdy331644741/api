<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use App\Models\Cms\Content;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ContentController extends Controller
{
    //获取内容列表
    public function getList($type_id,$pagenum=5){
        if(!$type_id){
            $this->outputJson(10001,array('error_msg'=>'	Parames Error'));
        }
        $data = Content::where(['release'=>1,$type_id=>$type_id])->orderBy('id','desc')->paginate($pagenum)->get();
        $this->outputJson(0,$data);
    }

    //获取内容分类
    public function getContentType(){
        $content_type = config('activity.content_type');
        $this->outputJson(0,$content_type);
    }

    //添加内容
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'type_id' => 'required|alpha_num',
            'cover' => 'required_if:type_id:1',
            'title' => 'required',
            'contents' => 'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $content = new Content();
        $content->type_id = $request->type_id;
        $content->cover = $request->cover;
        $content->title = $request->title;
        $content->contents = $request->contents;
        $content->release = 0;
        $res = $content->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$content->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除指定内容
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num|cms_contents,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $res = Content::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //修改内容
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|alpha_num|cms_contents,id',
            'type_id' => 'required|alpha_num',
            'cover' => 'required_if:type_id:1',
            'title' => 'required',
            'contents' => 'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $res = Activity::where('id',$request->id)->update([
            'type_id'=>$request->type_id,
            'cover'=>$request->cover,
            'title'=>$request->title,
            'contents'=>$request->contents,
        ]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

}
