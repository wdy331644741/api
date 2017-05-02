<?php

namespace App\Http\JsonRpcs;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\User;
use Lib\JsonRpcClient;
use Illuminate\Pagination\Paginator;
use Validator;



class BbsThreadJsonRpc extends JsonRpc {

    /**
     *  根据板块获取帖子列表
     *
     * @param id 区域id
     * @param pageNum  每页条数
     * @param page 当前页
     * @JsonRpcMethod
     */
    public function getBbsThreadList($params){

        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $threadSectionId = $params->id;
        $res = Thread::select('id', 'user_id', 'type_id', 'title', 'views', 'comment_num', 'istop', 'isgreat', 'ishot', 'created_at', 'created_at', 'updated_at')->where(['isverify'=>1,'type_id'=>$params->id])
            ->with('users')
            ->whereNotIn('user_id', function($query){
                $query->select('user_id')
                    ->from('bbs_users')
                    ->where(['isadmin'=>1]);
            })
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
            ->with('users')
            ->where(['isverify'=>1,'id'=>$params->id])
            ->first();
        $comment_info = Comment::where(['isverify' => 1, 'tid' => $thread_info->id])
            ->with('users')
            ->orderByRaw('created_at')
            ->get()
            ->toArray();
        //view +1
        Thread::where(['id'=>$params->id])->update(['views'=>$thread_info['views']+1]);
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
     *
     * 获取置顶帖子列表
     *
     * @JsonRpcMethod
     */
    public function getBbsThreadTopList($params){
        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        $limit = isset($params->limit)?$params->limit:-1;
        $res =Thread::where(['istop'=>1,'isverify'=>1,'type_id'=>$params->id])
            ->whereIn('user_id',function ($query){
                $query->select('user_id')
                    ->from('bbs_users')
                ->where(['isadmin'=>1]);
            })
            ->with('users')
            ->limit($limit)
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

