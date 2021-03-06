<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Bbs\Pm;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadCollection;
use App\Models\Bbs\User;
use Lib\JsonRpcClient;
use Illuminate\Pagination\Paginator;
use Validator;
use App\Models\bbs\ThreadRecord;
use App\Models\Bbs\GlobalConfig;
use Cache;


class BbsThreadJsonRpc extends JsonRpc
{


    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
    }
    /**
     *  根据板块获取帖子列表
     *  全部排序
     * @排序规则：阅读量倒叙
     * @param id 区域id
     * @param pageNum  每页条数
     * @param page 当前页
     * @JsonRpcMethod
     */

    public  function getBbsThreadAllList($params){

        $userId = $this->userId;

        $validator = Validator::make(get_object_vars($params), [
            'id' => 'required|exists:bbs_thread_sections,id',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        //判断缓存是否过期
        if(Cache::has("getBbsThreadAllList_".$typeId."_".$page)){
            $json = Cache::get("getBbsThreadAllList_".$typeId."_".$page);
            return [
                'code'=>0,
                'message'=>'success',
                'data'=>json_decode($json,1)
            ];
        }
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        //自定义分页  查找一个月内view最多的帖子  剔除 管理员发的置顶贴
        //$monthTime = date("Y-m-d", strtotime("-3 month"));
        $thread = new Thread(['userId' => $userId]);

        $res = $thread->select("id", "user_id", "content", "views", "comment_num", "isgreat", "ishot", "title","cover","isofficial","collection_num","zan_num", "created_at", "updated_at","video_code","is_new","is_special","new")
            ->where(['istop' => 0])
            ->where(['isverify' => 1, 'type_id' => $typeId])
            //->whereNotIn('id', [isset($commentThread[0]['id'])?$commentThread[0]['id']:"",isset($pvThread[0]['id'])?$pvThread[0]['id']:""])
            ->with('user')
            ->with('collection')
            ->with('zan')
            ->with('read')
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();

        //加入缓存
        Cache::put("getBbsThreadAllList_".$typeId."_".$page,json_encode($res),3);
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$res
        ];

    }

    /**
     * 根据板块获取帖子列表
     * 最热排序
     * @排序规则：先查询最热帖子，然后展示帖子大于等于临界值的帖子
     * @param id 区域id
     * @param pageNum  每页条数
     * @param page 当前页、
     * @JsonRpcMethod
     */
    public function getBbsThreadHotList($params)
    {
        $userId = $this->userId;
        $validator = Validator::make(get_object_vars($params), [
            'id' => 'required|exists:bbs_thread_sections,id',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $thread = new Thread(['userId' => $userId]);

        $orderViews = GlobalConfig::where(['key'=>'orderViews'])->value('val');
        $orderComments = GlobalConfig::where(['key'=>'orderComments'])->value('val');
        $res = $thread->select("id", "user_id", "content", "views", "comment_num", "isgreat", "ishot", "title","cover","isofficial","collection_num","zan_num", "created_at", "updated_at","video_code","is_new","is_special","new")
            ->where(['isverify' => 1, 'type_id' => $typeId,'istop'=>0])

            ->Where(function($query)use($orderViews,$orderComments){
                $query->where('views','>=',$orderViews)
                    ->orWhere('comment_num','>=',$orderComments)
                    ->orwhere('ishot',1);
            })
            ->with('user')
            ->with('collection')
            ->with('zan')
            ->with('read')
            ->orderByRaw('ishot DESC,created_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$res
        ];


    }
    /**
     *
     *获取精华帖子的详情
     * @排序规则 ：按阅读量倒叙
     * @JsonRpcMethod
     */
    public function getBbsThreadGreatList($params){
        $userId = $this->userId;
        $validator = Validator::make(get_object_vars($params), [
            'id' => 'required|exists:bbs_thread_sections,id',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        //自定义分页  查找本周1条view最多的帖子  剔除 管理员发的置顶贴

        $thread = new Thread(['userId' => $userId]);
        $res = $thread->select("id", "user_id", "content", "views", "comment_num", "isgreat", "ishot", "title","cover","isofficial","collection_num","zan_num", "created_at", "updated_at","video_code","is_new","is_special","new")
            ->where(['istop' => 0,'isgreat'=>1])
            ->where(['isverify' => 1, 'type_id' => $typeId])
            ->with('user')
            ->with('collection')
            ->with('zan')
            ->with('read')
            ->orderByRaw('isgreat DESC,created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$res
        ];
    }

    /**
     *
     *获取最新帖子的详情
     * @按创建时间倒叙
     * @JsonRpcMethod
     */
    public function getBbsThreadLastList($params){
        $userId = $this->userId;
        $validator = Validator::make(get_object_vars($params), [
            'id' => 'required|exists:bbs_thread_sections,id',
        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $typeId = $params->id;
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $thread = new Thread(['userId' => $userId]);

        $res = $thread->select("id", "user_id", "content", "views", "comment_num", "isgreat", "ishot", "title","cover","isofficial","collection_num","zan_num", "created_at", "updated_at","video_code","is_new","is_special","new")
            ->where(['istop' => 0])
            ->where(['isverify' => 1, 'type_id' => $typeId])
            ->with('user')
            ->with('collection')
            ->with('zan')
            ->with('read')
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$res
        ];


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
        if($this->userId){
            ThreadRecord::firstOrCreate(['user_id' => $this->userId,'tid'=>$params->id],['user_id' => $this->userId,'tid'=>$params->id]);
        }
        $thread =  new Thread(['userId'=>$this->userId]);
        $id = $params->id;

        $thread_info =  $thread->select("id", "user_id", "content", "views", "comment_num", "isgreat", "ishot","cover", "title","isofficial","collection_num","zan_num", "created_at", "updated_at","video_code","is_new","is_special","new")

            ->where(['isverify'=>1,'id'=>$id])
            ->orWhere(function($query)use($userId,$id){
                $query->where(['user_id'=>$userId,'id'=>$id]);
            })
            ->with('user')
            ->with('collection')
            ->with('zan')
            ->with('read')
            ->first();

        if($thread_info) {
            //view +1
            Thread::where(['id' => $params->id])->increment('views');

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
     * 获取置顶帖子列表
     * @JsonRpcMethod
     */
    public function getBbsThreadTopList($params){
        $pageNum = isset($params->pageNum) ? $params->pageNum : 3;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res =Thread::select("id","cover","title","type_id","url","isgreat", "ishot","isofficial","video_code","is_new","is_special","new","created_at","updated_at")
            ->where(['istop'=>1,'isverify'=>1,'type_id'=>$params->type_id])
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );
    }
    /**
     * 获取置顶帖子列表
     * @JsonRpcMethod
     */
    public function delBbsUserThread($params)
    {
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'ids' => 'required',

        ]);

        if ($validator->fails()) {
            throw new OmgException(OmgException::DATA_ERROR);
        }

        $deleted['num'] = Thread::where(['user_id' => $this->userId])->whereIn('id', $params->ids)->delete();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $deleted,
        );
    }
    /*获取置顶的第一条帖子*/
    private  function getBbsThreadTopOne($type_id){
        $res =Thread::select("id","cover","title","type_id","url","created_at","updated_at",'')
            ->where(['istop'=>1,'isverify'=>1,'type_id'=>$type_id])
            ->orderByRaw('created_at DESC')
            ->first();
        return $res;
    }
}
