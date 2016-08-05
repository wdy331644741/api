<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;
use App\Service\RuleCheck;
use App\Models\ActivityJoin;

use Lib\JsonRpcClient;
use Config;

class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityID;
    private $userID;
    private $logUrl;
    private $logID;
    private $triggerData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityID,$userID,$logUrl,$triggerData,$logID)
    {
        $this->activityID = intval($activityID);
        $this->userID = intval($userID);
        $this->logUrl = trim($logUrl);
        $this->logID = $logID;
        $this->triggerData = $triggerData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $info = "\t活动ID:".$this->activityID."\t用户ID:".$this->userID."\t";
        //验证规则
        $status = RuleCheck::check($this->activityID,$this->userID,$this->triggerData);
        if($status['send'] === true){
            //调用发奖队列
            file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."开始发奖".$info."\n",FILE_APPEND);
            //给本人发的奖励
//            echo $this->userID."&".$this->activityID;
//            echo 111;exit;
            $status = SendAward::addAwardByActivity($this->userID,$this->activityID);
            //记录日志
            file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."本人状态:发送成功".$info.json_encode($status)."\n",FILE_APPEND);
            //修改活动参与表状态
            $arr = array();
            $arr['status'] = 2;
            $arr['remark'] = json_encode($status);
            ActivityJoin::where('id',$this->logID)->update($arr);
            //给邀请人发奖励
            $url = Config::get('award.reward_http_url');
            $client = new JsonRpcClient($url);
            //获取邀请人id
            $res = $client->getInviteUser(array('uid'=>$this->userID));
            if(isset($res['result']['code']) && $res['result']['code'] === 0 && isset($res['result']['data']) && !empty($res['result']['data'])){
                $inviteUserID = isset($res['result']['data']['id']) ? $res['result']['data']['id'] : 0;
                if(empty($inviteUserID)){
                    file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."没有邀请人".$info."\n",FILE_APPEND);
                    return true;
                }else{
                    //调用发奖接口
                    $status = SendAward::addAwardToInvite($inviteUserID,$this->activityID);
                    //记录日志
                    file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."邀请人状态:发送成功 邀请人ID:".$inviteUserID."\t活动ID:".$this->activityID."\t".json_encode($status)."\n",FILE_APPEND);
                    //修改活动参与表状态
                    $arr = array();
                    $arr['invite_remark'] = json_encode($status);
                    ActivityJoin::where('id',$this->logID)->update($arr);
                }
                return true;
            }
            return true;
        }else{
            //记录规则错误日志
            file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t ruleErrorMsg \t".$info.$status['errmsg']."\n",FILE_APPEND);
            //修改活动参与表状态
            $err = array();
            $err['status'] = 1;
            $err['remark'] = $status['errmsg'];
            ActivityJoin::where('id',$this->logID)->update($err);
            return false;
        }
    }
}
