<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\User;
use App\Models\Bbs\ThreadPm;
use Lib\JsonRpcClient;
use Validator;
use Config;
use Illuminate\Pagination\Paginator;




class BbsUserJsonRpc extends JsonRpc {


    private $userId;
    public function __construct()
    {
        global $userId;
        $this->userId = $userId;

    }

    /**
     *  用户上传头像
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserHeadimg($param){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        if(!isset(Config::get('headimg')['user'][$param->head_img])){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $headimg_url = Config::get('headimg')['user'][$param->head_img];
        $res = User::select('user_id', $this->userId)->update(['head_img' => $headimg_url]);
        if ($res) {
            $user = array(
                'user_id' => $this->userId,
                'head_img' => $param->$headimg_url,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $user
            );
        } else {
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '上传头像失败'
            );

        }

    }

    /**
     *  用户更改昵称
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserNickname($param){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $res = User::select('user_id', $this->userId)->update(['head_img' => $param->nickname]);
        if ($res) {
            $user = array(
                'user_id' => $this->userId,
                'head_img' => $param->nickname,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $user
            );
        } else {
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '更改昵称失败'
            );
        }

    }

    /**
     *  用户发布帖子
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishThread($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'type_id'=>'required|exists:bbs_thread_sections,id',
            'title'=>'required',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        $thread = new Thread();
        $thread->user_id = $this->userId;
        $thread->type_id = $params->type_id;
        $thread->title = isset($params->title) ? $params->title : NULL;
        $thread->content = $params->content;
        $thread->istop =  0;
        $thread->isverify = 0;
        $thread->verify_time = date('Y-m-d H:i:s');
        $thread->save();
        if($thread->id){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => Thread::where(['id'=>$thread->id])->first()
            );
        }else{
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => 'Database Error'
            );
        }

    }
    /**
     *  用户发表评论
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishComment($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_threads,id',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        $comment = new Comment();
        $comment->user_id = $this->userId;
        $comment->tid = $params->id;
        $comment->content = $params->content;
        $comment->isverify = 0;
        $comment->save();
        if($comment->id){
            $thread_info = Thread::where(['id'=>$params->id])->first();
            $this->commentUserPm($thread_info['user_id'],$params->id,$comment->id);//评论后添加到消息列表
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $comment::where(['id'=>$comment->id])->first()
            );
        }else{
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => 'Database Error'
            );
        }


    }
    private function commentUserPm($pmUserId,$tid,$cid){
        $pms = new ThreadPm();
        $pms->user_id = $pmUserId;
        $pms->tid = $tid;
        $pms->cid = $cid;
        $pms->isverify = 0;
        $pms->save();
    }
    /**
     *  获取用户发表的帖子 分页
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserThread($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = Thread::select('id', 'user_id', 'type_id', 'title', 'views', 'comment_num', 'istop', 'isgreat', 'ishot', 'created_at',  'updated_at')
            ->where(['isverify'=>1,'user_id'=>$this->userId])
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        $rData['list'] = $res['data'];
        $rData['total'] = $res['total'];
        $rData['per_page'] = $res['per_page'];
        $rData['current_page'] = $res['current_page'];
        $rData['last_page'] = $res['last_page'];
        $rData['from'] = $res['from'];
        $rData['to'] = $res['to'];
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$rData,
        );



    }
    /**
     *  获取用户发表的评论 分页
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserComment($params){
        $this->userId = 123;
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = Comment::select('id','from_user_id', 'tid', 'content', 'isverify', 'verify_time', 'created_at',  'updated_at')
            ->where(['isverify'=>1,'user_id'=>$this->userId])
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        $rData['list'] = $res['data'];
        $rData['total'] = $res['total'];
        $rData['per_page'] = $res['per_page'];
        $rData['current_page'] = $res['current_page'];
        $rData['last_page'] = $res['last_page'];
        $rData['from'] = $res['from'];
        $rData['to'] = $res['to'];
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$rData,
        );



    }

}

