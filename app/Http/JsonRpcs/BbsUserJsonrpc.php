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
use Lib\JsonRpcClient;
use Validator;
use Config;
use Illuminate\Pagination\Paginator;
use App\Service\Func;
use App\Models\Bbs\GlobalConfig;
use Illuminate\Support\Facades\Redis;
use App\Service\NetEastCheckService;
use App\Service\Attributes;
use App\Service\BbsSendAwardService;
use App\Models\Bbs\CommentReply;
use Illuminate\Support\Facades\DB;






class BbsUserJsonRpc extends JsonRpc {
    private $userId;
    private $userInfo;


    public function __construct()
    {
        global $userId;
        $this->userId = $userId;
        $this->userInfo = Func::getUserBasicInfo($userId);

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

        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        $updateRes = $client->async_profile(array("user_id"=>$this->userId,"nickname"=>"","avater"=>$params->headImg));
        if($updateRes['result']['code']!=0){
            throw new OmgException(OmgException::DATA_ERROR);
        }
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
            'nickname'=>'required|max:11|min:2',
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

        if($res['code']!=200){
            throw new OmgException(OmgException::NAME_IS_ALIVE);
        }

        $users = User::where(['nickname' => $param->nickname])->whereNotIn('user_id', ["$this->userId"])->first();

        if (!$users) {
            //同步数据
            $url = env('ACCOUNT_HTTP_URL');
            $client = new JsonRpcClient($url);
            $updateRes = $client->async_profile(array("user_id"=>$this->userId,"nickname"=>$param->nickname,"avater"=>""));
            if(isset($updateRes['error']) &&$updateRes['error']['code']!=0){
                throw new OmgException(OmgException::NICKNAME_REPEAT);
            }
            User::where(['user_id' => $this->userId])->update(['nickname' => $param->nickname]);

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

        $thread->cover =  !empty($params->imgs)?json_encode($params->imgs):NULL;
        $thread->verify_time = date('Y-m-d H:i:s');

        if($verifyResult ==0 ){
            $thread->verify_label =isset($res["result"]["labels"])?json_encode($res["result"]["labels"]):"";
        }


        if($verifyResult ==1){
            $bbsAward = new BbsSendAwardService($this->userId);
            $bbsAward->publishThreadAward();
            $res = $this->isNewThread();
            if($res){
                $thread->is_new = 0;
            }else{
                $this->setNewThread();
                $thread->is_new = 1;
            }
        }
        $thread->save();
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
        $picArrays=[];
        $resImgCode = 1;
        if(!empty($params->imgs)){
            foreach ($params->imgs as $key=> $value){
                $picArrays[$key]['name'] = $value;
                $picArrays[$key]['type'] = 1;
                $picArrays[$key]['data'] = $value;
            }
            $inParamImg = array(
                "images"=>json_encode($picArrays),
            );

            $imgCheck = new NetEastCheckService($inParamImg);
            $resImg = $imgCheck->imgCheck();

            if($resImg['code'] =='200'){

                $result = $resImg["result"];

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
        $resMaxCode = $resImgCode+$verifyResult;
        switch ($resMaxCode){
            case 0 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的评论已提交审核';
                break;
            case 1 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的评论已提交审核';
                break;
            case 2 ://审核未通过
                $verifyResult = 1;
                $verifyMessage = '评论成功';
        }

        $comment = new Comment();
        $comment->user_id = $this->userId;
        $comment->tid = $params->id;
        $comment->content = $params->content;
        $comment->isverify = $verifyResult;
        $comment->cover =  !empty($params->imgs)?json_encode($params->imgs):NULL;
        if($verifyResult ==0){
            $comment->verify_label =isset($res["result"]["labels"])?json_encode($res["result"]["labels"]):"";
        }
        $comment->save();

        if($verifyResult ==1){
            //增加帖子评论数目
            Thread::where(['id'=>$params->id])->increment('comment_num');
            //发送消息
            $pm = new Pm();
            $pm->user_id = Thread::where(['id'=>$params->id])->first()->user_id;
            $pm->from_user_id = $this->userId;
            $pm->tid = $params->id;
            $pm->content = "回复了你的评论";
            $pm->type = 4;
            $pm->msg_type = 2;
            $pm->save();

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
            'thread_id'=>'required|exists:bbs_threads,id',
            'content'=>'required'
        ]);
        $commentInfo = Comment::where(["id"=>$params->comment_id])->first();
        $toUserInfo = User::where(["user_id"=>$commentInfo->user_id])->first();
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        //回复审核
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
                    $verifyMessage = '回复成功';
                    break;
                case 1 ://有嫌疑
                    $verifyResult = 0;
                    $verifyMessage = '您的回复已提交审核';
                    break;
                case 2 ://审核未通过
                    throw new OmgException(OmgException::COMMENT_ERROR);
            }
        }else{
            $verifyResult= 0;
            $verifyMessage = '您的回复已提交审核';
        }
        $picArrays=[];
        $resImgCode = 1;
        if(!empty($params->imgs)){
            foreach ($params->imgs as $key=> $value){
                $picArrays[$key]['name'] = $value;
                $picArrays[$key]['type'] = 1;
                $picArrays[$key]['data'] = $value;
            }
            $inParamImg = array(
                "images"=>json_encode($picArrays),
            );

            $imgCheck = new NetEastCheckService($inParamImg);
            $resImg = $imgCheck->imgCheck();

            if($resImg['code'] =='200'){

                $result = $resImg["result"];

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
        $resMaxCode = $resImgCode+$verifyResult;
        switch ($resMaxCode){
            case 0 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的回复已提交审核';
                break;
            case 1 ://有嫌疑
                $verifyResult = 0;
                $verifyMessage = '您的回复已提交审核';
                break;
            case 2 ://审核未通过
                $verifyResult = 1;
                $verifyMessage = '回复成功';
        }



        DB::beginTransaction();
            //回复表
            $comReply = new CommentReply();
            $comReply->comment_id = $params->comment_id;
            $comReply->from_id = $this->userId;
            $comReply->to_id = $toUserInfo->user_id;
            $comReply->content = $params->content;
            $comReply->reply_type = "reply";
            $comReply->is_verify =$verifyResult;
            $comReply->cover =  !empty($params->imgs)?json_encode($params->imgs):NULL;
            $replyRes = $comReply->save();
            if(!$replyRes){
                throw new OmgException(OmgException::DATA_ERROR);
            }
            $comment = new Comment();
            $comment->user_id = $this->userId;
            $comment->tid = $params->thread_id;
            $comment->t_user_id = $toUserInfo->user_id;
            $comment->content = $params->content;//格式再定
            $comment->isverify = $verifyResult;
            $comment->comment_type = 1;//回复的类型 1   评论类型 0
            $comment->reply_id = $params->comment_id;
            $comment->cover =  !empty($params->imgs)?json_encode($params->imgs):NULL;
            $comRes = $comment->save();
            if(!$comRes){
                DB::rollBack();
                throw new OmgException(OmgException::DATA_ERROR);
            }
        DB::commit();
        return array(
            'code' => 0,
            'message' => $verifyMessage,
            'data' => $comment::where(['id'=>$comment->id])->first()
        );
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

        $res = Comment::select('id','tid', 'content', 'isverify', 'verify_time', 'created_at',  'updated_at')
            ->where(['isverify'=>1,'user_id'=>$this->userId])
            ->with('thread')
            ->orderByRaw('created_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
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
        $res = Pm::where(['user_id'=>$this->userId,'msg_type'=>$params->type])
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
    public function delBbsUserPm($params){
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make(get_object_vars($params), [
            'ids'=>'required',

        ]);

        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }


        $deleted['num'] = Pm::where(['user_id'=>$this->userId])->whereIn('id',$params->ids)->delete();
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
     *  消息小红点
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
     *  任务小红点
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserCountTask($params)
    {


        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        //查询每日任务
        $nowTime = date("Y-m-d",time());

        $dayTask =  Tasks::where(["group_id"=>1])->get()->toArray();
        $counter = 0;
        foreach ($dayTask as $value){
            $res = Task::where(['user_id'=>$this->userId,'task_type'=>$value['remark']])->where('created_at','>',$nowTime)->first();
            if($res){
                $counter++;
            }
        }


        $achieveTask = Tasks::where(["group_id"=>2])->get()->toArray();

        foreach ($achieveTask  as $value){
            $res = Task::where(['user_id'=>$this->userId,'task_type'=>$value['remark']])->first();
            if($res){
                $counter++;
            }
        }
        return $counter;


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
            $User->head_img = empty($this->userInfo['avater'])?Config::get('headimg')['user'][1]:$this->userInfo['avater'];//默认取第一个
            $User->nickname = $this->userInfo['nickname'];
            $User->phone = $this->userInfo['phone'];
            $User->isblack = 0;
            $User->isadmin = 0;
            $User->save();

//
//            $res = $User::where(['nickname' => 'wl' . $User->id])->first();
//            if($res){
//                $randomArray = range ("a","z");
//                $random = rand(0,25);
//                $User::where(['id' => $User->id])->update(['nickname' => 'wl' . $User->id .$randomArray["$random"]]);
//            }else {
//                $User::where(['id' => $User->id])->update(['nickname' => 'wl' . $User->id]);
//            }
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
        $bbsUserInfo['userZanNum'] = $userCommentZanNum+$userThreadZanNum;
        //用户被评论数数目
        $bbsUserInfo['userCommentNum'] = Comment::select('id')
            ->where(['t_user_id'=>$this->userId,'isverify'=>1,'comment_type'=>0])//0 代表评论  1 代表回复
            ->count();
        /*$bbsUserInfo['userCommentNum'] = Comment::where(["bbs_comments.isverify"=>1])
            ->leftJoin('bbs_threads', 'tid', '=', 'bbs_threads.id')
            ->where(["bbs_threads.user_id"=>$this->userId,"bbs_threads.isverify"=>1])
           ->count();*/


        //用户被收藏数目
        $bbsUserInfo['userThreadCollectionNum'] = ThreadCollection::where(["t_user_id"=>$this->userId,"status"=>0])->count();
        $countPmInfo = $this->getBbsUserCountPm($param);
        $countTaskInfo = $this->getBbsUserCountTask($param);
        $bbsUserInfo['pmPoint']=$countPmInfo['data']['num'];
        $bbsUserInfo['taskPoint'] = $countTaskInfo;

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
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        //每日发帖任务 dayPublishThread
        $nowTime = date("Y-m-d",time());
        $dayPublishThreadTaskInfo["list"] = Tasks::where(["task_mark"=>"dayPublishThread","enable"=>1])->orderByRaw('number')->get()->toArray();
        $dayThreadCount = Thread::where('created_at','>',$nowTime)->where(['isverify'=>1,'user_id'=>$this->userId])->count();
        foreach ($dayPublishThreadTaskInfo["list"] as $k=>$value){
            $res = Task::where('award_time','>',$nowTime)->where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $dayPublishThreadTaskInfo["list"][$k]['current'] = $dayThreadCount;
            $dayPublishThreadTaskInfo["list"][$k]['isaward'] = $res;

        }
        $dayPublishThreadTaskInfo["description"] = "每日发帖";
        //成就累计发帖  achievePublishThread
        $achievePublishThreadTaskInfo["list"] = Tasks::where(["task_mark"=>"achievePublishThread","enable"=>1])->orderByRaw('number')->get()->toArray();
        $achieveThreadCount = Thread::where(['isverify'=>1,'user_id'=>$this->userId])->count();
        foreach ($achievePublishThreadTaskInfo["list"] as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achievePublishThreadTaskInfo["list"][$k]['current'] = $achieveThreadCount;
            $achievePublishThreadTaskInfo["list"][$k]['isaward'] = $res;

        }
        $achievePublishThreadTaskInfo["description"] ="累计发布主题帖";
        //成就为他人点赞 achieveZanThreadP
        $achieveZanThreadPTaskInfo["list"] = Tasks::where(["task_mark"=>"achieveZanThreadP","enable"=>1])->orderByRaw('number')->get()->toArray();
        $achieveZanThreadPCount = ThreadZan::where(['user_id'=>$this->userId])->count();
        foreach ($achieveZanThreadPTaskInfo["list"] as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanThreadPTaskInfo["list"][$k]['current'] = $achieveZanThreadPCount;
            $achieveZanThreadPTaskInfo["list"][$k]['isaward'] = $res;

        }
        $achieveZanThreadPTaskInfo["description"] = "累计为他人点赞";
        //成就回复点赞 achieveZanComment
        $achieveZanCommentTaskInfo["list"] = Tasks::where(["task_mark"=>"achieveZanComment","enable"=>1])->orderByRaw('number')->get()->toArray();
        $achieveZanCommentCount = CommentZan::where(['c_user_id'=>$this->userId])->count();
        foreach ($achieveZanCommentTaskInfo["list"] as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanCommentTaskInfo["list"][$k]['current'] = $achieveZanCommentCount;
            $achieveZanCommentTaskInfo["list"][$k]['isaward'] = $res;

        }
        $achieveZanCommentTaskInfo["description"] = "回复获得点赞";
        //成就主题贴点赞 achieveZanThread
        $achieveZanThreadTaskInfo["list"] = Tasks::where(["task_mark"=>"achieveZanThread","enable"=>1])->orderByRaw('number')->get()->toArray();
        $achieveZanThreadCount["list"] = ThreadZan::where(['t_user_id'=>$this->userId])->count();
        foreach ($achieveZanThreadTaskInfo["list"] as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveZanThreadTaskInfo["list"][$k]['current'] = $achieveZanThreadCount;
            $achieveZanThreadTaskInfo["list"][$k]['isaward'] = $res;

        }
        $achieveZanThreadTaskInfo["description"] = "主题帖获得点赞";
        //主题贴加精数量 achieveGreatThread
        $achieveGreatThreadTaskInfo["list"] = Tasks::where(["task_mark"=>"achieveGreatThread","enable"=>1])->orderByRaw('number')->get()->toArray();
        $achieveGreatThreadCount = Thread::where(['user_id'=>$this->userId,"isverify"=>1,"isgreat"=>1])->count();
        foreach ($achieveGreatThreadTaskInfo["list"] as $k=>$value){
            $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
            $achieveGreatThreadTaskInfo["list"][$k]['current'] = $achieveGreatThreadCount;
            $achieveGreatThreadTaskInfo["list"][$k]['isaward'] = $res;

        }
        $achieveGreatThreadTaskInfo["description"] = "主题帖被加精";
        $res = [
            [
                "title"=>"每日任务",
                "task"=>"day",
                "list"=>[
                    [
                        "dayPublishThread"=>$dayPublishThreadTaskInfo,

                    ]

                ]
            ],
            [
                "title"=>"成就任务",
                "task"=>"achieve",
                "list"=>[
                    [
                        "achievePublishThread"=>$achievePublishThreadTaskInfo,

                    ],
                    [
                        "achieveZanThreadP"=>$achieveZanThreadPTaskInfo,

                    ],
                    [
                        "achieveZanComment"=>$achieveZanCommentTaskInfo,

                    ],
                    [
                        "achieveZanThread"=>$achieveZanThreadTaskInfo,

                    ],
                    [
                        "achieveGreatThread"=>$achieveGreatThreadTaskInfo,

                    ],


                ]


            ]

        ];
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$res
        ];
    }
    /*
     *  获取用户发放体验金
     *
     * @JsonRpcMethod
     */
     public function getBbsUserCountAward($param){
         if (empty($this->userId)) {
             throw  new OmgException(OmgException::NO_LOGIN);
         }
         $nowTime = date("Y-m-d",time());
         $dayPublishThreadTaskInfo = Tasks::where(["task_mark"=>"dayPublishThread","enable"=>1])->get()->toArray();
         foreach ($dayPublishThreadTaskInfo as $k=>$value){
             $res = Task::where('award_time','>',$nowTime)->where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }

         //成就累计发帖  achievePublishThread
         $achievePublishThreadTaskInfo = Tasks::where(["task_mark"=>"achievePublishThread","enable"=>1])->get()->toArray();
         foreach ($achievePublishThreadTaskInfo as $k=>$value){
             $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }
         //成就为他人点赞 achieveZanThreadP
         $achieveZanThreadPTaskInfo = Tasks::where(["task_mark"=>"achieveZanThreadP","enable"=>1])->get()->toArray();

         foreach ($achieveZanThreadPTaskInfo as $k=>$value){
             $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }
         //成就回复点赞 achieveZanComment
         $achieveZanCommentTaskInfo = Tasks::where(["task_mark"=>"achieveZanComment","enable"=>1])->get()->toArray();

         foreach ($achieveZanCommentTaskInfo as $k=>$value){
             $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }
         //成就主题贴点赞 achieveZanThread
         $achieveZanThreadTaskInfo = Tasks::where(["task_mark"=>"achieveZanThread","enable"=>1])->get()->toArray();

         foreach ($achieveZanThreadTaskInfo as $k=>$value){
             $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }
         //主题贴加精数量 achieveGreatThread
         $achieveGreatThreadTaskInfo = Tasks::where(["task_mark"=>"achieveGreatThread","enable"=>1])->get()->toArray();

         foreach ($achieveGreatThreadTaskInfo as $k=>$value){
             $res = Task::where(['task_type'=>$value['remark'],'user_id'=> $this->userId])->count();
             if(!$res){
                 return [
                     'code'=>0,
                     'message'=>'success',
                     'data'=>1
                 ];
             }

         }
         return [
             'code'=>0,
             'message'=>'success',
             'data'=>0
         ];


     }

    /**
     *  获取用户被收藏的帖子
     *
     * @JsonRpcMethod
     */
    public function getBbsUserCollect($params)
    {
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = ThreadCollection::select('id','user_id', 'tid','updated_at')
            ->where(['t_user_id'=>$this->userId,'status'=>0])
            ->with('thread')
            ->with('user')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );

    }


    /**
     *  获取用户被赞的帖子
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserZanThread($params)
    {
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = ThreadZan::select('id','user_id', 'tid','updated_at')
            ->where(['t_user_id'=>$this->userId,'status'=>0])
            ->with('user')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );


    }


    /**
     *  获取用户被赞的帖子
     *
     * @JsonRpcMethod
     */



    public  function  getBbsUserCommentZan($params)
    {
        
        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = CommentZan::select('id','user_id', 'cid','updated_at')
            ->where(['c_user_id'=>$this->userId,'status'=>0])
            ->with('user')
            ->with('comment')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );
    }

    /**
     *  获取用户评论过的帖子
     *
     * @JsonRpcMethod
     */
    public  function getBbsUserComThread($params)

    {

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = Comment::select('id','content', 'tid','updated_at')
            ->where(['t_user_id'=>$this->userId,'isverify'=>1,'comment_type'=>0])//0 代表评论  1 代表回复
            ->with('thread')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );


    }

    /**
     *  获取用户回复过的评论
     *
     * @JsonRpcMethod
     */

    public function getBbsUserComCommnet($params)
    {

        if (empty($this->userId)) {
            throw  new OmgException(OmgException::NO_LOGIN);
        }
        $pageNum = isset($params->pageNum) ? $params->pageNum : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $res = CommentReply::select('id','comment_id','content','updated_at')
            ->where(['to_id'=>$this->userId,'is_verify'=>1,'reply_type'=>'reply'])//0 代表评论  1 代表回复
            ->with('replycomment')
            ->orderByRaw('updated_at DESC')
            ->paginate($pageNum)
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$res,
        );
    }

    /*
     *
     * 是否是新人贴
     * */
     private function  isNewThread(){

         $isNewThreadKey = 'bbs_newThread';
        //判断 是否发过新帖子
         $res = Redis::GETBIT($isNewThreadKey,$this->userId);
         return $res;

     }
     /*
      * 设置新人贴
      *
      * */
     private function setNewThread(){
         $isNewThreadKey = 'bbs_newThread';
         Redis::SETBIT($isNewThreadKey,$this->userId,1);
     }
}

