<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use Validator;
use DB;
use App\Models\Cms\Content;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ContentController extends Controller
{
    //获取内容列表
    public function getList($type_id,$pagenum=5){
        if(!$type_id){
            $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = Content::where('release',1)->where('type_id',$type_id)->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //获取内容分类
    public function getType(){
        $content_type = config('activity.content_type');
        return $this->outputJson(0,$content_type);
    }

    //添加内容
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'type_id' => 'required|alpha_num',
            'cover' => 'required_if:type_id,1',
            'title' => 'required',
            'content' => 'required',
            'source' => 'required_if:type_id,1|active_url'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $content = new Content();
        $content->type_id = $request->type_id;
        $content->cover = $request->cover;
        $content->title = $request->title;
        $content->content = $request->content;
        $content->source = $request->source;
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
            'id' => 'required|integer|exists:cms_contents,id',
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
            'id' => 'required|integer|exists:cms_contents,id',
            'type_id' => 'required|alpha_num',
            'cover' => 'required_if:type_id,1',
            'title' => 'required',
            'content' => 'required',
            'source' => 'required_if:type_id,1|active_url'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $res = Content::where('id',$request->id)->update([
            'type_id'=>$request->type_id,
            'cover'=>$request->cover,
            'title'=>$request->title,
            'content'=>$request->content,
            'source'=>$request->source,
        ]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //获取指定内容
    public function getDetail($id){
        if(!$id){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = Content::where('release',1)->find($id);
        return $this->outputJson(0,$data);
    }

}
