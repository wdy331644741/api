<?php

namespace App\Http\JsonRpcs;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadSection;
use APP\Models\Bbs\User;
use Lib\JsonRpcClient;
use Illuminate\Pagination\Paginator;
use Validator;



class BbsThreadJsonRpc extends JsonRpc {

    /**
     *  根据板块获取帖子列表
     *
     * @param thread_id 区域id
     * @param pageNum  每页条数
     * @param page 当前页
     * @JsonRpcMethod
     */
    public function getBbsThreadList($params){
        $thread_section = isset($params->thread_section)?$params->thread_section:2;//默认闲聊
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res = Thread::select('id', 'user_id', 'type_id', 'title', 'views', 'comment_num', 'istop', 'isgreat', 'ishot', 'created_at', 'created_at', 'updated_at')->where(['isverify'=>1,'type_id'=>$thread_section])
            ->with('users')
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
     *
     *获取帖子的详情
     * @
     *
     * @JsonRpcMethod
     */
    public  function getBbsThreadDetail($params){
        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_threads,id',

        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );

        }
        $thread_info = Thread::select('id', 'user_id', 'type_id', 'title', 'views', 'comment_num', 'istop', 'isgreat', 'ishot', 'created_at',  'updated_at')
            ->where(['isverify'=>1,'id'=>$params->id])
            ->first();
        $comment_info = Comment::where(['isverify' => 1, 'tid' => $thread_info->id])
            ->with('users')
            ->get()
            ->toArray();
        $data['thread_info'] = $thread_info;
        $data['comment_list'] = $comment_info;
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );

    }
    /**
     *
     *发布帖子
     * @
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishThread($params){
        $validator = Validator::make(get_object_vars($params), [
            'user_id'=>'required|exists:bbs_users,user_id',
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
        $thread->user_id = $params->user_id;
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
     *
     *
     * 获取热门帖子列表
     *
     * @JsonRpcMethod
     */
    public function getBbsThreadTopList($params){
        $res =Thread::where(['istop'=>1,'isverify'=>1])
            ->orderByRaw('created_at DESC')
            ->get()
            ->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );


    }

}

