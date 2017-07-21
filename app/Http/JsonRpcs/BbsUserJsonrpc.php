<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\Bbs\CommentZan;
use App\Models\Bbs\Task;
use App\Models\Bbs\Tasks;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Bbs\ThreadCollection;
use App\Models\Bbs\ThreadZan;
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
use App\Models\Bbs\CommentReply;




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
        $inParamText = array(
            'dataId'=>time(),//设置为时间戳
            'content' => $param->nickname,

        );
        $netCheck = new NetEastCheckService($inParamText);
        $res = $netCheck->userCheck();

        if($res['result']['code']!=0){
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

//        if($threadNum >= Config::get('bbsConfig')['threadPublishMax']){
//            throw new OmgException(OmgException::THREAD_LIMIT);
//        }

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
        //拉黑限制
        $bbsUserInfo = User::where(['user_id'=>$this->userId])->first();
        if($bbsUserInfo->isblack==1){
            throw new OmgException(OmgException::RIGHT_ERROR);
        };
        $inParamText = array(
            'dataId'=>time(),//设置为时间戳
            'content' => $params->content.$params->title,

        );

        $resTextCode = 1;
        $resImgCode = 1;
        $netCheck = new NetEastCheckService($inParamText);
        $resText = $netCheck->textCheck();

        if($resText['code'] =='200'){
            switch ($resText['result']['action']){
                case 0 ://审核通过
                    $resTextCode = 1;

                    break;
                case 1 ://有嫌疑
                    $resTextCode = 0;
                    $resLabel = $resText["result"]["labels"];
                    break;
                case 2 ://审核未通过
                    throw new OmgException(OmgException::THREAD_ERROR);
            }
        }else{
            $resTextCode= 0;

        }
        $picArrays=[];
        if(!empty($params->imgs)){
            foreach ($params->imgs as $key=> $value){
                $picArrays[$key]['name'] = $value;
                $picArrays[$key]['type'] = 1;
                $picArrays[$key]['data'] = $value;
            }
            $inParamImg = array(
                "images"=>json_encode($picArrays),
            );
            //dd($inParamImg);
            $imgCheck = new NetEastCheckService($inParamImg);
            $resImg = $imgCheck->imgCheck();

            if($resImg['code'] =='200'){

                $result = $resImg["result"];
                    // var_dump($array);
                foreach($result as $index => $image_ret){

                    $maxLevel=-1;
                    foreach($image_ret["labels"] as $index=>$label){
                        $maxLevel=$label["level"]>$maxLevel?$label["level"]:$maxLevel;
                    }
                    if($maxLevel==0){
                        $resImgCode = 1;
                    }else if($maxLevel==1){
                        $resImgCode = 0;

                    }else if($maxLevel==2){
                        throw new OmgException(OmgException::THREAD_ERROR);
                    }

                }
            }else{
                $resImgCode= 0;
            }
        }
        $resMaxCode = $resImgCode+$resTextCode;
        switch ($resMaxCode){
            case 0 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的发贴已提交审核';
                break;
            case 1 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的发贴已提交审核';
                break;
            case 2 ://审核未通过
                $verifyResult = 1;
                $verifyMessage = '发贴成功';
        }

        $thread = new Thread();
        $thread->user_id = $this->userId;
        $thread->type_id = $params->type_id;
        $thread->title = isset($params->title) ? $params->title : NULL;
        $thread->content = $params->content;
        $thread->istop =  0;
        $thread->isverify = $verifyResult;

        $thread->cover =  isset($params->imgs)?json_encode($params->imgs):"";
        $thread->verify_time = date('Y-m-d H:i:s');

        if($verifyResult ==0 ){
            $thread->verify_label =isset($res["result"]["labels"])?json_encode($res["result"]["labels"]):"";
        }
        $thread->save();
        if($verifyResult ==1){
            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->publishThreadAward();
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

//        if($commentNum >= Config::get('bbsConfig')['commentPublishMax']){
//            throw new OmgException(OmgException::COMMENT_LIMIT);
//        }

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
            //增加帖子评论数目
            Thread::where(['id'=>$params->id])->increment('comment_num');
            //发送消息
            $pm = new Pm();
            $pm->user_id = Thread::where(['id'=>$params->id])->first()->id;
            $pm->from_user_id = $this->userId;
            $pm->tid = $params->id;
            $pm->content = "回复了你的评论";
            $pm->type = 4;
            $pm->msg_type = 2;
            $pm->save();
            //$bbsAward = new BbsSendAwardService($this->userId);
            //$bbsAward->publishThreadAward();
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
     *  用户发表评论
     *  仅支持一级评论
     * @JsonRpcMethod
     */
    public function BbsPublishReply($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'comment_id'=>'required|exists:bbs_comments,id',
            'to_id'=>'required|exists:bbs_users,id',

        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $comReply = new CommentReply();
        $comReply->comment_id = $params->comment_id;
        $comReply->from_id = $this->userId;
        $comReply->to_id = $params->to_id;
        $comReply->content = $params->content;
        $comReply->reply_type = "comment";
        $comReply->is_verify =1;
        $res = $comReply->save();
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
        $validator = Validator::make(get_object_vars($params), [
            'type'=>'required',

        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $res = Pm::where(['user_id'=>$this->userId,'msg_type'=>$params->type,'isread'=>0])
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
        $validator = Validator::make(get_object_vars($params), [
            'type'=>'required',

        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $deleted['num'] = Pm::where(['user_id'=>$this->userId,'msg_type'=>$params->type,'isread'=>0])->delete();
        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$deleted,
        );

    }
    /**
     *  消息置未已读
     *
     * @JsonRpcMethod
     */
    public function updatePmStatus($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'id'=>'required|exists:bbs_pms,id',

        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $res = Pm::where(["id"=>$params->id,"user_id"=>$this->userId])->update(["isread"=>1]);
        if($res){
            return[
                'code'=>0,
                'message'=>'success',
                'data'=>$res,
            ];


        }else{
            throw new OmgException(OmgException::DATA_ERROR);
        }
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
     *   收到的赞  收到的评论  被收藏数
     * @JsonRpcMethod
     */
    public function getBbsUserInfo($param){

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }

        $bbsUserInfo = User::where(['user_id'=>$this->userId])->first();

        //没有信息去拉取信息
        if(!$bbsUserInfo){
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
            //发送官方欢迎通知
            $Pm = new Pm();
            $Pm->user_id = $this->userInfo['id'];
            $Pm->content = "欢迎来到网利社区";
            $Pm->type = 1;
            $Pm->msg_type= 1;
            $Pm->save();
            $bbsUserInfo = User::where(['user_id'=>$this->userId])->first();
        }
        //用户发帖被点赞数目
        $userThreadZanNum = ThreadZan::where(["t_user_id"=>$this->userId,"status"=>0])->count();
        //用户评论被点赞数目
        $userCommentZanNum = CommentZan::where(["c_user_id"=>$this->userId,"status"=>0])->count();
        //用户被评论数数目

        $bbsUserInfo['userZanNum'] = $userCommentZanNum+$userThreadZanNum;
        $bbsUserInfo['userCommentNum'] = Comment::where(["bbs_comments.isverify"=>1])
            ->leftJoin('bbs_threads', 'tid', '=', 'bbs_threads.id')
            ->where(["bbs_threads.user_id"=>$this->userId,"bbs_threads.isverify"=>1])
            ->count();
        //用户被收藏数目
        $bbsUserInfo['userThreadCollectionNum'] = ThreadCollection::where(["t_user_id"=>$this->userId,"status"=>0])->count();
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$bbsUserInfo
        ];


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
     * dayPublishThread  achievePublishThread achieveZanThreadP achieveZanThread achieveZanComment achieveGreatThread
     */
    public function queryBbsUserTask($param){
        $this->userId =123;
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        //每日发帖任务 dayPublishThread
        $nowTime = date("Y-m-d",time());
        $dayPublishThreadTaskInfo = Tasks::where(["task_mark"=>"dayPublishThread"])->get()->toArray();
        $dayThreadCount = Thread::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
        foreach ($dayPublishThreadTaskInfo as $k=>$value){
            $res = Task::where('award_time','>',$nowTime)->where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $dayPublishThreadTaskInfo[$k]['current'] = $dayThreadCount;
            $dayPublishThreadTaskInfo[$k]['isaward'] = $res;

        }

        //成就累计发帖  achievePublishThread
        $achievePublishThreadTaskInfo = Tasks::where(["task_mark"=>"achievePublishThread"])->get()->toArray();
        $achieveThreadCount = Thread::where(['isverify'=>1,'user_id'=>$this->userId])->count();
        foreach ($achievePublishThreadTaskInfo as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achievePublishThreadTaskInfo[$k]['current'] = $achieveThreadCount;
            $achievePublishThreadTaskInfo[$k]['isaward'] = $res;

        }
        //成就为他人点赞 achieveZanThreadP
        $achieveZanThreadPTaskInfo = Tasks::where(["task_mark"=>"achieveZanThreadP"])->get()->toArray();
        $achieveZanThreadPCount = ThreadZan::where(['user_id'=>$this->userId])->count();
        foreach ($achieveZanThreadPTaskInfo as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanThreadPTaskInfo[$k]['current'] = $achieveZanThreadPCount;
            $achieveZanThreadPTaskInfo[$k]['isaward'] = $res;

        }
        //成就回复点赞 achieveZanComment
        $achieveZanCommentTaskInfo = Tasks::where(["task_mark"=>"achieveZanComment"])->get()->toArray();
        $achieveZanCommentCount = CommentZan::where(['c_user_id'=>$this->userId])->count();
        foreach ($achieveZanCommentTaskInfo as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanCommentTaskInfo[$k]['current'] = $achieveZanCommentCount;
            $achieveZanCommentTaskInfo[$k]['isaward'] = $res;

        }
        //成就主题贴点赞 achieveZanThread
        $achieveZanThreadTaskInfo = Tasks::where(["task_mark"=>"achieveZanThread"])->get()->toArray();
        $achieveZanThreadCount = ThreadZan::where(['t_user_id'=>$this->userId])->count();
        foreach ($achieveZanThreadTaskInfo as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanThreadTaskInfo[$k]['current'] = $achieveZanThreadCount;
            $achieveZanThreadTaskInfo[$k]['isaward'] = $res;

        }
        //主题贴加精数量 achieveGreatThread
        $achieveGreatThreadTaskInfo = Tasks::where(["task_mark"=>"achieveGreatThread"])->get()->toArray();
        $achieveGreatThreadCount = Thread::where(['user_id'=>$this->userId,"isverify"=>1,"isgreat"=>1])->count();
        foreach ($achieveGreatThreadTaskInfo as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveGreatThreadTaskInfo[$k]['current'] = $achieveGreatThreadCount;
            $achieveGreatThreadTaskInfo[$k]['isaward'] = $res;

        }
        $res = [
            [
                "title"=>"每日任务",
                "task"=>"day",
                "list"=>[
                    [
                        "dayPublishThread"=>$dayPublishThreadTaskInfo,
                        "description"=>"每日发帖"
                    ]

                ]
            ],
            [
                "title"=>"成就任务",
                "task"=>"achieve",
                "list"=>[
                    [
                        "achievePublishThread"=>$achievePublishThreadTaskInfo,
                        "description"=>"累计发布主题帖",
                    ],
                    [
                        "achieveZanThreadP"=>$achieveZanThreadPTaskInfo,
                        "description"=>"累计为他人点赞",
                    ],
                    [
                        "achieveZanComment"=>$achieveZanCommentTaskInfo,
                        "description"=>"回复获得点赞",
                    ],
                    [
                        "achieveZanThread"=>$achieveZanThreadTaskInfo,
                        "description"=>"主题帖获得点赞",
                    ],
                    [
                        "achieveGreatThread"=>$achieveGreatThreadTaskInfo,
                        "description"=>"主题帖被加精",
                    ],


                ]


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

