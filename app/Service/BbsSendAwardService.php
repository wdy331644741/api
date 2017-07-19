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
     public function __construct($userId,$userPid = "")
     {
        $this->userId = $userId;
        $this->userPId = $userPid;

     }
    /*
     * 发帖触发
     * */
    public function publishThreadAward()
    {
        $this->dayPublishThreadTask();
        $this->publishThreadTask();
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
        $this->zanThreadPTask();
        $this->zanThreadTask();
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

        $dayPublishThreadInfo = Tasks::where(["remark"=>"dayPublishThread"])->get()->toArray();
        if($dayPublishThreadInfo) {
            $nowTime = date("Y:m:d",time());
            $userDayThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->where('created_at','>',$nowTime)->count();
            foreach ($dayPublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id"=>$this->userId,"task_type"=>$value['task_type']])->where('award_time','>',$nowTime)->count();
                //未发过奖
                if(!$res){
                    //审核发奖条件
                    if($userDayThreadCount >= $value['number']){
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);

                    }

                }else{

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

        $dayPublishThreadInfo = Tasks::where(["remark"=>"achievePublishThread"])->get()->toArray();
        if($dayPublishThreadInfo) {

            $userDayThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->count();
            foreach ($dayPublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id"=>$this->userId,"task_type"=>$value['task_type']])->count();
                //未发过奖
                if(!$res){
                    //审核发奖条件
                    if($userDayThreadCount >= $value['number']){
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                    }

                }else{

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
        $achievePublishThreadInfo = Tasks::where(["remark"=>"achieveZanThread"])->get()->toArray();
        if($achievePublishThreadInfo) {
            //点击者 处理点赞任务
            $userDayThreadCount = ThreadZan::where(["t_user_id"=>$this->userPid,"status"=>0])->count();
            foreach ($achievePublishThreadInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['task_type']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userDayThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userPid);
                    }

                } else {

                }


            }
        }else{
            return false;
        }



    }
    /*
     *
     * 成就评论点赞任务
     * */
    private  function zanCommentTask()
    {
        $achieveZanCommentInfo = Tasks::where(["remark"=>"achieveZanComment"])->get()->toArray();
        if($achieveZanCommentInfo) {
            //点击者 处理点赞任务
            $userZanCommentCount = CommentZan::where(["c_user_id"=>$this->userPid,"status"=>0])->count();
            foreach ($achieveZanCommentInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['task_type']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userZanCommentCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userPid);
                    }

                } else {
                    return false;
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
        $achieveGreatCommentInfo = Tasks::where(["remark"=>"achieveGreatComment"])->get()->toArray();
        if($achieveGreatCommentInfo) {
            //点击者 处理点赞任务
            $userGreatThreadCount = Thread::where(["user_id"=>$this->userId,"isverify"=>1,"isgreat"=>1])->count();
            foreach ($achieveGreatCommentInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['task_type']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userGreatThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                    }

                } else {

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
        $achieveZanThreadPInfo = Tasks::where(["remark"=>"achieveZanThreadP"])->get()->toArray();
        if($achieveZanThreadPInfo) {
            //点击者 处理点赞任务
            $userZanThreadCount = ThreadZan::where(["user_id"=>$this->userId,"status"=>1])->count();
            foreach ($achieveZanThreadPInfo as $value) {
                //审核是否已经发过奖
                $res = Task::where(["user_id" => $this->userId, "task_type" => $value['task_type']])->count();
                //未发过奖
                if (!$res) {
                    //审核发奖条件
                    if ($userZanThreadCount >= $value['number']) {
                        //发奖
                        $this->organizeDataAndSend($value,$this->userId);
                    }

                } else {

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
        $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
        $return = SendAward::experience($awards);

    }


}
