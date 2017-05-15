<?php

namespace App\Http\JsonRpcs;
use App\Models\Bbs\Pm;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\User;
use Lib\JsonRpcClient;
use Illuminate\Pagination\Paginator;
use Validator;



class BbsThreadJsonRpc extends JsonRpc {


    public  function __construct()
    {
        global $userId;
        $this->userId =$userId;
    }
    /**
     *  根据板块获取帖子列表
     *
     * @param id 区域id
     * @param pageNum  每页条数
     * @param page 当前页
     * @JsonRpcMethod
     */
    public function getBbsThreadList($params){
        $userId = $this->userId;
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
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        //自定义分页  查找本周2条view最多的帖子  剔除 管理员发的置顶贴
        $mondayTime = date("Y-m-d",strtotime("-1 week Monday"));

        $thread = new Thread(['userId'=>$userId]);

        $hotThread = $thread->where(['isverify'=>1,'type_id'=>$typeId])
                             ->orWhere(function($query)use($typeId,$userId){
                                 $query->where(['user_id'=>$userId,"type_id"=>$typeId]);
                })
            ->where('created_At','>',$mondayTime)
            ->with('users')
            ->with("commentAndVerify")
            ->whereNotIn('id', function($query) use($typeId){
                $query->select('id')
                    ->from('bbs_threads')
                    ->where(['isinside'=>1,'istop'=>1,'type_id'=>$typeId]);
            })
            ->orderByRaw('views DESC')
            ->offset(0)
            ->limit(2)
            ->orderByRaw('created_at DESC')
            ->get()
            ->toArray();

        foreach ($hotThread as $key=>$value){
            $hotThreadId[] = $value['id'];
        }
        $res = $thread
            ->Where(function($query)use($typeId,$userId){
            $query->where(['user_id'=>$userId,"type_id"=>$typeId])
                ->orwhere(['isverify'=>1,"type_id"=>$typeId]);
        })
            ->with('users')
            ->with("commentAndVerify")
            ->whereNotIn('id', function($query) use($typeId){
                $query->select('id')
                    ->from('bbs_threads')
                    ->where(['isinside'=>1,'istop'=>1,'type_id'=>$typeId]);
            })
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        if(empty($hotThread)){

            foreach ($res['data'] as $key => $value){
                $res['data'][$key]['comments']=[];
                foreach ($value['comment_and_verify'] as $k =>$v) {
                    $res['data'][$key]['comment_and_verify'][$k]['users'] = User::where(['user_id' => $v['user_id']])->first();
                    $res['data'][$key]['comments'][$k] = $res['data'][$key]['comment_and_verify'][$k];
                }
                unset($res['data'][$key]['comment_and_verify']);

            }

            $rData['total'] = $res['total'];
            $rData['per_page'] = $res['per_page'];
            $rData['current_page'] = $res['current_page'];
            $rData['last_page'] = $res['last_page'];
            $rData['from'] = $res['from'];
            $rData['to'] = $res['to'];
            $rData['list'] = $res['data'];
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$rData,
            );
        }else{
            if($page ==1){
                $offset = 0;
                $step =$pageNum-count($hotThreadId);
            }else{
                $offset = ($page-1)*$pageNum-count($hotThreadId);
                $step =$pageNum;
            }
            $result = $thread
                ->Where(function($query)use($typeId,$userId){
                    $query->where(['user_id'=>$userId,"type_id"=>$typeId])
                          ->orwhere(['isverify'=>1,"type_id"=>$typeId]);
                })
                ->whereNotIn('id',$hotThreadId)
                ->whereNotIn('id', function($query) use($typeId){
                    $query->select('id')
                        ->from('bbs_threads')
                        ->where(['isinside'=>1,'istop'=>1,'type_id'=>$typeId]);
                })

                ->with('users')
                ->with('commentAndVerify')
                ->orderByRaw('created_at DESC')
                ->offset($offset)
                ->limit($step)
                ->get()
                ->toArray();
            if($page == 1){
                $data['list'] = array_merge($hotThread,$result);
            }else{
                $data['list'] = $result;
            }
            foreach ($data['list'] as $key => $value){
                $data['list'][$key]['comments']=[];
                foreach ($value['comment_and_verify'] as $k =>$v) {

                    $data['list'][$key]['comment_and_verify'][$k]['users'] = User::where(['user_id' => $v['user_id']])->first();
                    $data['list'][$key]['comments'][$k] = $data['list'][$key]['comment_and_verify'][$k];

                }
                unset($data['list'][$key]['comment_and_verify']);
            }
            $rData['list'] = $data['list'];
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

    /**
     *
     *获取帖子的详情
     * @
     *
     * @JsonRpcMethod
     */
    public  function getBbsThreadDetail($params){
        $userId = $this->userId;
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

        $thread =  new Thread(['userId'=>$userId]);
        $id = $params->id;
        $thread_info =  $thread->where(['isverify'=>1,'id'=>$id])
               ->orWhere(function($query)use($userId,$id){
                   $query->where(['user_id'=>$userId,'id'=>$id]);
               })
            ->with('users')
            ->with('commentAndVerify')
            ->first();

        if($thread_info) {
            $thread_info->comments = $thread_info->commentAndVerify;
            unset($thread_info->commentAndVerify);
            //view +1
            Thread::where(['id' => $params->id])->update(['views' => $thread_info->views + 1]);
            //dd($thread_info);
            if (!empty($params->fromPm)) {
                Pm::where(['id' => $params->fromPm])->update(['isread' => 1]);
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $thread_info,
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
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res =Thread::where(['istop'=>1,'isinside'=>1,'isverify'=>1,'type_id'=>$params->id])
            ->with('users')
            ->paginate($pageNum)
            ->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );
    }



}

