<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\SignInSystem;
use App\Service\SendAward;
use App\Service\Func;
use Request;

class SignInSystemJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $userId;
    private $award;
    private $result;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId,$award,$result)
    {
        $this->userId = $userId;
        $this->award = $award;
        $this->result = $result;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->userId;
        $award = $this->award;
        $result = $this->result;
        $remark = [];
        $multipleCard = $result['multiple_card'];
        // 发送现金
        if($award['is_rmb']) {
            $uuid = SendAward::create_guid();

            // 创建记录
            $result['awardName'] = $award['size'] . '元';
            $result['amount'] = strval($award['size']);
            $result['awardType'] = 7;
            $res = SignInSystem::create([
                'user_id' => $userId,
                'award_name' => $result['awardName'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'amount' => $award['size'],
                'multiple' => $result['multiple'],
                'multiple_card' => $multipleCard,
                'user_agent' => Request::header('User-Agent'),
                'status' => 0,
                'type' => 7,
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);

            $amount = bcmul($award['size'], $result['multiple'] + $multipleCard, 2);
            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $amount, 'shake');

            $remark['addMoneyRes'] = $result;
            // 成功
            if(isset($purchaseRes['result'])) {
                $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            }

            // 失败
            if(!isset($purchaseRes['result'])) {
                $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            }
        }

        // 根据别名发活动奖品
        if(!$award['is_rmb']) {
            $aliasName = $award['alias_name'];
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                $result['awardName'] = $awards[0]['award_name'];
                $result['awardType'] = $awards[0]['award_type'];
                $result['amount'] = strval(intval($result['awardName']));
                $remark['awards'] = $awards;
                SignInSystem::create([
                    'user_id' => $userId,
                    'amount' => $award['size'],
                    'award_name' => $result['awardName'],
                    'uuid' => '',
                    'ip' => Request::getClientIp(),
                    'user_agent' => Request::header('User-Agent'),
                    'status' => 1,
                    'type' => $result['awardType'],
                    'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
        return ;
    }
}
