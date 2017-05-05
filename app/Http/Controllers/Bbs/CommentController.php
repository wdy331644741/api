<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Comment;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Pm;
use App\Http\Traits\BasicDatatables;
use Validator;

class CommentController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id','content', 'isverify','verify_time'];
    protected $deleteValidates = [
        'id'=>'required|exists:bbs_comments,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Comment();
    }


    //审核评论
    public function postCheck(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_comments,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $comment = Comment::find($request->id);
        $thread = Thread::find($comment->tid);
        $user_id = null;
        if($thread != null){
            $user_id = $thread->user_id;
            $pm = new Pm();
            $pm->user_id = $user_id;
            $pm->from_user_id = $comment->user_id;
            $pm->tid = $comment->tid;
            $pm->cid = 0;
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
}
