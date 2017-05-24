<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Bbs\Pm;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\User;
use Lib\JsonRpcClient;
use Illuminate\Pagination\Paginator;
use Validator;




class BbsCommentJsonrpc extends JsonRpc {
    private $userId;
    public  function __construct()
    {
        global $userId;
        $this->userId = $userId;
    }

    /**
     *
     *
     * 获取帖子下评论列表
     *
     * @JsonRpcMethod
     */
   public  function getBbsCommentList($params){
       $userId = $this->userId;
       $validator = Validator::make(get_object_vars($params), [
           'id'=>'required|exists:bbs_threads,id',
       ]);

       if($validator->fails()){
           throw new OmgException(OmgException::DATA_ERROR);
       }
       $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
       $page = isset($params->page) ? $params->page : 1;
       Paginator::currentPageResolver(function () use ($page) {
           return $page;
       });
       $tid = $params->id;
       $data = Comment::where(['tid'=>$tid,"isverify"=>1])
               ->orWhere(function($query)use($userId,$tid){
                   $query->where(['user_id'=>$userId,"tid"=>$tid]);
               })
           ->with('users')
           ->orderByRaw('created_at')
           ->paginate($pageNum)
           ->toArray();

       return array(
           'code'=>0,
           'message'=>'success',
           'data'=>$data,
       );
   }



}

