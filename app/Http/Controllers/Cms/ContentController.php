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
        $data = Content::where('type_id',$type_id)->orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //获取内容列表(通过alias_name获取)
    public function getListStr($name,$pagenum=5){
        if(!$name){
            $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $type_id = ContentType::where('alias_name',$name)->value('type_id');
        $data = Content::where('type_id',$type_id)->orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //添加内容
    public function postAdd(Request $request){

        $validator = Validator::make($request->all(), [
            'type_id' => 'required|integer|exists:cms_content_types,id',
            'title' => 'required',
            'content' => 'required',
            'platform' =>'in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $platform = isset($request->platform) ? $request->platform : 0;
        $content = new Content();
        $content->type_id = $request->type_id;
        $content->title = $request->title;
        $content->content = $request->content;
        $content->platform = $platform;
        /*if(isset($request->release)){
            $content->release = $request->release;
        }*/
        if(isset($request->cover)){
            $content->cover = $request->cover;
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
            'platform' =>'in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $platform = isset($request->platform) ? $request->platform : 0;
        $putdata = array(
            'type_id'=>$request->type_id,
            'title'=>$request->title,
            'content'=>$request->content,
            'platform'=>$platform,
        );

        if(isset($request->cover)){
            $putdata['cover'] = $request->cover;
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
        $data = Content::find($id);
        return $this->outputJson(0,$data);
    }

    //发布内容接口
    public function postRelease(Request $request){
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:cms_contents,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $updata = [
            'release'=>1,
            'release_at'=>date('Y-m-d H:i:s')
        ];
        $res = Content::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //下线内容接口
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:cms_contents,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Content::where('id',$request->id)->update(array('release',0));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //内容上移
    public function getUp($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_contents,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = Content::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = Content::whereRaw("id + sort > $current_num")->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = Content::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = Content::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //内容下移
    public function getDown($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_contents,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = Content::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = Content::whereRaw("id + sort < $current_num")->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'	Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = Content::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = Content::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //分类上移
    public function getTypeUp($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_content_types,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $parent_id = ContentType::find($id)->value('parent_id');
        $current = ContentType::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = ContentType::where('parent_id',$parent_id)->whereRaw("id + sort > $current_num")->where('parent_id',$parent_id)->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = ContentType::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = ContentType::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //分类下移
    public function getTypeDown($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_content_types,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $parent_id = ContentType::find($id)->value('parent_id');
        $current = ContentType::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = ContentType::where('parent_id',$parent_id)->whereRaw("id + sort < $current_num")->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = ContentType::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = ContentType::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //新建分类
    public function postTypeAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|numeric',
            'name' => 'required',
            'alias_name' => 'alpha_dash|unique:cms_content_types,alias_name',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $alias_name = isset($request->alias_name) ? $request->alias_name : NULL;
        $contentType = new ContentType();
        $contentType->parent_id = $request->parent_id;
        $contentType->name = $request->name;
        $contentType->alias_name = $alias_name;
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
            'alias_name' => 'alpha_dash|unique:cms_content_types,alias_name',
        ]);

        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $alias_name = isset($request->alias_name) ? $request->alias_name : NULL;
        $putdata = array(
            'parent_id'=>$request->parent_id,
            'name'=>$request->name,
            'alias_name'=>$alias_name,
        );
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
        $data = array();
        if($id == 0){
            $base = ContentType::where('parent_id',0)->orderByRAW('id + sort DESC')->orderBy('id','DESC')->get()->toArray();
            foreach($base as $val){
                $childrens = ContentType::where('parent_id',$val['id'])->orderByRAW('id + sort DESC')->orderBy('id','DESC')->get()->toArray();
                $val['childrens'] = $childrens;
                $data[] = $val;
            }
        }elseif ($id){
            $data = ContentType::where('parent_id',intval($id))->orderByRAW('id + sort DESC')->orderBy('id','DESC')->get();
        }else{
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        return $this->outputJson(0,$data);
    }

    //查询分类列表（通过alias_name）
    public function getTypeListStr($name){
        if(!$name){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $type_id = ContentType::where('alias_name',$name)->value('id');
        $data = ContentType::where('parent_id',$type_id)->orderByRaw('id + sort DESC')->orderBy('id','DESC')->get();
        return $this->outputJson(0,$data);
    }

}
