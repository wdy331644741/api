<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Comment;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Pm;
use App\Models\Bbs\ReplyConfig;
use App\Models\Bbs\CommentReply;
use App\Http\Traits\BasicDatatables;
use App\Service\Func;
use Validator;

class CommentController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id', 'tid', 'content', 'isverify', 'created_at'];
    protected $deleteValidates = [
        'id'=>'required|exists:bbs_comments,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Comment();
    }

    //帖子为审核列表
    public function getList(Request $request){
        $res = Func::freeSearch($request,new Comment(),$this->fileds,['thread']);
        return response()->json(array('error_code'=> 0, 'data'=>$res));

    }

    //官方回复评论
    public function postAdminReply(Request $request) {
        $validator = Validator::make($request->all(), [
            'comment_id'=>'required|exists:bbs_comments,id',
            'from_id'=>'required|exists:bbs_users,user_id',
            'content'=>'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        if(isset($request->comment_type)){
            $commentType = $request->comment_type ? 3 : 1;
        }
        $comment = Comment::find($request->comment_id);
        $commentReply = new CommentReply();
        $commentReply->comment_id = $request->comment_id;
        $commentReply->from_id = $request->from_id;
        $commentReply->to_id = $comment->user_id;
        $commentReply->content = $request->content;
        $commentReply->reply_type = $commentType === 1 ? "comment" : "official";
        $commentReply->t_user_id = $comment->t_user_id;
        $commentReply->is_verify = 1;
        $commentObj = new Comment();
        $commentObj->user_id = $request->from_id;
        $commentObj->tid = $comment->tid;
        $commentObj->content = $request->content;
        $commentObj->isverify = 1;
        $commentObj->verify_time = date('Y-m-d H:i:s');
        $commentObj->comment_type = $commentType;
        $commentObj->t_user_id = $comment->t_user_id;
        $res1 = $commentObj->save();
        $res = $commentReply->save();
        if($res && $res1){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //评论帖子
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'tid'=>'required|exists:bbs_threads,id',
            'content'=>'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $t_user_id = Thread::where('id',$request->tid)->value('user_id');
        $commentReply = new Comment();
        $commentReply->user_id = $request->user_id;
        $commentReply->content = $request->content;
        $commentReply->tid = $request->tid;
        $commentReply->comment_type = $request->comment_type ? 2 : 0;
        $commentReply->t_user_id = $t_user_id;
        $commentReply->isverify = 1;
        $commentReply->verify_time = date('Y-m-d H:i:s');
        $res = $commentReply->save();
        if($res){
            return $this->outputJson(0,array('id'=>$commentReply->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //审核状态修改
    public function postVerifyPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_comments,id',
            'isverify'=>'required|in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        switch ($request->isverify){
            case  1:
                return $this->_checkSuccess($request->id);
                break;
            case  2:
                return $this->_checkFail($request->id);
                break;
        }
    }

    //拒绝审核
    private function _checkFail($id){
        if(empty($id)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $comment = Comment::find($id);
        if(in_array($comment->isverify,[2])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        $thread = Thread::where('id',$comment->tid)->first();
        if($comment->isverify == 1){
            $thread->decrement('comment_num');
        }
        /*if(in_array($thread->isverify,[2])){
            return $this->outputJson(10012,array('error_msg'=>'Error Operation'));
        }*/
        $res = Comment::where('id',$id)->update(['isverify'=>2,'verify_time'=>date('Y-m-d H:i:s')]);
        if($comment != null){
            $pm = new Pm();
            $pm->user_id = $thread->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $comment->tid;
            $pm->msg_type = 1;
            $pm->type = 3;
            $pm->content = '您的回复未能通过审核';
            $pm->save();
        }
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //审核通过
    private function _checkSuccess($id){
        if(empty($id)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $comment = Comment::find($id);
        if(in_array($comment->isverify,[1])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        Thread::where('id',$comment->tid)->increment('comment_num');
        $res = Comment::where('id',$id)->update(['isverify'=>1,'verify_time'=>date('Y-m-d H:i:s')]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //批量审核
    public function postBatchPass(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $idArr = explode('-',$request->id);
        foreach (array_filter($idArr) as $val){
            $comment = Comment::find($val);
            if(in_array($comment->isverify,[1])){
                $error[$val] = 10010;
                continue;
            }
            $thread = Thread::find($comment->tid);
            $user_id = null;
            if($thread != null){
                /*if(in_array($thread->isverify,[2])){
                    $error[$val] = 10012;
                    continue;
                }*/
                $user_id = $thread->user_id;
                $pm = new Pm();
                $pm->user_id = $user_id;
                $pm->from_user_id = $comment->user_id;
                $pm->tid = $comment->tid;
                $pm->cid = 0;
                $pm->type = 3;
                $pm->content = $comment->content;
                $pm->save();
                Thread::where('id',$comment->tid)->increment('comment_num');
            }
            $putData = [
                'isverify'=>1,
                'verify_time'=>date('Y-m-d H:i:s')
            ];
            $res = Comment::find($val)->update($putData);
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

    //批量拒绝评论
    public function postBatchRefuse(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $idArr = explode('-',$request->id);
        foreach (array_filter($idArr) as $val){
            $comment = Comment::find($val);
            if(in_array($comment->isverify,[2])){
                $error[$val] = 10010;
                continue;
            }
            $thread = Thread::find($comment->tid);
            $user_id = null;
            if($thread != null){
                /*if(in_array($thread->isverify,[2])){
                    $error[$val] = 10012;
                    continue;
                }*/
                $user_id = $thread->user_id;
                $pm = new Pm();
                $pm->user_id = $user_id;
                $pm->from_user_id = 0;
                $pm->tid = $comment->tid;
                $pm->msg_type = 1;
                $pm->type = 3;
                $pm->content = '您的回复未能通过审核';
                $pm->save();
                if(in_array($comment->isverify,[1])){
                    Thread::where('id',$comment->tid)->decrement('comment_num');
                }
            }
            $putData = [
                'isverify'=>2,
                'verify_time'=>date('Y-m-d H:i:s')
            ];
            $res = Comment::find($val)->update($putData);
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


    /*//审核评论
    public function postCheck(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_comments,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $comment = Comment::find($request->id);
        if(in_array($comment->isverify,[1])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        $thread = Thread::find($comment->tid);
        $user_id = null;
        if($thread != null){
            if(in_array($thread->isverify,[0,2])){
                return $this->outputJson(10012,array('error_msg'=>'Error Operation'));
            }
            $user_id = $thread->user_id;
            $pm = new Pm();
            $pm->user_id = $user_id;
            $pm->from_user_id = $comment->user_id;
            $pm->tid = $comment->tid;
            $pm->cid = 0;
            $pm->type = 3;
            $pm->content = $comment->content;
            $pm->save();
            Thread::where('id',$comment->tid)->increment('comment_num');
        }
        $putData = [
            'isverify'=>1,
            'verify_time'=>date('Y-m-d H:i:s')
        ];
        $res = Comment::find($request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //审核评论失败
    public function postCheckFail(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_comments,id',
            'cid'=>'required|exists:bbs_replay_configs,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $comment = Comment::find($request->id);
        if(in_array($comment->isverify,[2])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        if($comment != null){
            $pm = new Pm();
            $pm->user_id = $comment->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $comment->tid;
            $pm->cid = $request->cid;
            $pm->comment_id = $request->id;
            $pm->type = 4;
            $reply = ReplyConfig::find($request->cid);
            $pm->content = $reply->description;
            $pm->save();
        }
        $res = Comment::where('id',$request->id)->update(['isverify'=>2]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }*/

}