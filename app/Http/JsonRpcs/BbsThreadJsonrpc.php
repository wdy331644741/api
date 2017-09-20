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
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        //自定义分页  查找本周1条view最多的帖子  剔除 管理员发的置顶贴

        $mondayTime = date("Y-m-d",strtotime("-1 week Monday"));

        $thread = new Thread(['userId'=>$userId]);
        $hotThreadId = [];
        $hotThread = $thread->select("bbs_threads.id as id","bbs_threads.user_id","content","views","comment_num","isgreat","ishot","title","bbs_threads.created_at","bbs_threads.updated_at")
            ->whereNotIn('bbs_threads.id', function($query) use($typeId){
            $query->select('id')
                ->from('bbs_threads')
                ->where(['bbs_threads.istop'=>1,'bbs_threads.type_id'=>$typeId]);

            })
            ->where('bbs_threads.created_at','>',$mondayTime)
            ->where(['bbs_threads.isverify'=>1,'bbs_threads.type_id'=>$typeId])
            ->orWhere(function($query)use($typeId,$userId){
                $query->where(['bbs_threads.user_id'=>$userId,"bbs_threads.type_id"=>$typeId]);
            })

            ->join("bbs_users",function($join){
                $join->on("bbs_users.user_id","=","bbs_threads.user_id");

            })
            ->with("user")
            ->with("commentAndVerify")

            ->orderByRaw('views DESC')
            ->offset(0)
            ->limit(10)
            ->orderByRaw('bbs_threads.updated_at DESC')
            ->get()
            ->toArray();
        foreach ($hotThread as $key=>$value){
            $hotThreadId[] = $value['id'];
        }

        $res = $thread->select("bbs_threads.id as id","bbs_threads.user_id","content","views","comment_num","isgreat","ishot","title","bbs_threads.created_at","bbs_threads.updated_at")
            ->where(function($query)use($typeId,$userId) {
                $query->where(['bbs_threads.isverify'=>1,'bbs_threads.type_id'=>$typeId])
                    ->orWhere(function($query)use($typeId,$userId){
                        $query->where(['bbs_threads.user_id'=>$userId,"bbs_threads.type_id"=>$typeId]);
                    });
            })
            ->whereNotIn('bbs_threads.id', function($query) use($typeId,$hotThreadId){
                $query->select('id')
                    ->from('bbs_threads')
                    ->where(['istop'=>1,'type_id'=>$typeId])
                    ->orwhereIn('bbs_threads.id',$hotThreadId);
            })
            ->Join("bbs_users",function($join){
                $join->on("bbs_users.user_id","=","bbs_threads.user_id");

            })
            ->with('user')
            ->with("commentAndVerify")
            ->orderByRaw('bbs_threads.updated_at DESC')

            ->paginate($pageNum)
            ->toArray();

        if(empty($hotThread)){

            foreach ($res['data'] as $key => $value){
                $res['data'][$key]['comments']=[];
                foreach ($value['comment_and_verify'] as $k =>$v) {
                    $res['data'][$key]['comment_and_verify'][$k]['user'] = User::where(['user_id' => $v['user_id']])->first();
                    $res['data'][$key]['comments'][$k] = $res['data'][$key]['comment_and_verify'][$k];
                    if($k >=1){
                        break;
                    }
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

            $result = $thread->select("bbs_threads.id as id","bbs_threads.user_id","content","views","comment_num","isgreat","ishot","title","bbs_threads.created_at","bbs_threads.updated_at")

                ->where(function($query)use($typeId,$userId) {
                    $query->where(['bbs_threads.isverify'=>1,'bbs_threads.type_id'=>$typeId])
                        ->orWhere(function($query)use($typeId,$userId){
                            $query->where(['bbs_threads.user_id'=>$userId,"bbs_threads.type_id"=>$typeId]);
                        });
                })
                ->whereNotIn('bbs_threads.id', function($query) use($typeId,$hotThreadId){
                    $query->select('id')
                        ->from('bbs_threads')
                        ->where(['istop'=>1,'type_id'=>$typeId])
                        ->orwhereIn('id',$hotThreadId);
                })
                ->join("bbs_users",function($join){
                    $join->on("bbs_users.user_id","=","bbs_threads.user_id");

                })
                ->with('user')
                ->with('commentAndVerify')
                ->orderByRaw('bbs_threads.updated_at DESC')
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

                    $data['list'][$key]['comment_and_verify'][$k]['user'] = User::where(['user_id' => $v['user_id']])->first();
                    $data['list'][$key]['comments'][$k] = $data['list'][$key]['comment_and_verify'][$k];
                    if($k>=1){
                        break;
                    }

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
            throw new OmgException(OmgException::DATA_ERROR);

        }

        $thread =  new Thread(['userId'=>$userId]);
        $id = $params->id;
        $thread_info =  $thread->select("id","content","views","url","comment_num","type_id","user_id","isgreat","ishot","title")
            ->where(['isverify'=>1,'id'=>$id])
               ->orWhere(function($query)use($userId,$id){
                   $query->where(['user_id'=>$userId,'id'=>$id]);
               })
            ->with('user')
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
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $thread_info,
            );
        }else{
                throw new OmgException(OmgException::DATA_ERROR);
        }


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
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res =Thread::select("id","cover","title","type_id","url")
            ->where(['istop'=>1,'isverify'=>1,'type_id'=>$params->id])
            //->with('user')
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );
    }



}

