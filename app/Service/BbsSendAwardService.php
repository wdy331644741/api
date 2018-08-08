<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\Bbs\CommentZan;
use App\Models\Bbs\ThreadZan;
use Lib\Curl;
use App\Models\Bbs\Task;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;
use App\Models\Activity;
use App\Models\Bbs\Tasks;
use App\Service\SendAward;
use App\Models\Bbs\GroupTask;




class BbsSendAwardService
{
     private $userId;
     private $userPid;
    /*
     * 触发节点
     * 发帖触发 publishThread   评论触发 publishComment
     * 帖子点赞触发 threadZan   评论点赞触发  commentZan
     * 帖子加精触发  threadGreat
     * */
     public function __construct($userId,$userPid = 0)
     {
        $this->userId = $userId;
        $this->userPid = $userPid;

     }
    /*
     * 发帖触发
     * */
    public function publishThreadAward($type = 0)
    {
        switch (true){
            case $type === 0:
                $this->dayPublishThreadTask();
                $this->publishThreadTask();
                break;
            case $type === 1:
                $this->dayPublishThreadTask();
                break;
            case $type === 2:
                $this->publishThreadTask();
                break;
            default :
                $this->dayPublishThreadTask();
                $this->publishThreadTask();
        }


    }
    /*
     * 评论触发
     * */
    public function publishCommentAward()
    {
        //$this->publishComme();


    }
    /*
     * 帖子点赞触发
     * */
    public  function threadZanAward()
    {
        $award = $this->zanThreadPTask();
        $this->zanThreadTask();
        return $award;
    }
    /*
     * 评论点赞触发
     * */
    public  function commentZanAward()
    {
        $this->zanCommentTask();
    }
    /*
     * 帖子加精触发
     * */
    public  function threadGreatAward()
    {
        $this->greatThreadTask();

    }
    /*
     *每天发帖任务
     * */
    private function dayPublishThreadTask()
    {
        $dayPublishThreadInfo = Tasks::where(["task_mark"=>"dayPublishThread","enable"=>1])->get()->toArray();
            if($dayPublishThreadInfo) {
            $nowTime = date("Y:m:d",time());
            $userDayThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->whereRaw('to_days(created_at) = to_days(now())')->count();
            foreach ($dayPublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id"=>$this->userId,"task_type"=>$value['remark']])->where('award_time','>',$nowTime)->count();
                //未发过奖
                if(!$res){
                    //审核发奖条件
                    if($userDayThreadCount >= $value['number']){
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);

                    }

                }


            }
        }else{
            return false;
        }

    }

    /*
     *成就发帖任务
     * */
    private function publishThreadTask()
    {
        $res = $this->maxAchieveAward($this->userId);
        if(!$res){
            return false;
        }
        $dayPublishThreadInfo = Tasks::where(["task_mark"=>"achievePublishThread","enable"=>1])->get()->toArray();
        if($dayPublishThreadInfo) {

            $userDayThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->count();
            foreach ($dayPublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id"=>$this->userId,"task_type"=>$value['remark']])->count();
                //未发过奖
                if(!$res){
                    //审核发奖条件
                    if($userDayThreadCount >= $value['number']){
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                    }

                }


            }
        }else{
            return false;
        }

    }
    /*
     *
     * 成就帖子点赞任务
     * **/
    private  function zanThreadTask()
    {
        $res = $this->maxAchieveAward($this->userId);
        if(!$res){
            return false;
        }
        $achievePublishThreadInfo = Tasks::where(["task_mark"=>"achieveZanThread","enable"=>1])->get()->toArray();
        if($achievePublishThreadInfo) {
            //作者 处理点赞任务
            $userDayThreadCount = ThreadZan::where(["t_user_id"=>$this->userPid,"status"=>0])->count();
            foreach ($achievePublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userPid, "task_type" => $value['remark']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userDayThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userPid);
                        return $value["award"];
                    }

                }


            }
        }else{
            return false;
        }



    }
    /*
     *
     * 成就评论被点赞任务
     * */
    private  function zanCommentTask()
    {
        $res = $this->maxAchieveAward($this->userId);
        if(!$res){
            return false;
        }
        $achieveZanCommentInfo = Tasks::where(["task_mark"=>"achieveZanComment","enable"=>1])->get()->toArray();
        if($achieveZanCommentInfo) {
            //点击者 处理点赞任务
            $userZanCommentCount = CommentZan::where(["c_user_id"=>$this->userPid,"status"=>0])->count();
            foreach ($achieveZanCommentInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userPid, "task_type" => $value['remark']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件

                    if ($userZanCommentCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userPid);
                    }

                }

            }
        }else{
            return false;
        }


    }
    /*
     *
     * 成就主题被加精任务
     * */
    private  function greatThreadTask()
    {
        $res = $this->maxAchieveAward($this->userId);
        if(!$res){
            return false;
        }
        $achieveGreatThreadInfo = Tasks::where(["task_mark"=>"achieveGreatThread","enable"=>1])->get()->toArray();
        if($achieveGreatThreadInfo) {
            //点击者 处理点赞任务
            $userGreatThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1,"isgreat"=>1])->count();
            foreach ($achieveGreatThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['remark']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userGreatThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                    }

                }


            }
        }else{
            return false;
        }

    }
    /*
     * 主题贴点赞者触发奖励
     * */
    private function zanThreadPTask()
    {
        $res = $this->maxAchieveAward($this->userId);
        if(!$res){
            return false;
        }
        $achieveZanThreadPInfo = Tasks::where(["task_mark"=>"achieveZanThreadP","enable"=>1])->get()->toArray();

        if($achieveZanThreadPInfo) {

            //点击者 处理点赞任务
            $userZanThreadCount = ThreadZan::where(["user_id"=>$this->userId,"status"=>0])->count();

            foreach ($achieveZanThreadPInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['remark']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userZanThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                        return $value["award"];
                    }

                }


            }
        }else{
            return false;
        }

    }
    /*
     *
     * 拼装接口数据
     * **/
    private function organizeDataAndSend($params,$awardUserId){
        $awards['id'] = 0;
        $awards['user_id'] = $awardUserId;
        $awards['source_id'] = $params['id'];
        $awards['name'] = $params['award'].'元体验金';
        $awards['source_name'] = $params['name'];
        $awards['experience_amount_money'] = $params['award'];
        $awards['effective_time_type'] = 1;
        $awards['effective_time_day'] = $params['exp_day'];
        $awards['platform_type'] = 0;
        $awards['limit_desc'] = '';
        $awards['trigger'] = "";
        $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
        SendAward::experience($awards);
        //记录发奖数据
        $task = new Task();
        $task->user_id = $awardUserId;
        $task->task_type = $params['remark'];
        $task->award = $params['award'];
        $task->award_time = date("Y-m-d H:i:s",time());
        $task->task_group_id = $params['group_id'];
        $task->save();

    }
    //只能获取一次的任务 设置阀值
    private  function maxAchieveAward($userId){

        //获取所有的仅一次发奖任务类型
        $maxAward = Tasks::where(["frequency"=>2,"enable"=>1])->sum('award');
        $achieveAwardType = Tasks::select(['remark'])->get()->toArray();
        foreach ($achieveAwardType as $v){
            $achieveAwardTypes[] = $v['remark'];
        }
        $userAward = Task::where(["user_id"=>$userId])
            ->whereIn("task_type",$achieveAwardTypes)
            ->sum('award');
        if($userAward>$maxAward){
            return false;
            //超过阀值 不需要发奖
        }else{
            return true;
            //未超过阀值 需要发奖
        }


    }

}
