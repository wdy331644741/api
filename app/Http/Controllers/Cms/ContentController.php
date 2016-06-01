<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use Validator;
use DB;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ContentController extends Controller
{
    //获取内容列表(通过type_id获取)
    public function getList($type_id,$pagenum=5){
        if(!$type_id){
            $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = Content::where('release',1)->where('type_id',$type_id)->orderBy('sort','ASC')->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //获取内容列表(通过alias_name获取)
    public function getListStr($name,$pagenum=5){
        if(!$name){
            $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $type_id = ContentType::where('alias_name',$name)->value('type_id');
        $data = Content::where('release',1)->where('type_id',$type_id)->orderBy('sort','ASC')->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //添加内容
    public function postAdd(Request $request){

        $validator = Validator::make($request->all(), [
            'type_id' => 'required|integer|exists:cms_content_types,id',
            'title' => 'required',
            'content' => 'required',
            'source' => 'url',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $content = new Content();
        $content->type_id = $request->type_id;
        $content->title = $request->title;
        $content->content = $request->content;
        if(isset($request->release)){
            $content->release = $request->release;
        }
        if(isset($request->cover)){
            $content->cover = $request->cover;
        }
        if(isset($request->source)){
            $content->source = $request->source;
        }
        if(isset($request->sort)){
            $content->sort = $request->sort;
        }
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
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
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
            'type_id' => 'required|alpha_num|exists:cms_content_types,id',
            'title' => 'required',
            'content' => 'required',
            'source' => 'url'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putdata = array(
            'type_id'=>$request->type_id,
            'title'=>$request->title,
            'content'=>$request->content
        );

        if(isset($request->cover)){
            $putdata['cover'] = $request->cover;
        }
        if(isset($request->source)){
            $putdata['source'] = $request->source;
        }
        if(isset($request->sort)){
            $putdata['sort'] = $request->sort;
        }
        $res = Content::where('id',$request->id)->update($putdata);
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

    //新建分类
    public function postTypeAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|numeric',
            'name' => 'required',
            'alias_name' => 'required|alpha|unique:cms_content_types,alias_name',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $contentType = new ContentType();
        $contentType->parent_id = $request->parent_id;
        $contentType->name = $request->name;
        $contentType->alias_name = $request->alias_name;
        if(isset($request->sort)){
            $contentType->sort = $request->sort;
        }
        $res = $contentType->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$contentType->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除分类，软删除
    public function postTypeDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:cms_content_types,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $res = ContentType::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改分类
    public function postTypePut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:cms_content_types,id',
            'parent_id' => 'required|numeric',
            'name' => 'required',
            'alias_name' => 'required|alpha|unique:cms_content_types,alias_name',
        ]);

        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putdata = array(
            'parent_id'=>$request->parent_id,
            'name'=>$request->name,
            'alias_name'=>$request->alias_name,
        );
        if(isset($request->sort)){
            $putdata['sort'] = $request->sort;
        }
        $res = ContentType::where('id',$request->id)->update($putdata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //查询单个分类（通过id）
    public function getTypeInfo($id){
        if(!$id){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = ContentType::find($id);
        return $this->outputJson(0,$data);
    }
    //查询单个分类（通过alias_name）
    public function getTypeInfoStr($name){
        if(!$name){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = ContentType::where('alias_name',$name)->get();
        return $this->outputJson(0,$data);
    }

    //查询分类列表（通过id）
    public function getTypeList($id){
        if($id !=0 && !$id){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = ContentType::where('parent_id',$id)->orderBy('sort','ASC')->orderBy('id','DESC')->get();
        return $this->outputJson(0,$data);
    }

    //查询分类列表（通过alias_name）
    public function getTypeListStr($name){
        if(!$name){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $type_id = ContentType::where('alias_name',$name)->value('id');
        $data = ContentType::where('parent_id',$type_id)->orderBy('sort','ASC')->orderBy('id','DESC')->get();
        return $this->outputJson(0,$data);
    }

}
