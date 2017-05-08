<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Bbs\Task;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\User;
use App\Models\Bbs\Pm;
use App\Models\Bbs\ReplyConfig;
use Lib\JsonRpcClient;
use Validator;
use Config;
use Illuminate\Pagination\Paginator;
use App\Service\Func;
use App\Models\Bbs\GlobalConfig;
use Illuminate\Support\Facades\Redis;
use App\Service\SendAward;




class BbsUserJsonRpc extends JsonRpc {
    private $userId;
    private $userInfo;
    private $achieveUserImgOrNameKey = '';
    private $bbsDayTaskSumAwardKey = '';
    private $bbsAchieveTaskSumAwardKey = '';
    private $bbsDayThreadOneTaskFinsh = 1;
    private $bbsDayThreadFiveTaskFinsh = 5;
    private $bbsDayCommentOneTaskFinsh = 1;
    private $bbsDayCommentFiveTaskFinsh = 5;
    private $bbsAchieveThreadTenTaskFinsh = 10;
    private $bbsAchieveCommentFiftyTaskFinsh = 50;
    private $bbsDayThreadOneTaskFinshAward = 200;
    private $bbsDayThreadFiveTaskFinshAward = 500;
    private $bbsDayCommentOneTaskFinshAward = 100;
    private $bbsDayCommentFiveTaskFinshAward = 300;
    private $bbsDayAllTaskFinshAward = 800;
    private $bbsAchieveThreadTenTaskFinshAward = 5000;
    private $bbsAchieveCommentFiftyTaskFinshAward = 5000;
    private $bbsAchieveImgOrNameTaskFinshAward = 5000;
    private $bbsSumAward = 200+500+100+300+800+5000+5000+5000;

    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
        $this->userInfo = Func::getUserBasicInfo($userId);
        $this->bbsDayTaskSumAwardKey = 'bbsDayTaskSum_'.date('Y-m-d',time()).'_'.$this->userId;
        $this->bbsAchieveTaskSumAwardKey = 'bbsAchieveTaskSum_'.$this->userId;
        $this->achieveUserImgOrNameKey = 'achieveUserImgOrName';



    }

    /**
     *  用户上传头像
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserHeadimg($param){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        if(!isset(Config::get('headimg')['user'][$param->head_img])){
            throw new OmgException(OmgException::DATA_ERROR);
        }

        $headImg = Config::get('headimg')['user'][$param->head_img];
        $user = User::where(['user_id' => $this->userId])->first();
        if($user){
            //更新
            $res = User::where(['user_id' => $this->userId])->update(['head_img'=>$headImg]);
        }else{
            //新建用户
            $newUser = new User();
            $newUser->user_id = $this->userId;
            $newUser->head_img = $headImg;
            $newUser->phone = $this->userInfo['phone'];
            $newUser->nickname = $this->userInfo['username'];
            $newUser->isblack = 0;
            $newUser->isadmin = 0;
            $res = $newUser->save();
        }
        if ($res) {
            //成就任务redis setbit  key achieveUserImgOrName  offset user_id
            Redis::setBit($this->achieveUserImgOrNameKey,$this->userId,1);
            $user = array(
                'user_id' => $this->userId,
                'head_img' => $headImg,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $user
            );
        } else {
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '上传头像失败'
            );

        }

    }

    /**
     *  用户更改昵称
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserNickname($param){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $user = User::where(['user_id' => $this->userId])->first();
        if($user){
            //更新
            $userNickname = User::where(['nickname'=>$param->nickname]);
            if(!$userNickname) {
                $res = User::where(['user_id' => $this->userId])->update(['nickname' => $param->nickname]);
            }else{
                return array(
                    'code' => -1,
                    'message' => 'fail',
                    'data' => '昵称重复'
                );
            }
        }else{
            //新建用户
            $newUser = new User();
            $newUser->user_id = $this->userId;
            $newUser->phone = $this->userInfo['phone'];
            $newUser->nickname = $this->$param->nickname;
            $newUser->isblack = 0;
            $newUser->isadmin = 0;
            $res = $newUser->save();

        }
        if ($res) {
            //成就任务redis setbit  key achieveUserImgOrName  offset user_id
            Redis::setBit($this->achieveUserImgOrNameKey,$this->userId,1);
            $user = array(
                'user_id' => $this->userId,
                'head_img' => $param->nickname,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $user
            );
        } else {
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '更改昵称失败'
            );
        }

    }

    /**
     *  用户发布帖子
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishThread($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'type_id'=>'required|exists:bbs_thread_sections,id',
            'title'=>'required',
            'content'=>'required|max:500',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        //发帖等级限制
        $publishLimit = GlobalConfig::where(['key'=>'vip_level'])->first();
        if($this->userInfo['level']<= $publishLimit['val']){
            return array(
                'code' => 2,
                'message' => 'fail',
                'data' => $publishLimit['remark']
            );
        }

        $thread = new Thread();
        $thread->user_id = $this->userId;
        $thread->type_id = $params->type_id;
        $thread->title = isset($params->title) ? $params->title : NULL;
        $thread->content = $params->content;
        $thread->istop =  0;
        $thread->isverify = Config::get('bbsConfig')['threadVerify']?0:1;
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
     *  用户发表评论
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishComment($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_threads,id',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => $validator->errors()->first()
            );
        }
        $comment = new Comment();
        $comment->user_id = $this->userId;
        $comment->tid = $params->id;
        $comment->content = $params->content;
        $comment->isverify = Config::get('bbsConfig')['commentVerify']?0:1;
        $comment->save();
        if($comment->id){
            $thread_info = Thread::where(['id'=>$params->id])->first();
            Thread::where(['id'=>$params->id])->update(['comment_num'=>$thread_info['comment_num']+1]);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $comment::where(['id'=>$comment->id])->first()
            );
        }else{
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => 'Database Error'
            );
        }


    }
    private function commentUserPm($pmUserId,$from_user_id,$tid,$cid,$content=""){
        $pms = new Pm();
        $pms->user_id = $pmUserId;
        $pms->from_user_id = $from_user_id;
        $pms->tid = $tid;
        $pms->cid = $cid;
        $pms->content = $content;
        $pms->isread = 0;
        $pms->save();
    }
    /**
     *  获取用户发表的帖子 分页
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserThread($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = Thread::where(['isverify'=>1,'user_id'=>$this->userId])
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
     *  获取用户发表的评论 分页
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserComment($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = Comment::select('id','from_user_id', 'tid', 'content', 'isverify', 'verify_time', 'created_at',  'updated_at')
            ->where(['isverify'=>1,'user_id'=>$this->userId])
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
     *  获取用户消息 分页
     *
     * @JsonRpcMethod
     */
    public function getBbsUserPm($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res = Pm::where(['isread'=>0,'user_id'=>$this->userId])
            ->with('fromUsers','threads','comments')
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();
        foreach ($res['data'] as $key=>$value){
            if($value['from_user_id'] ==0){//系统管理员回复
                $replyInfo =ReplyConfig::where(['id'=>$value['cid']])->first()->toArray();
                $res['data'][$key]['del_reason'] =$replyInfo['description'];
                unset($res['data'][$key]['from_users']);
                unset($res['data'][$key]['threads']);
                unset($res['data'][$key]['comments']);
            }
        }
        //Pm::where(['isread'=>0,'user_id'=>$this->userId])->update(['isread'=>1]);
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
     *  获取用户消息条数 分页
     *
     * @JsonRpcMethod
     */
    public function getBbsUserCountPm($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $res['num'] = Pm::where(['isread'=>0,'user_id'=>$this->userId])
            ->count();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );


    }
    /**
     *  获取用户信息 分页
     *
     * @JsonRpcMethod
     */
    public function getBbsUserInfo($param){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $BbsUserInfo = User::where(['user_id'=>$this->userId])->first();
        //has Userinfo
        if($BbsUserInfo){
            $userInfos = $BbsUserInfo->toArray();
            $dayUserAward = Redis::GET($this->bbsDayTaskSumAwardKey);
            $achieveUserAward = Redis::GET($this->bbsAchieveTaskSumAwardKey);
            $restAward = $this->bbsSumAward -$dayUserAward-$achieveUserAward;

            $userInfos['restAward'] = $restAward;
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$userInfos
            );
        }else{
            $User = new User();
            $User->user_id = $this->userInfo['id'];
            $User->head_img = Config::get('headimg')['user'][1];//默认取第一个
            $User->phone = $this->userInfo['phone'];
            $User->nickname ='用户'.$this->userInfo['id'];
            $User->isblack = 0;
            $User->isadmin = 0;
            $User->save();
            $BbsUserInfo = User::where(['user_id'=>$this->userId])->first();
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$BbsUserInfo->toArray()
            );
        }

    }
    /**
     *  获取全部用户头像
     *
     * @JsonRpcMethod
     */
    public function getBbsUserAllHeadImg($param){
        $allHeadImg = Config::get('headimg')['user'];
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$allHeadImg
        );

    }
    /**
     *  查询用户状态
     *
     * @JsonRpcMethod
     */
    public function queryBbsUserTask($param){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $nowTime = date("Y-m-d",time());
        $threadCount = Thread::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
        //是否领过奖
        $dayThreadTargetOne =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayThreadOne','user_id'=> $this->userId])->count();
        $dayThreadTargetFive = Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayThreadFive','user_id'=> $this->userId])->count();
        //任务类型 每日任务  成就任务
        //每日任务：主题贴  1次 dayThreadOne  5次 dayThreadFive
        $dayThreadTaskOne['description'] = "当日发布一次主题贴";
        $dayThreadTaskOne['taskType'] = "dayThreadOne";
        $dayThreadTaskOne['task'] = "day";
        $dayThreadTaskOne['taskMark'] = "dayThread";
        $dayThreadTaskOne['award'] = "奖励".$this->bbsDayCommentOneTaskFinshAward."体验金";
        $dayThreadTaskOne['current'] = $threadCount;
        $dayThreadTaskOne['finish'] =$this->bbsDayThreadOneTaskFinsh;
        $dayThreadTaskOne['isAward'] = $dayThreadTargetOne;
        $dayThreadTaskFive['description'] = "当日发行五次次主题贴";
        $dayThreadTaskFive['taskType'] = "dayThreadFive";
        $dayThreadTaskFive['task'] = "day";
        $dayThreadTaskFive['taskMark'] = "dayThread";
        $dayThreadTaskFive['award'] = "奖励".$this->bbsDayThreadFiveTaskFinshAward."体验金";
        $dayThreadTaskFive['current'] = $threadCount;
        $dayThreadTaskFive['finish'] =$this->bbsDayThreadFiveTaskFinsh;
        $dayThreadTaskFive['isAward'] = $dayThreadTargetFive;
        //每日任务：评论 1次 dayCommentOne  5次 dayCommentFive
        $commentCount = Comment::where('created_at','<',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
        //是否领过奖
        $dayCommentTargetOne =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentOne','user_id'=> $this->userId])->count();
        $dayCommentTargetFive = Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentFive','user_id'=> $this->userId])->count();
        $dayCommentTaskOne['description'] = "当日发布一次";
        $dayCommentTaskOne['taskType'] = "dayCommentOne";
        $dayCommentTaskOne['task'] = "day";
        $dayCommentTaskOne['taskMark'] = "dayComment";
        $dayCommentTaskOne['award'] = "奖励".$this->bbsDayCommentOneTaskFinshAward."体验金";
        $dayCommentTaskOne['current'] = $commentCount;
        $dayCommentTaskOne['finish'] =$this->bbsDayCommentOneTaskFinsh;
        $dayCommentTaskOne['isAward'] = $dayCommentTargetOne;
        $dayCommentTaskFive['description'] = "当日发行五次次主题贴";
        $dayCommentTaskFive['taskType'] = "dayCommentFive";
        $dayCommentTaskFive['task'] = "day";
        $dayCommentTaskFive['taskMark'] = "dayComment";
        $dayCommentTaskFive['award'] = "奖励".$this->bbsDayCommentFiveTaskFinshAward."体验金";
        $dayCommentTaskFive['current'] = $commentCount;
        $dayCommentTaskFive['finish'] =$this->bbsDayCommentFiveTaskFinsh;
        $dayCommentTaskFive['isAward'] = $dayCommentTargetFive;
        //完成所有每日任务
        $dayAllTaskCount = Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayAllTask','user_id'=> $this->userId])->count();
        $dayAllTask['description'] = "完成所有每日任务";
        $dayAllTask['taskType'] = "dayAllTask";
        $dayAllTask['task'] = "day";
        $dayAllTask['taskMark'] = "dayAll";
        $dayAllTask['award'] = "奖励".$this->bbsDayAllTaskFinshAward."体验金";
        $dayAllTask['current'] = $dayThreadTargetOne+$dayThreadTargetFive+$dayCommentTargetOne+$dayCommentTargetFive;
        $dayAllTask['finish'] =4;
        $dayAllTask['isAward'] = $dayAllTaskCount;
        $res['day']['dayThreadOne'] = $dayThreadTaskOne;
        $res['day']['dayThreadFive'] = $dayThreadTaskFive;
        $res['day']['dayCommentOne'] = $dayCommentTaskOne;
        $res['day']['dayCommentFive'] = $dayCommentTaskFive;
        $res['day']['dayAllTask'] =$dayAllTask;
        //成就任务 发布10主题帖 achieveThreadTen
        $achieveThreadCount = Thread::where(['isverify'=>1,'user_id'=>$this->userId])->count();
        $achieveThreadTenCount = Task::where(['task_type'=>'achieveThreadTen','user_id'=> $this->userId])->count();
        $achieveThreadTenTask['description'] = "累计发布十次主题帖";
        $achieveThreadTenTask['taskType'] = "achieveThreadTen";
        $achieveThreadTenTask['task'] = "achieve";
        $achieveThreadTenTask['taskMark'] = "achieveThread";
        $achieveThreadTenTask['award'] = "奖励".$this->bbsAchieveThreadTenTaskFinshAward."体验金";
        $achieveThreadTenTask['current'] = $achieveThreadCount;
        $achieveThreadTenTask['finish'] =$this->bbsAchieveThreadTenTaskFinsh;
        $achieveThreadTenTask['isAward'] = $achieveThreadTenCount;
        //评论50 achieveCommentFifty
        $achieveCommentCount = Comment::where(['isverify'=>1,'user_id'=>$this->userId])->count();
        $achieveCommentFiftyCount = Task::where(['task_type'=>'achieveCommentFifty','user_id'=> $this->userId])->count();
        $achieveCommentFiftyTask['description'] = "累计评论达到五十次";
        $achieveCommentFiftyTask['taskType'] = "achieveCommentFifty";
        $achieveCommentFiftyTask['task'] = "achieve";
        $achieveCommentFiftyTask['taskMark'] = "achieveComment";
        $achieveCommentFiftyTask['award'] = "奖励.".$this->bbsAchieveCommentFiftyTaskFinshAward."体验金";
        $achieveCommentFiftyTask['current'] = $achieveCommentCount;
        $achieveCommentFiftyTask['finish'] =$this->bbsAchieveCommentFiftyTaskFinsh;
        $achieveCommentFiftyTask['isAward'] = $achieveCommentFiftyCount;
        //上传头像及修改昵称  achieveUpdateImgOrName
        $achieveUpdateImgOrName = Redis::getBit($this->achieveUserImgOrNameKey,$this->userId);
        $achieveUpdateImgOrNameCount = Task::where(['task_type'=>'achieveUpdateImgOrName','user_id'=> $this->userId])->count();
        $achieveUpdateImgOrNameTask['description'] = "上传头像及修改昵称";
        $achieveUpdateImgOrNameTask['taskType'] = "achieveUpdateImgOrName";
        $achieveUpdateImgOrNameTask['task'] = "achieve";
        $achieveUpdateImgOrNameTask['taskMark'] = "achieveCommon";
        $achieveUpdateImgOrNameTask['award'] = "奖励".$this->bbsAchieveImgOrNameTaskFinshAward."体验金";
        $achieveUpdateImgOrNameTask['current'] = $achieveUpdateImgOrName;
        $achieveUpdateImgOrNameTask['finish'] =1;
        $achieveUpdateImgOrNameTask['isAward'] = $achieveUpdateImgOrNameCount;
        $res['achieve']['achieveThreadTen'] =$achieveThreadTenTask;
        $res['achieve']['achieveCommentFifty'] =$achieveCommentFiftyTask;
        $res['achieve']['achieveUpdateImgOrName'] =$achieveUpdateImgOrNameTask;
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res
        );
    }

    /**
     *  获取用户发放体验金
     *
     * @JsonRpcMethod
     */
     public function BbsUserSendAward($param){

         if (empty($this->userId)) {
             throw  new OmgException(OmgException::NO_LOGIN);
         }
         $nowTime = date("Y:m:d",time());
         switch ($param->taskType){
             case "dayThreadOne":
                 //是否领过奖
                 $alisa = "task_everyday_thread_one";
                 $dayThreadTargetOne =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayThreadOne','user_id'=> $this->userId])->count();
                 if($dayThreadTargetOne){
                    throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $threadCount = Thread::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();

                 if($threadCount < $this->bbsDayThreadOneTaskFinsh){
                    throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券

                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 //dd($sendData);exit;
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsDayTaskSumAwardKey,$this->bbsDayThreadOneTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'dayThreadOne';
                     $task->award = $this->bbsDayThreadOneTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                break;
             case "dayThreadFive":
                 //是否领过奖
                 $alisa = "task_everyday_thread_five";
                 $dayThreadTargetFive =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayThreadFive','user_id'=> $this->userId])->count();
                 if($dayThreadTargetFive){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $threadCount = Thread::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
                 if($threadCount < $this->bbsDayThreadFiveTaskFinsh){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsDayTaskSumAwardKey,$this->bbsDayThreadFiveTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'dayThreadFive';
                     $task->award = $this->bbsDayThreadFiveTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "dayCommentOne":
                 //是否领过奖
                 $alisa = "task_everyday_comment_one";
                 $dayCommentTargetFive =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentOne','user_id'=> $this->userId])->count();
                 if($dayCommentTargetFive){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $commentCount = Comment::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
                 if($commentCount < $this->bbsDayCommentOneTaskFinsh){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsDayTaskSumAwardKey,$this->bbsDayCommentOneTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'dayCommentOne';
                     $task->award = $this->bbsDayCommentOneTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "dayCommentFive":
                 //是否领过奖
                 $alisa = "task_everyday_comment_five";
                 $dayThreadTargetFive =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentFive','user_id'=> $this->userId])->count();
                 if($dayThreadTargetFive){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $threadCount = Comment::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
                 if($threadCount < $this->bbsDayThreadFiveTaskFinsh){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsDayTaskSumAwardKey,$this->bbsDayCommentFiveTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'dayThreadFive';
                     $task->award = $this->bbsDayCommentFiveTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "dayAllTask":
                 //是否领过奖
                 $alisa = "task_everyday_all";
                 $dayAlltask =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayAllTask','user_id'=> $this->userId])->count();
                 if($dayAlltask){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $threadOneCount = Task::where('created_at','>',$nowTime)->where(['task_type'=>'dayThreadOne','user_id'=>$this->userId])->count();
                 $threadFiveCount = Task::where('created_at','>',$nowTime)->where(['task_type'=>'dayThreadFive','user_id'=>$this->userId])->count();
                 $commentOneCount = Task::where('created_at','>',$nowTime)->where(['task_type'=>'dayCommentOne','user_id'=>$this->userId])->count();
                 $commentFiveCount = Task::where('created_at','>',$nowTime)->where(['task_type'=>'dayCommentFive','user_id'=>$this->userId])->count();
                 if(!($threadOneCount &&$threadFiveCount&&$commentOneCount&&$commentFiveCount)){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsDayTaskSumAwardKey,$this->bbsDayAllTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'dayAllTask';
                     $task->award = $this->bbsDayAllTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "achieveThreadTen":
                 //是否领过奖
                 $alisa = "	task_achieve_thread_ten";
                 $achieveThreadTenTask =  Task::where(['task_type'=>'achieveThreadTen','user_id'=> $this->userId])->count();
                 if($achieveThreadTenTask){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $achieveThreadTenTaskCount = Thread::where(['isverify'=>1,'user_id'=>$this->userId])->count();
                 if($achieveThreadTenTaskCount < $this->bbsAchieveThreadTenTaskFinsh){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'achieveThreadTen';
                     $task->award = $this->bbsAchieveThreadTenTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "achieveCommentFifty":
                 //是否领过奖
                 $alisa = "	task_achieve_comment_fifty";
                 $achieveCommentFiftyTask =  Task::where(['task_type'=>'achieveCommentFifty','user_id'=> $this->userId])->count();
                 if($achieveCommentFiftyTask){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $achieveCommentFiftyTaskCount = Thread::where(['isverify'=>1,'user_id'=>$this->userId])->count();
                 if($achieveCommentFiftyTaskCount < $this->bbsAchieveCommentFiftyTaskFinsh){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsAchieveTaskSumAwardKey,$this->bbsAchieveCommentFiftyTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'achieveCommentFifty';
                     $task->award = $this->bbsAchieveCommentFiftyTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             case "achieveUpdateImgOrName":
                 //是否领过奖
                 $alisa = "	task_achieve_imgOrName";
                 $dayCommentTargetFive =  Task::where(['task_type'=>'achieveUpdateImgOrName','user_id'=> $this->userId])->count();
                 if($dayCommentTargetFive){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $commentCount = Redis::getBit($this->achieveUserImgOrNameKey,$this->userId);
                 if(!$commentCount){
                     throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
                 }
                 //发送代金券
                 $sendData = SendAward::ActiveSendAward($this->userId,$alisa);
                 //
                 if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
                     //成功
                     //信息入库
                     Redis::INCRBY($this->bbsAchieveTaskSumAwardKey,$this->bbsAchieveImgOrNameTaskFinshAward);
                     $task = new Task();
                     $task->user_id = $this->userId;
                     $task->task_type = 'achieveUpdateImgOrName';
                     $task->award = $this->bbsAchieveImgOrNameTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => 'success',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     return array(
                         'code' => -1,
                         'message' => isset($sendData['msg']) ? $sendData['msg'] : ""
                     );
                 }
                 break;
             default:
                 throw new OmgException(OmgException::DATA_ERROR);
                 break;
         }

     }

}

