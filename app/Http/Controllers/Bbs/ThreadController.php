<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Service\Func;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Pm;
use Validator;

class ThreadController extends Controller
{
    //帖子为审核列表
    public function getList($isverify=0){
        if(!in_array($isverify,[0,1])){
            $res = Thread::onlyTrashed()->with('users','sections')->orderBy('id','desc')->paginate(20)->toArray();
            return $this->outputJson(0,$res);
        }
        $res = Thread::where('isverify',$isverify)->with('users','sections')->orderBy('id','desc')->paginate(20)->toArray();
        return $this->outputJson(0,$res);
    }

    //帖子搜索
    public function getSearch(Request $request){
        $res = Func::Search($request,new Thread());
        return $this->outputJson(0,$res);
    }

    //还原帖子
    public function postRestore(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'user_id'=>'required',
            'cid'=>'required|exists:bbs_replay_configs,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $isverify = Thread::find($request->id)->value('isverify');
        if($isverify){
            $pm = new Pm();
            $pm->user_id = $request->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $request->id;
            $pm->cid = $request->cid;
            $pm->save();
        }
        $res = Thread::where('id',$request->id)->restore();
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //内部发帖
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|exists:bbs_users,user_id',
            'type_id'=>'required|exists:bbs_thread_sections,id',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $thread = new Thread();
        $thread->user_id = $request->user_id;
        $thread->type_id = $request->type_id;
        $thread->cover = isset($request->cover) ? $request->cover : NULL;
        $thread->title = isset($request->title) ? $request->title : NULL;
        $thread->content = $request->content;
        $thread->isinside = 1;
        $thread->istop = $request->istop ? $request->istop : 0;
        $thread->isverify = 1;
        $thread->verify_time = date('Y-m-d H:i:s');
        $thread->save();
        if($thread->id){
            return $this->outputJson(0,array('insert_id'=>$thread->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //编辑帖子
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'user_id'=>'exists:bbs_users,user_id',
            'type_id'=>'exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [
            'istop'=>$request->istop ? $request->istop : 0
        ];
        if(isset($request->user_id)){
            $putData['user_id'] = $request->user_id;
        }
        if(isset($request->type_id)){
            $putData['type_id'] = $request->type_id;
        }
        if(isset($request->cover)){
            $putData['cover'] = $request->cover;
        }
        if(isset($request->title)){
            $putData['title'] = $request->title;
        }
        if(isset($request->content)) {
            $putData['content'] = $request->content;
        }
        $res = Thread::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除帖子（审核失败）
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'user_id'=>'required|exists:bbs_users,user_id',
            'cid'=>'required|exists:bbs_replay_configs,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        Thread::destroy($request->id);
        $pm = new Pm();
        $pm->user_id = $request->user_id;
        $pm->from_user_id = 0;
        $pm->tid = $request->id;
        $pm->cid = $request->cid;
        $pm->save();
        if($pm->id){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //审核，加精，置顶，最热
    public function postToogleStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [];
        if(isset($request->istop)){
            $putData['istop'] = $request->istop;
        }
        if(isset($request->isgreat)){
            $putData['isgreat'] = $request->isgreat;
        }
        if(isset($request->ishot)){
            $putData['ishot'] = $request->ishot;
        }
        if(isset($request->isverify)){
            $putData['isverify'] = $request->isverify;
            $putData['verify_time'] = date('Y-m-d H:i:s');
        }
        $res = Thread::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}