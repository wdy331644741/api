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
use App\Service\NetEastCheckService;
use App\Service\Attributes;
use App\Service\BbsSendAwardService;




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
    private $bbsDayThreadOneTaskFinshAward = 800;
    private $bbsDayThreadFiveTaskFinshAward = 2500;
    private $bbsDayCommentOneTaskFinshAward = 500;
    private $bbsDayCommentFiveTaskFinshAward = 1500;
    private $bbsDayAllTaskFinshAward = 1000;
    private $bbsAchieveThreadTenTaskFinshAward = 5000;
    private $bbsAchieveCommentFiftyTaskFinshAward = 5000;
    private $bbsAchieveImgOrNameTaskFinshAward = 500;
    private $bbsSumAward = 800+2500+500+1500+1000+5000+5000+500;

    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
        $this->userId =1111;
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
    public function updateBbsUserHeadimg($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($params->headImg)){
            throw new OmgException(OmgException::DATA_ERROR);
        }

        //更新
        $res = User::where(['user_id' => $this->userId])->update(['head_img'=>$params->headImg]);

        if ($res) {
            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->updateImgOrName();
            $user = array(
                'user_id' => $this->userId,
                'head_img' => $params->headImg,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $user
            );
        } else {
            throw new OmgException(OmgException::DATA_ERROR);

        }

    }

    /**
     *  用户更改昵称
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserNickname($param){
        //审核名称  禁用 网利
        $param->nickname = trim(str_replace(" ","",$param->nickname));

        preg_match('/(网利)/', $param->nickname, $matches);
        if($matches){
            throw new OmgException(OmgException::NAME_IS_ALIVE);
        }
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($param->nickname)){
            throw new OmgException(OmgException::NICKNAME_IS_NULL);
        }
        $validator = Validator::make(get_object_vars($param), [
            'nickname'=>'required|max:10',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::NICKNAME_ERROR);
        }


        $users = User::where(['nickname' => $param->nickname])->whereNotIn('user_id', ["$this->userId"])->first();

        if (!$users) {
            User::where(['user_id' => $this->userId])->update(['nickname' => $param->nickname]);

            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->updateImgOrName();
            $userInfo = array(
                'user_id' => $this->userId,
                'nickName' => $param->nickname,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $userInfo
            );


        }else{
            throw new OmgException(OmgException::NICKNAME_REPEAT);
        }
    }




    /**
     *  用户发布帖子
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishThread($params){

        $threadTimeLimit = Redis::GET('threadTimeLimit_'.$this->userId);
        //
        if($threadTimeLimit){
            throw new OmgException(OmgException::API_BUSY);
        }else{
            Redis::SET('threadTimeLimit_'.$this->userId,"1");
            $timeLimit = Config::get('bbsConfig')['threadPublishTimeLimit'];
            Redis::PEXPIRE('threadTimeLimit_'.$this->userId,$timeLimit);
        }

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        //发帖数量限制

        $threadNum = Attributes::getNumberByDay($this->userId,"bbs_user_thread_nums");

        if($threadNum >= Config::get('bbsConfig')['threadPublishMax']){
            throw new OmgException(OmgException::THREAD_LIMIT);
        }

        $validator = Validator::make(get_object_vars($params), [
            'type_id'=>'required|exists:bbs_thread_sections,id',
            'title'=>'required',
            'content'=>'required|max:500',
        ]);

        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        //发帖等级限制

        $publishLimit = GlobalConfig::where(['key'=>'vip_level'])->first();
//        if($this->userInfo['level']<= $publishLimit['val']){
//            throw new OmgException(OmgException::RIGHT_ERROR);
//        }
        //拉黑限制
        $bbsUserInfo = User::where(['user_id'=>$this->userId])->first();
        if($bbsUserInfo->isblack==1){
            throw new OmgException(OmgException::RIGHT_ERROR);
        };
        $inParam = array(
            'dataId'=>time(),//设置为时间戳
            'content' => $params->content.$params->title,

        );
        $netCheck = new NetEastCheckService($inParam);
        $res = $netCheck->textCheck();
        if($res['code'] =='200'){
            switch ($res['result']['action']){
                case 0 ://审核通过
                    $verifyResult = 1;
                    $verifyMessage = '发贴成功';
                    break;
                case 1 ://有嫌疑
                    $verifyResult = 0;
                    $verifyMessage = '您的发贴已提交审核';
                    break;
                case 2 ://审核未通过
                   throw new OmgException(OmgException::THREAD_ERROR);
            }
        }else{
            $verifyResult= 0;
            $verifyMessage = '您的发贴已提交审核';
        }

        $thread = new Thread();
        $thread->user_id = $this->userId;
        $thread->type_id = $params->type_id;
        $thread->title = isset($params->title) ? $params->title : NULL;
        $thread->content = $params->content;
        $thread->istop =  0;
        $thread->isverify = $verifyResult;
        $thread->verify_time = date('Y-m-d H:i:s');

        if($verifyResult ==0){
            $thread->verify_label =isset($res["result"]["labels"])?json_encode($res["result"]["labels"]):"";
        }
        $thread->save();
        if($verifyResult ==1){
            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->threadAward();
        }
        Attributes::incrementByDay($this->userId,"bbs_user_thread_nums");

        $message = $verifyMessage;


        if($thread->id){
            return array(
                'code' => 0,
                'message' => $message,
                'data' => Thread::where(['id'=>$thread->id])->first()
            );
        }else{
            throw new OmgException(OmgException::API_ILLEGAL);
        }

    }
    /**
     *  用户发表评论
     *
     * @JsonRpcMethod
     */
    public  function BbsPublishComment($params){
        $commentTimeLimit = Redis::GET('commentTimeLimit_'.$this->userId);
        if($commentTimeLimit){
            throw new OmgException(OmgException::API_BUSY);
        }else{
            Redis::SET('commentTimeLimit_'.$this->userId,"1");
            $timeLimit = Config::get('bbsConfig')['commentPublishTimeLimit'];
            Redis::PEXPIRE('commentTimeLimit_'.$this->userId,$timeLimit);
        }
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $commentNum = Attributes::getNumberByDay($this->userId,"bbs_user_comment_nums");

        if($commentNum >= Config::get('bbsConfig')['commentPublishMax']){
            throw new OmgException(OmgException::COMMENT_LIMIT);
        }

        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_threads,id',
            'content'=>'required',
        ]);

        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $bbsUserInfo = User::where(['user_id'=>$this->userId])->first();
        if($bbsUserInfo->isblack==1){
            throw new OmgException(OmgException::RIGHT_ERROR);
        };//
        $inParam = array(
            'dataId'=>time(),//设置为时间戳
            'content' => $params->content,

        );

        $netCheck = new NetEastCheckService($inParam);
        $res = $netCheck->textCheck();
        if($res['code'] =='200'){
            switch ($res['result']['action']){
                case 0 ://审核通过
                    $verifyResult = 1;
                    $verifyMessage = '评论成功';
                    break;
                case 1 ://有嫌疑
                    $verifyResult = 0;
                    $verifyMessage = '您的评论已提交审核';
                    break;
                case 2 ://审核未通过
                    throw new OmgException(OmgException::COMMENT_ERROR);
            }
        }else{
            $verifyResult= 0;
            $verifyMessage = '您的评论已提交审核';
        }

        $comment = new Comment();
        $comment->user_id = $this->userId;
        $comment->tid = $params->id;
        $comment->content = $params->content;
        $comment->isverify = $verifyResult;
        if($verifyResult ==0){
            $comment->verify_label =isset($res["result"]["labels"])?json_encode($res["result"]["labels"]):"";
        }
        $comment->save();

        if($verifyResult ==1){

            Thread::where(['id'=>$params->id])->increment('comment_num');
            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->commentAward();
        }
        Attributes::incrementByDay($this->userId,"bbs_user_comment_nums");

        $message = $verifyMessage;

        if($comment->id){
            return array(
                'code' => 0,
                'message' => $message,
                'data' => $comment::where(['id'=>$comment->id])->first()
            );
        }else{
            throw new OmgException(OmgException::API_ILLEGAL);
        }


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
        $res = Pm::where(['user_id'=>$this->userId])
            ->with('fromUsers','threads','comments')
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
     *  全部删除用户消息
     *
     * @JsonRpcMethod
     */
    public function delBbsUserAllPm($params){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $deleted['num'] = Pm::where(['user_id'=>$this->userId])->delete();
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$deleted,
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
            $dayUserAward = Redis::GET($this->bbsDayTaskSumAwardKey);
            $achieveUserAward = Redis::GET($this->bbsAchieveTaskSumAwardKey);
            $restAward = $this->bbsSumAward -$dayUserAward-$achieveUserAward;
            $BbsUserInfo['restAward'] = $restAward;
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$BbsUserInfo
            );
        }else{
            $User = new User();
            $User->user_id = $this->userInfo['id'];
            $User->head_img = Config::get('headimg')['user'][1];//默认取第一个
            $User->phone = $this->userInfo['phone'];
            $User->isblack = 0;
            $User->isadmin = 0;
            $User->save();
            $res = $User::where(['nickname' => 'wl' . $User->id])->first();
            if($res){
                $randomArray = range ("a","z");
                $random = rand(0,25);
                $User::where(['id' => $User->id])->update(['nickname' => 'wl' . $User->id .$randomArray["$random"]]);
            }else {
                $User::where(['id' => $User->id])->update(['nickname' => 'wl' . $User->id]);
            }
            $BbsUserInfo = User::where(['user_id'=>$this->userId])->first();
            $dayUserAward = Redis::GET($this->bbsDayTaskSumAwardKey);
            $achieveUserAward = Redis::GET($this->bbsAchieveTaskSumAwardKey);
            $restAward = $this->bbsSumAward -$dayUserAward-$achieveUserAward;
            $BbsUserInfo['restAward'] = $restAward;
            return array(
                'code'=>0,
                'message'=>'success',
                'data'=>$BbsUserInfo
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
        $dayThreadTaskOne['description'] = "发布一次主题贴";
        $dayThreadTaskOne['taskType'] = "dayThreadOne";
        $dayThreadTaskOne['task'] = "day";
        $dayThreadTaskOne['taskMark'] = "dayThread";
        $dayThreadTaskOne['award'] = "奖励".$this->bbsDayThreadOneTaskFinshAward."体验金";
        $dayThreadTaskOne['current'] = $threadCount;
        $dayThreadTaskOne['finish'] =$this->bbsDayThreadOneTaskFinsh;
        $dayThreadTaskOne['isAward'] = $dayThreadTargetOne;
        $dayThreadTaskOne['icon'] = env('APP_URL')."/images/bbs/icon_comment.png";
        $dayThreadTaskFive['description'] = "发布五次主题贴";
        $dayThreadTaskFive['taskType'] = "dayThreadFive";
        $dayThreadTaskFive['task'] = "day";
        $dayThreadTaskFive['taskMark'] = "dayThread";
        $dayThreadTaskFive['award'] = "奖励".$this->bbsDayThreadFiveTaskFinshAward."体验金";
        $dayThreadTaskFive['current'] = $threadCount;
        $dayThreadTaskFive['finish'] =$this->bbsDayThreadFiveTaskFinsh;
        $dayThreadTaskFive['isAward'] = $dayThreadTargetFive;
        $dayThreadTaskFive['icon'] = env('APP_URL')."/images/bbs/icon_comment.png";
        //每日任务：评论 1次 dayCommentOne  5次 dayCommentFive
        $commentCount = Comment::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
        //是否领过奖
        $dayCommentTargetOne =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentOne','user_id'=> $this->userId])->count();
        $dayCommentTargetFive = Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayCommentFive','user_id'=> $this->userId])->count();
        $dayCommentTaskOne['description'] = "发布一次评论";
        $dayCommentTaskOne['taskType'] = "dayCommentOne";
        $dayCommentTaskOne['task'] = "day";
        $dayCommentTaskOne['taskMark'] = "dayComment";
        $dayCommentTaskOne['award'] = "奖励".$this->bbsDayCommentOneTaskFinshAward."体验金";
        $dayCommentTaskOne['current'] = $commentCount;
        $dayCommentTaskOne['finish'] =$this->bbsDayCommentOneTaskFinsh;
        $dayCommentTaskOne['isAward'] = $dayCommentTargetOne;
        $dayCommentTaskOne['icon'] = env('APP_URL')."/images/bbs/icon_comment.png";
        $dayCommentTaskFive['description'] = "发布五次评论";
        $dayCommentTaskFive['taskType'] = "dayCommentFive";
        $dayCommentTaskFive['task'] = "day";
        $dayCommentTaskFive['taskMark'] = "dayComment";
        $dayCommentTaskFive['award'] = "奖励".$this->bbsDayCommentFiveTaskFinshAward."体验金";
        $dayCommentTaskFive['current'] = $commentCount;
        $dayCommentTaskFive['finish'] =$this->bbsDayCommentFiveTaskFinsh;
        $dayCommentTaskFive['isAward'] = $dayCommentTargetFive;
        $dayCommentTaskFive['icon'] = env('APP_URL')."/images/bbs/icon_comment.png";
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
        $dayAllTask['icon'] = env('APP_URL')."/images/bbs/icon_comment.png";
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
        $achieveThreadTenTask['icon'] = env('APP_URL')."/images/bbs/icon_post.png";
        //评论50 achieveCommentFifty
        $achieveCommentCount = Comment::where(['isverify'=>1,'user_id'=>$this->userId])->count();
        $achieveCommentFiftyCount = Task::where(['task_type'=>'achieveCommentFifty','user_id'=> $this->userId])->count();
        $achieveCommentFiftyTask['description'] = "累计评论达到五十次";
        $achieveCommentFiftyTask['taskType'] = "achieveCommentFifty";
        $achieveCommentFiftyTask['task'] = "achieve";
        $achieveCommentFiftyTask['taskMark'] = "achieveComment";
        $achieveCommentFiftyTask['award'] = "奖励".$this->bbsAchieveCommentFiftyTaskFinshAward."体验金";
        $achieveCommentFiftyTask['current'] = $achieveCommentCount;
        $achieveCommentFiftyTask['finish'] =$this->bbsAchieveCommentFiftyTaskFinsh;
        $achieveCommentFiftyTask['isAward'] = $achieveCommentFiftyCount;
        $achieveCommentFiftyTask['icon'] = env('APP_URL')."/images/bbs/icon_post.png";
        //上传头像及修改昵称  achieveUpdateImgOrName

        //数据库再次确认

        $achieveUpdateImgOrNameCount = Task::where(['task_type'=>'achieveUpdateImgOrName','user_id'=> $this->userId])->count();
        $achieveUpdateImgOrNameTask['description'] = "上传头像及修改昵称";
        $achieveUpdateImgOrNameTask['taskType'] = "achieveUpdateImgOrName";
        $achieveUpdateImgOrNameTask['task'] = "achieve";
        $achieveUpdateImgOrNameTask['taskMark'] = "achieveCommon";
        $achieveUpdateImgOrNameTask['award'] = "奖励".$this->bbsAchieveImgOrNameTaskFinshAward."体验金";
        $achieveUpdateImgOrNameTask['current'] = $achieveUpdateImgOrNameCount;
        $achieveUpdateImgOrNameTask['finish'] =1;
        $achieveUpdateImgOrNameTask['isAward'] = $achieveUpdateImgOrNameCount;
        $achieveUpdateImgOrNameTask['icon'] = env('APP_URL')."/images/bbs/icon_post.png";
        $res = [
            [
                "title"=>"每日任务",
                "task"=>"day",
                "list"=>[$dayThreadTaskOne,$dayThreadTaskFive,$dayCommentTaskOne,$dayCommentTaskFive,$dayAllTask]
            ],
            [
                "title"=>"成就任务",
                "task"=>"achieve",
                "list"=>[$achieveThreadTenTask,$achieveCommentFiftyTask,$achieveUpdateImgOrNameTask]
            ]

        ];
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
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
                     $task->task_type = 'dayCommentFive';
                     $task->award = $this->bbsDayCommentFiveTaskFinshAward;
                     $task->award_time = date("Y-m-d H:i:s",time());
                     $task->save();
                     return array(
                         'code' => 0,
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
                 }
                 break;
             case "dayAllTask":
                 //是否领过奖
                 $alisa = "task_everyday_all";
                 $dayAllTask =  Task::where('award_time','>',$nowTime)->where(['task_type'=>'dayAllTask','user_id'=> $this->userId])->count();
                 if($dayAllTask){
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
                 }
                 break;
             case "achieveThreadTen":
                 //是否领过奖
                 $alisa = "task_achieve_thread_ten";
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
                     Redis::INCRBY($this->bbsAchieveTaskSumAwardKey,$this->bbsAchieveThreadTenTaskFinshAward);
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
                 }
                 break;
             case "achieveCommentFifty":
                 //是否领过奖
                 $alisa = "task_achieve_comment_fifty";
                 $achieveCommentFiftyTask =  Task::where(['task_type'=>'achieveCommentFifty','user_id'=> $this->userId])->count();
                 if($achieveCommentFiftyTask){
                     throw new OmgException(OmgException::MALL_IS_HAS);
                 }
                 $achieveCommentFiftyTaskCount = Comment::where(['isverify'=>1,'user_id'=>$this->userId])->count();
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
                 }
                 break;
             case "achieveUpdateImgOrName":
                 //是否领过奖
                 $alisa = "task_achieve_imgOrName";
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
                         'message' => '领取成功',
                         'data' => $sendData[0]
                     );
                 }else{
                     //失败
                     throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
                 }
                 break;
             default:
                 throw new OmgException(OmgException::DATA_ERROR);
                 break;
         }

     }

}

