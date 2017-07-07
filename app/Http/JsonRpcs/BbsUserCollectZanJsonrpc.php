<?php

namespace App\Http\JsonRpcs;


use Lib\JsonRpcClient;
use Validator;
use App\Exceptions\OmgException;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadCollection;
use App\Models\Bbs\ThreadZan;
use App\Models\Bbs\CommentZan;



class BbsUserCollectZanJsonrpc extends JsonRpc {


    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
        $this->userId =123;

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
       //dd($res);
       if($res){
           Thread::where(["id"=>$params->id])->increment("collection_num");
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
       $commentInfo = Comment::where(["id"=>$params->id]);
       $res = CommentZan::updateOrCreate(["user_id"=>$this->userId,"cid"=>$params->id,"c_user_id"=>$commentInfo["user_id"]],["status"=>0]);
       if($res){
           Comment::where(["id"=>$params->id])->increment("zan_num");
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
}

