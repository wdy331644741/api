<?php
namespace App\Service;

use App\Exceptions\OmgException;
use Lib\Curl;
use App\Models\Bbs\Task;
use App\Models\Bbs\Thread;
use App\Models\Bbs\Comment;




class BbsSendAwardService
{
     private $userId;

     public function __construct($userId)
     {
        $this->userId = $userId;

     }
     /*
      * 帖子任务
      * */
     public function threadAward(){

         $this->threadOneDayAward();//每天发帖一次奖励
         $this->threadFiveDayAward();//每天发帖五次奖励
         $this->threadTenAchieveAward();//成就发帖十次奖励
         $this->dayAward();//审核是否完成每日任务

     }
     public function commentAward(){

         $this->commentOneDayAward();//每天评论一次奖励
         $this->commentFiveDayAward();//每天评论五次奖励
         $this->commentFiftyAchieveAward();//成就评论五十次奖励
         $this->dayAward();//审核是否完成每日任务
     }

     public function updateImgOrName(){
         $this->updateImgOrNameAchieveAward();//成就更新名称或者头像任务


     }



     /*
      * 每日发帖一次
      * */
     private function threadOneDayAward(){

         $alisa = "task_everyday_thread_one";
         $taskType = "dayThreadOne";
         $bbsDayThreadOneTaskFinsh = 1;
         $bbsDayThreadOneTaskFinshAward = 800;
         $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->where('award_time','>',date("Y-m-d",time()))->count();
         if(!$sendAward){//未发奖
             $threadDaySum = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->where('created_at','>',date("Y-m-d",time()))->count();
             if($threadDaySum >=$bbsDayThreadOneTaskFinsh){//满足条件
                 SendAward::ActiveSendAward($this->userId,$alisa);
                 $task = new Task();
                 $task->user_id = $this->userId;
                 $task->task_type = $taskType;
                 $task->award = $bbsDayThreadOneTaskFinshAward;
                 $task->award_time = date("Y-m-d H:i:s",time());
                 $task->save();
             }
         }

     }
     /*
      *
      * 每日发帖五次
      * */
     private function threadFiveDayAward(){
         $alisa = "task_everyday_thread_five";
         $taskType = "dayThreadFive";
         $bbsDayThreadFiveTaskFinsh = 5;
         $bbsDayThreadFiveTaskFinshAward = 2500;
         $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->where('award_time','>',date("Y-m-d",time()))->count();
         if(!$sendAward){//未发奖
             $threadDaySum = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->where('created_at','>',date("Y-m-d",time()))->count();
             if($threadDaySum >=$bbsDayThreadFiveTaskFinsh){//满足条件
                 SendAward::ActiveSendAward($this->userId,$alisa);
                 $task = new Task();
                 $task->user_id = $this->userId;
                 $task->task_type = $taskType;
                 $task->award = $bbsDayThreadFiveTaskFinshAward;
                 $task->award_time = date("Y-m-d H:i:s",time());
                 $task->save();
             }
         }

     }
     /*
      *每日评论一次
      * */
     private function commentOneDayAward(){
         $alisa = "task_everyday_comment_one";
         $taskType = "dayCommentOne";
         $bbsDayCommentOneTaskFinsh = 1;
         $bbsDayCommentOneTaskFinshAward = 500;
         $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->where('award_time','>',date("Y-m-d",time()))->count();
         if(!$sendAward){//未发奖
             $threadDaySum = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->where('created_at','>',date("Y-m-d",time()))->count();
             if($threadDaySum >=$bbsDayCommentOneTaskFinsh){//满足条件
                 SendAward::ActiveSendAward($this->userId,$alisa);
                 $task = new Task();
                 $task->user_id = $this->userId;
                 $task->task_type = $taskType;
                 $task->award = $bbsDayCommentOneTaskFinshAward;
                 $task->award_time = date("Y-m-d H:i:s",time());
                 $task->save();
             }
         }
     }
    /*
     * 每日评论五次
     *
     * */
     private function commentFiveDayAward(){
         $alisa = "task_everyday_comment_five";
         $taskType = "dayCommentFive";
         $bbsDayCommentOneTaskFinsh = 5;
         $bbsDayCommentOneTaskFinshAward = 1500;
         $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->where('award_time','>',date("Y-m-d",time()))->count();
         if(!$sendAward){//未发奖
             $threadDaySum = Thread::where(["user_id"=>$this->userId,"isverify"=>1])->where('created_at','>',date("Y-m-d",time()))->count();
             if($threadDaySum >=$bbsDayCommentOneTaskFinsh){//满足条件
                 SendAward::ActiveSendAward($this->userId,$alisa);
                 $task = new Task();
                 $task->user_id = $this->userId;
                 $task->task_type = $taskType;
                 $task->award = $bbsDayCommentOneTaskFinshAward;
                 $task->award_time = date("Y-m-d H:i:s",time());
                 $task->save();
             }
         }


     }
     /*
      *
      * 每日任务总和
      * */
     private  function dayAward(){

         $alisa = "task_everyday_all";
         $taskType = "dayAllTask";
         $bbsDayAllTaskFinsh = 4;
         $bbsDayAllTaskFinshAward = 1000;
         $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->where('award_time','>',date("Y-m-d",time()))->count();
         if(!$sendAward){//未发奖
             $countDayTask = Task::where(["user_id"=>$this->userId])->where('award_time','>',date("Y-m-d",time()))->count();
             if($countDayTask >=$bbsDayAllTaskFinsh){//满足条件
                 SendAward::ActiveSendAward($this->userId,$alisa);
                 $task = new Task();
                 $task->user_id = $this->userId;
                 $task->task_type = $taskType;
                 $task->award = $bbsDayAllTaskFinshAward;
                 $task->award_time = date("Y-m-d H:i:s",time());
                 $task->save();
             }
         }
     }
    /*
     *
     * 成就任务总计发帖十次
     * */
    private function threadTenAchieveAward(){
        $alisa = "task_achieve_thread_ten";
        $taskType = "achieveThreadTen";
        $bbsachieveThreadTenTaskFinsh = 10;
        $bbsachieveThreadTenTaskFinshAward = 5000;
        $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->count();
        if(!$sendAward){//未发奖
            $countDayTask = Task::where(["user_id"=>$this->userId])->count();
            if($countDayTask >=$bbsachieveThreadTenTaskFinsh){//满足条件
                SendAward::ActiveSendAward($this->userId,$alisa);
                $task = new Task();
                $task->user_id = $this->userId;
                $task->task_type = $taskType;
                $task->award = $bbsachieveThreadTenTaskFinshAward;
                $task->award_time = date("Y-m-d H:i:s",time());
                $task->save();
            }
        }

    }
    /*
     * 成就任务总计发评论五十次
     * */
    private function commentFiftyAchieveAward(){
        $alisa = "task_achieve_comment_fifty";
        $taskType = "achieveCommentFifty";
        $bbsachieveCommentFiftyTaskFinsh = 50;
        $bbsachieveCommentFiftyTaskFinshAward = 5000;
        $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->count();
        if(!$sendAward){//未发奖
            $countDayTask = Task::where(["user_id"=>$this->userId])->count();
            if($countDayTask >=$bbsachieveCommentFiftyTaskFinsh){//满足条件
                SendAward::ActiveSendAward($this->userId,$alisa);
                $task = new Task();
                $task->user_id = $this->userId;
                $task->task_type = $taskType;
                $task->award = $bbsachieveCommentFiftyTaskFinshAward;
                $task->award_time = date("Y-m-d H:i:s",time());
                $task->save();
            }
        }

    }

    /*
     * 成就任务 修改昵称或者头像
     *
     * */
    private function updateImgOrNameAchieveAward(){
        $alisa = "task_achieve_imgOrName";
        $taskType = "achieveUpdateImgOrName";
        $bbsDayAllTaskFinsh = 1;
        $achieveUpdateImgOrNameTaskFinshAward = 500;
        $sendAward = Task::where(["user_id"=>$this->userId,"task_type"=>$taskType])->count();
        if(!$sendAward){//未发奖
            SendAward::ActiveSendAward($this->userId,$alisa);
            $task = new Task();
            $task->user_id = $this->userId;
            $task->task_type = $taskType;
            $task->award = $achieveUpdateImgOrNameTaskFinshAward;
            $task->award_time = date("Y-m-d H:i:s",time());
            $task->save();
        }


    }
}
