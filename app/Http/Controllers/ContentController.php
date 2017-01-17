<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\ContentType;
use App\Models\Cms\Content;
use App\Models\Cms\Notice;
use Illuminate\Pagination\Paginator;

class ContentController extends Controller
{
    //内容列表页
    public function getList($type = null,$page = null){
        if(empty($type)){
            return redirect('https://www.wanglibao.com');
        }
        if($type == 'notice'){
            
        }
        $pageNum =10;
        if($page === null)
            $page = 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $contentType = ContentType::where(array('alias_name' =>$type))->first();
        $where = array('type_id' => $contentType->id, 'release' => 1);
        $data = Content::select('id','cover','title','content','release_at','updated_at','description','keywords')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pageNum);
        return view('content.list_'.$type,['data'=>$data,'type'=>$type]);
    }


    //内容列表页
    public function getDetail($id = null){
        if(empty($id)){
            return redirect('https://www.wanglibao.com');
        }
        dd($id);
    }
}
