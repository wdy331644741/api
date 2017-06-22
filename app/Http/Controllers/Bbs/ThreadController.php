<?php

namespace App\Http\Controllers\Bbs;

use App\Models\Bbs\ReplyConfig;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Service\Func;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Thread;
use App\Http\Traits\BasicDatatables;
use App\Models\Bbs\Pm;
use PhpParser\Comment;
use Validator;

class ThreadController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id','title','content', 'type_id', 'url', 'cover', 'created_at', 'istop', 'isgreat', 'ishot', 'isverify', 'comment_num'];
    protected $deleteValidates = [
        'id' => 'required|exists:bbs_threads,id'
    ];
    protected $addValidates = [
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_threads,id'
    ];

    function __construct() {
        $this->model = new Thread();
    }

    //帖子为审核列表
    public function getList($sid,$isverify=0){
        if(!in_array($isverify,[0,1,2])){
            $res = Thread::where('type_id',$sid)->onlyTrashed()->with('user','section')->orderBy('id','desc')->paginate(20)->toArray();
            return $this->outputJson(0,$res);
        }
        $res = Thread::where(['type_id'=>$sid,'isverify'=>$isverify])->with('user','section')->orderBy('id','desc')->paginate(20)->toArray();
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
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
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
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $thread = new Thread();
        $thread->user_id = $request->user_id;
        $thread->type_id = $request->type_id;
        $thread->cover = isset($request->cover) ? $request->cover : NULL;
        $thread->title = isset($request->title) ? $request->title : NULL;
        $thread->content = isset($request->content) ? $request->content : NULL;
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

    //审核失败
    public function postPassFail(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'cid'=>'required|exists:bbs_replay_configs,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $thread = Thread::find($request->id);
        if(in_array($thread->isverify,[1,2])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        Thread::where('id',$request->id)->update(['isverify'=>2,'verify_time'=>date('Y-m-d H:i:s')]);
        $pm = new Pm();
        $pm->user_id = $thread->user_id;
        $pm->from_user_id = 0;
        $pm->tid = $request->id;
        $pm->cid = $request->cid;
        $pm->type = 2;
        $reply = ReplyConfig::find($request->cid);
        $pm->content = $reply->description;
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
            $verify_time = date('Y-m-d H:i:s');
            $putData['isverify'] = $request->isverify;
            $putData['verify_time'] = $verify_time;
            $thread = Thread::find($request->id);
            if(in_array($thread->isverify,[1,2])){
                return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
            }
            $pm = new Pm();
            $pm->user_id = $thread->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $request->id;
            $pm->type = 1;
            $pm->save();
        }
        $res = Thread::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //批量通过审核
    public function postBatchPass(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        foreach ($request->id as $val){
            $thread = Thread::find($val);
            if(in_array($thread->isverify,[1,2])){
                $error[$val] = 10010;
                continue;
            }
            $verify_time = date('Y-m-d H:i:s');
            $putData['isverify'] = 1;
            $putData['verify_time'] = $verify_time;
            $pm = new Pm();
            $pm->user_id = $thread->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $val;
            $pm->type = 1;
            $pm->save();
            $res = Thread::where('id',$request->id)->update($putData);
            if(!$res){
                $error[$val] = 10002;
                continue;
            }
        }
        if(empty($error)){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10011,array('error_msg'=>'Error Array','error_arr'=>$error));
        }
    }

    //后台回复帖子
    public function postAdminReply(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $res = Thread::where('id',$request->id)->first();
        if(in_array($res->isverify,[0,2])){
            return $this->outputJson(10012,array('error_msg'=>'Error Operation'));
        }
        $verify_time = date('Y-m-d H:i:s');
        $comment = new Comment();
        $comment->user_id = 0;
        $comment->tid = $request->id;
        $comment->content = $request->content;
        $comment->isverify = 1;
        $comment->verify_time = $verify_time;
        $comment->save();
        if($comment->id){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }
}
