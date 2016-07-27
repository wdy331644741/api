<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Service\SendAward;
use App\Service\RuleCheck;

use Lib\JsonRpcClient;
use Config;

class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityID;
    private $userID;
    private $rule;
    private $logUrl;
    private $triggerData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityID,$userID,$rule,$logUrl,$triggerData)
    {
        $this->activityID = intval($activityID);
        $this->userID = intval($userID);
        $this->rule = trim($rule);
        $this->logUrl = trim($logUrl);
        $this->triggerData = $triggerData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //验证规则
        $rule = $this->rule;
        $status = RuleCheck::$rule($this->activityID,$this->userID);
        if($status['send'] === true){
            //调用发奖队列
            file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."开始发奖 活动ID:".$this->activityID."  用户ID:".$this->userID."\n",FILE_APPEND);
            //给本人发的奖励
            $status = SendAward::addAwardByActivity($this->userID,$this->activityID,0);
            if(!empty($status)){
                file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."本人状态:发送成功".json_encode($status)."\n",FILE_APPEND);
            }else{
                file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."本人状态:发奖失败".json_encode($status)."\n",FILE_APPEND);
            }
            //给邀请人发奖励
            $url = Config::get('award.reward_http_url');
            $client = new JsonRpcClient($url);
            //获取邀请人id
            $res = $client->getInviteList(array('uid'=>$this->userID));
            if(isset($res['result']['code']) && $res['result']['code'] === 0 && isset($res['result']['data']) && !empty($res['result']['data'])){
                $inviteUserID = isset($res['result']['data'][0]) ? $res['result']['data'][0] : 0;
                if(empty($inviteUserID)){
                    file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."没有邀请人"."\n",FILE_APPEND);
                    return true;
                }else{
                    //调用发奖接口
                    $status = SendAward::addAwardToInvite($inviteUserID,$this->activityID,1);
                    if(!empty($status)){
                        file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."邀请人状态:发送成功 邀请人ID:".$inviteUserID.json_encode($status)."\n",FILE_APPEND);
                    }else{
                        file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t"."邀请人状态:发奖失败 邀请人ID:".$inviteUserID.json_encode($status)."\n",FILE_APPEND);
                    }
                }
                return true;
            }
            return true;
        }else{
            //记录规则错误日志
            file_put_contents($this->logUrl,date("Y-m-d H:i:s")."\t ruleErrorMsg \t".$status['errmsg']."\n",FILE_APPEND);
            return false;
        }
    }
}
