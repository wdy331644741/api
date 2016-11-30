<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Cms\Notice;
use Validator;
use Storage;

class NoticeController extends Controller
{
    //获取公告列表
    public function getList($pagenum=30){
        $data = Notice::orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pagenum);
        return $this->outputJson(0,$data);
    }

    //添加公告
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
            'platform' =>'in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $platform = isset($request->platform) ? $request->platform : 0;
        $notice = new Notice();
        $notice->title = $request->title;
        $notice->content = $request->content;
        $notice->platform = $platform;
        $res = $notice->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$notice->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除公告
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_notices,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Notice::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改公告
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_notices,id',
            'title' => 'required',
            'content' => 'required',
            'platform' =>'in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $platform = isset($request->platform) ? $request->platform : 0;
        $putdata = array(
            'title'=>$request->title,
            'content'=>$request->content,
            'platform'=>$platform,
        );
        
        $res = Notice::where('id',$request->id)->update($putdata);
        if($res){
            $notice = Notice::find($request->id);
            if($notice->updated_at){
                $timeStamp = strtotime($notice->updated_at);
                if(!file_exists(storage_path('cms/notice/detail/'.$request->id.'-'.$timeStamp.'.html'))){
                    $fileArr = glob(storage_path('cms/notice/detail/'.$request->id.'-*.html'));
                    for($i=0; $i<count($fileArr); $i++){
                        unlink($fileArr[$i]);
                    }
                    if(file_exists(storage_path('cms/notice/detail/'.$request->id.'.html'))){
                        unlink(storage_path('cms/notice/detail/'.$request->id.'.html'));
                    }
                    $res = view('static.detail_notice', $notice)->render();
                    Storage::disk('static')->put("notice/detail/".$request->id.'-'.$timeStamp.".html", $res);
                }
            }
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //获取指定公告
    public function getDetail($id){
        if(!$id){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $data = Notice::find($id);
        return $this->outputJson(0,$data);
    }

    //发布公告接口
    public function postRelease(Request $request){
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:cms_notices,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $updata = [
            'release'=>1,
            'release_at'=>date('Y-m-d H:i:s')
        ];
        $res = Notice::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //下线公告接口
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:cms_notices,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Notice::where('id',$request->id)->update(array('release'=>0));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //公告上移
    public function getUp($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_notices,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = Notice::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = Notice::whereRaw("id + sort > $current_num")->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = Notice::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = Notice::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //内容下移
    public function getDown($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_notices,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = Notice::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = Notice::whereRaw("id + sort < $current_num")->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'	Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = Notice::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = Notice::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
