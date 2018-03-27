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
use App\Service\AliyunOSSService;
use OSS\OssClient;
use OSS\Core\OssException;




class BbsCommentJsonRpc extends JsonRpc {
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
       $sort = isset($params->sort) ? $params->sort : 'asc';
       Paginator::currentPageResolver(function () use ($page) {
           return $page;
       });
       $tid = $params->id;
       $comment = new Comment(["userId"=>$userId]);
       $data = $comment->where(['tid'=>$tid,"isverify"=>1])
               ->orWhere(function($query)use($userId,$tid){
                   $query->where(['user_id'=>$userId,"tid"=>$tid]);
               })
           ->with('users')
           ->with('zan')
           ->with('replyUser')
           ->with('officeReply')
           ->orderBy('created_at',$sort)
           ->paginate($pageNum)
           ->toArray();

       return array(
           'code'=>0,
           'message'=>'success',
           'data'=>$data,
       );
   }
      /**
     *
     *
     * 删除帖子
     *
     *
     * @JsonRpcMethod
     */
    public  function delBbsComment($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'ids' => 'required'
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $resNum =0;
        foreach ($params->ids as $value) {
            $commentInfo = Comment::where(["id" => $value])->first();
            $res = Comment::where(["id" => $value, "user_id" => $this->userId])->delete();
            if ($res) {
                Thread::where(["id" => $commentInfo['tid']])->decrement('comment_num');
                $resNum++;
            }
        }
        return[
            'code'=>0,
            'message'=>'success',
            'data'=>$resNum,
        ];
    }


    /**
     *
     *
     *获取帖子详情
     *
     *
     *
     * @JsonRpcMethod
     */
    public  function getBbsCommentDetail($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
             'id'=>'required|exists:bbs_comments,id'
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $res = Comment::where(["id"=>$params->id,"isverify"=>1])->first();
        return[
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        ];
    }



}

