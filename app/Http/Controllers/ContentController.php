<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\ContentType;
use App\Models\Cms\Content;
use App\Models\Cms\Notice;
use Illuminate\Pagination\Paginator;
use App\Models\Bbs\Comment;

use Excel;

class ContentController extends Controller
{
    //内容列表页
    public function getList($type = null,$page = null){
        if(empty($type)){
            return redirect('https://www.wanglibao.com');
        }
        $pageNum =10;
        if($page === null)
            $page = 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if($type == 'notice'){
            $where = array('release' => 1);
            $data = Notice::select('id','title','content','release_at')->where($where)->whereIn('platform',[0,1])->orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pageNum);
            return view('content.list_'.$type,['data'=>$data,'type'=>$type,'base_url'=>env('YY_BASE_HOST')]);
        }
        $contentType = ContentType::where(array('alias_name' =>$type))->first();
        $where = array('type_id' => $contentType->id, 'release' => 1);
        $data = Content::select('id','cover','title','content','release_at','description','keywords')->where($where)->orderByRaw('id + sort DESC')->orderBy('id','desc')->paginate($pageNum);
        return view('content.list_'.$type,['data'=>$data,'type'=>$type,'base_url'=>env('YY_BASE_HOST')]);
    }


    //内容列表页
    public function getDetail($type = null,$id = null){
        if(empty($type)){
            return redirect('https://www.wanglibao.com');
        }
        if(empty($id)){
            return redirect('https://www.wanglibao.com');
        }
        if($type == 'notice'){
            $data = Notice::select('id','title','content','release_at')->where(['id'=>$id])->first();
            return view('content.detail_'.$type,$data);
        }
        $data = Content::select('id','cover','title','content','release_at','description','keywords')->where(['id'=>$id])->first();
        return view('content.detail_'.$type,$data);
    }


    //帮助中心页
    public function getHelp(){
        $contentType = ContentType::where('alias_name','questions')->first();
        $typeArr = ContentType::select('id','parent_id','name')->where('parent_id',$contentType->id)->orderByRaw('id + sort DESC')->orderBy('id','desc')->get();
        $typeId = array();
        foreach ($typeArr as $item){
            $typeId[] = $item['id'];
        }
        $data = ContentType::whereIn('id',$typeId)
            ->with(['contents'=>function($query){
                $query->where(['release'=>1])
                    ->orderByRaw('id + sort DESC')->orderBy('id','desc');
            }])->orderByRaw('id + sort DESC')
            ->orderBy('id','desc')->get();
        $often = Content::select('id','type_id','title')->where(['release'=>1,'platform'=>1])->get();
        return view('content.help', ['data'=>$data,'types'=>$typeArr,'oftens'=>$often,'base_url'=>env('YY_BASE_HOST')]);
    }

    //导出帖子评论
    public function getExportGxfcExecl($tid,$date){
        $start_date = date('Y-m-d 00:00:00',strtotime($date));
        $end_date = date('Y-m-d 00:00:00',strtotime($date."+ 1 day"));
        $data = Comment::select('user_id','content','created_at')->where(['tid'=>$tid,'isverify'=>1])
            ->where('created_at','>=',$start_date)
            ->where('created_at','<',$end_date)
            ->orderBy('created_at','ASC')
            ->get()
            ->toArray();
        foreach($data as $key => $item){
            if($key == 0){
                $cellData[$key] = array('用户ID','评论内容','创建时间');
            }
            $cellData[$key+1] = array($item['user_id'],$item['content'],$item['created_at']);
        }
        $fileName = $date.'-评论列表';
        $typeName = "xls";
        Excel::create($fileName,function($excel) use ($cellData,$fileName){
            $excel->sheet($fileName, function($sheet) use ($cellData){
                $sheet->cells("A1:C1",function ($cells){
                    $cells->setBackground('#C5E1BA');
                    $cells->setFontWeight('bold');
                });
                $sheet->setWidth(array(
                    'A'=>10,
                    'B'=>20,
                    'C'=>20,
                ));
                $sheet->rows($cellData);
            });
        })->export($typeName);
    }
}