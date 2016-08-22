<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use App\Models\Cms\Notice;
use Response;
use Storage;
use Validator;

class TemplateController extends Controller
{
    //生成媒体报道html
    public function postMediaList() {
        $contentType = ContentType::where(array('alias_name' =>'report'))->first();
        if(!$contentType) {
            return $this->outputJson(10002, array('error_msg' => '类型不存在'));
        }
        $pageNum = 10;
        $total = Content::where('type_id',$contentType->id)->count();
        $totalPage = ceil($total/$pageNum);
        $where = array('type_id' => $contentType->id, 'release' => 1);
        for($page=1; $page<=$totalPage; $page++){
            $skip = ($page-1)*$pageNum;
            $data = Content::select('id','cover','title','content','release_at','updated_at')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->skip($skip)->take($pageNum)->get();
            $res = view('static.list_media', array('data'=>$data))->render();
            Storage::disk('static')->put("news/list/list_media_{$page}.html", $res);
            foreach($data as $media){
                if($media->updated_at){
                    $timeStamp = strtotime($media->updated_at);
                    if(!file_exists(storage_path('static/news/detail/detail_'.$media->id.'_'.$timeStamp.'.html'))){
                        $fileArr = glob(storage_path('static/news/detail/detail_'.$media->id.'*.html'));
                        for($i=0; $i<count($fileArr); $i++){
                            unlink($fileArr[$i]);
                        }
                        $res = view('static.detail_media', $media)->render();
                        Storage::disk('static')->put("news/detail/detail_".$media->id.'_'.$timeStamp.".html", $res);
                    }
                }else{
                    if(!file_exists(storage_path('static/news/detail/detail_'.$media->id.'.html'))){
                        $res = view('static.detail_media', $media)->render();
                        Storage::disk('static')->put("news/detail/detail_".$media->id.".html", $res);
                    }
                }
            }
        }
    }

    //生成网利动态html
    public function postDynamicList() {
        $contentType = ContentType::where(array('alias_name' =>'trends'))->first();
        if(!$contentType) {
            return $this->outputJson(10002, array('error_msg' => '类型不存在'));
        }
        $pageNum = 10;
        $total = Content::where('type_id',$contentType->id)->count();
        $totalPage = ceil($total/$pageNum);
        $where = array('type_id' => $contentType->id, 'release' => 1);
        for($page=1; $page<=$totalPage; $page++){
            $skip = ($page-1)*$pageNum;
            $data = Content::select('id','cover','title','content','release_at','updated_at')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->skip($skip)->take($pageNum)->get();
            $res = view('static.list_dynamic', array('data'=>$data))->render();
            Storage::disk('static')->put("news/list/list_dynamic_{$page}.html", $res);
            foreach($data as $media){
                if($media->updated_at){
                    $timeStamp = strtotime($media->updated_at);
                    if(!file_exists(storage_path('static/news/detail/detail_'.$media->id.'_'.$timeStamp.'.html'))){
                        $fileArr = glob(storage_path('static/news/detail/detail_'.$media->id.'*.html'));
                        for($i=0; $i<count($fileArr); $i++){
                            unlink($fileArr[$i]);
                        }
                        $res = view('static.detail_dynamic', $media)->render();
                        Storage::disk('static')->put("news/detail/detail_".$media->id.'_'.$timeStamp.".html", $res);
                    }
                }else{
                    if(!file_exists(storage_path('static/news/detail/detail_'.$media->id.'.html'))){
                        $res = view('static.detail_dynamic', $media)->render();
                        Storage::disk('static')->put("news/detail/detail_".$media->id.".html", $res);
                    }
                }
            }
        }
    }


    //生成网站公告html
    public function postNoticeList() {
        $pageNum = 10;
        $total = Notice::count();
        $totalPage = ceil($total/$pageNum);
        $where = array('release' => 1);
        for($page=1; $page<=$totalPage; $page++){
            $skip = ($page-1)*$pageNum;
            $data = Notice::select('id','title','content','release_at','updated_at')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->skip($skip)->take($pageNum)->get();
            $res = view('static.list_notice', array('data'=>$data))->render();
            Storage::disk('static')->put("news/list/list_notice_{$page}.html", $res);
            foreach($data as $media){
                if($media->updated_at){
                    $timeStamp = strtotime($media->updated_at);
                    if(!file_exists(storage_path('static/news/detail/notice_'.$media->id.'_'.$timeStamp.'.html'))){
                        $fileArr = glob(storage_path('static/news/detail/notice_'.$media->id.'*.html'));
                        for($i=0; $i<count($fileArr); $i++){
                            unlink($fileArr[$i]);
                        }
                        $res = view('static.detail_notice', $media)->render();
                        Storage::disk('static')->put("news/detail/notice_".$media->id.'_'.$timeStamp.".html", $res);
                    }
                }else{
                    if(!file_exists(storage_path('static/news/detail/notice_'.$media->id.'.html'))){
                        $res = view('static.detail_notice', $media)->render();
                        Storage::disk('static')->put("news/detail/notice_".$media->id.".html", $res);
                    }
                }
            }
        }
    }

    #TODO //生成加入我们html
    public function postJoinList() {
        $pageNum = 10;
        $total = Notice::count();
        $totalPage = ceil($total/$pageNum);
        $where = array('release' => 1);
        for($page=1; $page<=$totalPage; $page++){
            $skip = ($page-1)*$pageNum;
            $data = Notice::select('id','title','content','release_at','updated_at')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->skip($skip)->take($pageNum)->get();
            $res = view('static.list_notice', array('data'=>$data))->render();
            Storage::disk('static')->put("news/list/list_notice_{$page}.html", $res);
            foreach($data as $media){
                if($media->updated_at){
                    $timeStamp = strtotime($media->updated_at);
                    if(!file_exists(storage_path('static/news/detail/notice_'.$media->id.'_'.$timeStamp.'.html'))){
                        $fileArr = glob(storage_path('static/news/detail/notice_'.$media->id.'*.html'));
                        for($i=0; $i<count($fileArr); $i++){
                            unlink($fileArr[$i]);
                        }
                        $res = view('static.detail_notice', $media)->render();
                        Storage::disk('static')->put("news/detail/notice_".$media->id.'_'.$timeStamp.".html", $res);
                    }
                }else{
                    if(!file_exists(storage_path('static/news/detail/notice_'.$media->id.'.html'))){
                        $res = view('static.detail_notice', $media)->render();
                        Storage::disk('static')->put("news/detail/notice_".$media->id.".html", $res);
                    }
                }
            }
        }
    }
}
