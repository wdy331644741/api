<?php

namespace App\Http\JsonRpcs;


use Lib\JsonRpcClient;
use Validator;
use App\Exceptions\OmgException;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadCollect;
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
       $res = ThreadCollect::updateOrCreate(["status"=>0],["user_id"=>$this->userId,"tid"=>$params->id]);
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
        $res = ThreadCollect::updateOrCreate(["status"=>1],["user_id"=>$this->userId,"tid"=>$params->id]);
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
       $res = ThreadZan::updateOrCreate(["status"=>0],["user_id"=>$this->userId,"tid"=>$params->id]);
       if($res){
           Thread::where(["id"=>$params->id])->increment("zan_num");

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
       $res = ThreadZan::updateOrCreate(["status"=>1],["user_id"=>$this->userId,"tid"=>$params->id]);
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
       $res = CommentZan::updateOrCreate(["status"=>0],["user_id"=>$this->userId,"tid"=>$params->id]);
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
       $res = CommentZan::updateOrCreate(["status"=>1],["user_id"=>$this->userId,"tid"=>$params->id]);
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

