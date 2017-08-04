<?php

namespace App\Http\JsonRpcs;


use App\Service\BbsSendAwardService;
use Lib\JsonRpcClient;
use Validator;
use App\Exceptions\OmgException;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadCollection;
use App\Models\Bbs\ThreadZan;
use App\Models\Bbs\CommentZan;
use App\Models\Bbs\Pm;
use Illuminate\Pagination\Paginator;



class BbsUserCollectZanJsonrpc extends JsonRpc {


    public function __construct()
    {
        global $userId;
        $this->userId = $userId;

    }

    /**
     *  增加用户收藏
     *
     *
     * @JsonRpcMethod
     */

    public function AddThreadCollect($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
       $validator = Validator::make(get_object_vars($params), [
           'id' => 'required|exists:bbs_threads,id',

       ]);

       if ($validator->fails()) {
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $threadInfo = Thread::where(["id"=>$params->id])->first();
       $res = ThreadCollection::updateOrCreate(["user_id"=>$this->userId,"tid"=>$params->id,"t_user_id"=>$threadInfo['user_id']],["status"=>0]);

       if($res){
           Thread::where(["id"=>$params->id])->increment("collection_num");
           $pm = new Pm();
           $pm->user_id = $threadInfo['user_id'];
           $pm->from_user_id = $this->userId;
           $pm->tid = $threadInfo['id'];
           $pm->content = "收藏了你的帖子";
           $pm->type = 1;
           $pm->msg_type = 2;
           $pm->save();
           return array(
               'code'=>0,
               'message'=>'success',
               'data'=>$res,
           );
       }else{
           throw new OmgException(OmgException::API_FAILED);
       }

   }
    /**
     *  删除用户收藏
     *
     *
     * @JsonRpcMethod
     */
    public function DelThreadCollect($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'id' => 'required|exists:bbs_threads,id',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $res = ThreadCollection::updateOrCreate(["user_id"=>$this->userId,"tid"=>$params->id],["status"=>1]);
        if($res){
             Thread::where(["id"=>$params->id])->decrement("collection_num");
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$res,
            );
        }else{
            throw new OmgException(OmgException::API_FAILED);
        }

    }
    /**
     *  增加帖子赞
     *
     *
     * @JsonRpcMethod
     */
   public  function AddThreadZan($params){
       if (empty($this->userId)) {
           throw  new OmgException(OmgException::NO_LOGIN);
       }
       $validator = Validator::make(get_object_vars($params), [
           'id' => 'required|exists:bbs_threads,id',
       ]);

       if ($validator->fails()) {
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $threadInfo = Thread::where(["id"=>$params->id])->first();
       $res = ThreadZan::updateOrCreate(["user_id"=>$this->userId,"tid"=>$params->id,"t_user_id"=>$threadInfo['user_id']],["status"=>0]);
       if($res){
           Thread::where(["id"=>$params->id])->increment("zan_num");
           $pm = new Pm();
           $pm->user_id = $threadInfo['user_id'];
           $pm->from_user_id = $this->userId;
           $pm->tid = $threadInfo['id'];
           $pm->content = "赞了你的帖子";
           $pm->type = 2;
           $pm->msg_type = 2;
           $pm->save();
           $bbsAward = new BbsSendAwardService($this->userId,$threadInfo['user_id']);
           $bbsAward->threadZanAward();
           return array(
               'code'=>0,
               'message'=>'success',
               'data'=>$res,
           );
       }else{
           throw new OmgException(OmgException::API_FAILED);
       }

   }
    /**
     *  删除帖子赞
     *
     *
     * @JsonRpcMethod
     */

   public function delThreadZan($params){
       if (empty($this->userId)) {
           throw  new OmgException(OmgException::NO_LOGIN);
       }
       $validator = Validator::make(get_object_vars($params), [
           'id' => 'required|exists:bbs_threads,id',
       ]);

       if ($validator->fails()) {
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $res = ThreadZan::updateOrCreate(["user_id"=>$this->userId,"tid"=>$params->id],["status"=>1]);
       if($res){
           Thread::where(["id"=>$params->id])->decrement("zan_num");
           return array(
               'code'=>0,
               'message'=>'success',
               'data'=>$res,
           );
       }else{
           throw new OmgException(OmgException::API_FAILED);
       }
   }

    /**
     *  增加评论赞
     *
     *
     * @JsonRpcMethod
     */
   public function AddCommentZan($params){
       if (empty($this->userId)) {
           throw  new OmgException(OmgException::NO_LOGIN);
       }
       $validator = Validator::make(get_object_vars($params), [
           'id' => 'required|exists:bbs_comments,id',
       ]);

       if ($validator->fails()) {
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $commentInfo = Comment::where(["id"=>$params->id])->first();
       $res = CommentZan::updateOrCreate(["user_id"=>$this->userId,"cid"=>$params->id,"c_user_id"=>$commentInfo["user_id"]],["status"=>0]);
       if($res){
           Comment::where(["id"=>$params->id])->increment("zan_num");
           $pm = new Pm();
           $pm->user_id = $commentInfo['user_id'];
           $pm->from_user_id = $this->userId;
           $pm->cid = $commentInfo['id'];
           $pm->content = "赞了你的评论";
           $pm->type = 2;
           $pm->msg_type = 2;
           $pm->save();
           $bbsAward = new BbsSendAwardService($this->userId,$commentInfo["user_id"]);
           $bbsAward->commentZanAward();
           return array(
               'code'=>0,
               'message'=>'success',
               'data'=>$res,
           );
       }else{
           throw new OmgException(OmgException::API_FAILED);
       }


   }
    /**
     *  删除评论赞
     *
     *
     * @JsonRpcMethod
     */
   public function delCommentZan($params){
       if (empty($this->userId)) {
           throw  new OmgException(OmgException::NO_LOGIN);
       }
       $validator = Validator::make(get_object_vars($params), [
           'id' => 'required|exists:bbs_comments,id',
       ]);

       if ($validator->fails()) {
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $res = CommentZan::updateOrCreate(["user_id"=>$this->userId,"cid"=>$params->id],["status"=>1]);
       if($res){
           Comment::where(["id"=>$params->id])->decrement("zan_num");
           return array(
               'code'=>0,
               'message'=>'success',
               'data'=>$res,
           );
       }else{
           throw new OmgException(OmgException::API_FAILED);
       }
   }
    /**
     *  获取收藏帖子接口
     *
     *
     * @JsonRpcMethod
     */
    public function getBbsUserCollectList($params)
    {
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = ThreadCollection::where(["user_id"=>$this->userId,"status"=>0])
            ->with('thread')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();
        ;
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );

    }
    /**
     *  批量删除收藏帖子接口
     *
     *
     * @JsonRpcMethod
     */
    public function delBbsThreadCollect($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'ids' => 'required',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $resNum = 0;
        foreach ($params->ids as $value) {
            $threadCollectInfo = ThreadCollection::where(["id"=>$value])->first();
            $res = ThreadCollection::updateOrCreate(["user_id" => $this->userId, "id" => $value], ["status" => 1]);
            if ($res) {
                Thread::where(["id" => $threadCollectInfo["tid"]])->decrement("collection_num");
                $resNum++;
            }
        }
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$resNum,
        ];


    }

}

