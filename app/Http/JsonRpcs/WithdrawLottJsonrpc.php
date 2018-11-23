<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\RichLottery;
use App\Models\UserAttribute;
use App\Models\SendRewardLog;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use App\Service\SendMessage;
use App\Service\LotteryService;
use Illuminate\Support\Facades\Redis;
use Config, Request, DB, Cache;

class WithdrawLottJsonRpc extends JsonRpc
{

    protected static $attr_key = 'withdraw_lott';//储存在用户属性表中的key && 活动名称(时间控制)

    /**
     * 拆礼物 首页
     *
     * @JsonRpcMethod
     */
    public function testquanzhong() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $_userInfo = call_user_func(array("App\Service\Func","getUserBasicInfo"),$userId);
        $config = Config::get('withdrawlott');

        // 是否触发间隔限制
        // if($this->isTooOften($userId, $config)) {
        //     throw new OmgException(OmgException::API_BUSY);
        // }

        $result = [
            'awardName' => '',
            'awardType' => 0,
            'amount' => 0,
            'awardSigni' => '',
        ];
        $remark = [];

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //符合用户会员等级的奖品列表
        $item = $config['lists'][max($_userInfo['level'],0)];
        $award = LotteryService::getAward($item);

        //查询是否 剩余抽奖次数
        $beforeCounts = $this->getUserWithdraw();
        if($beforeCounts  <= 0){
            return [
                'code' => -1,
                'message' => 'failed',
                'data' => '抽奖次数不足',
            ];
        }
        //事务开始
        DB::beginTransaction();
        // //forupdate
        // LotteryService::getAttrNumber($userId,$config['drew_daily_key']);
        

        // 根据别名发活动奖品
        $aliasName = $award['alias_name'];
        
        $awards = SendAward::ActiveSendAward($userId, $aliasName);
        if(isset($awards[0]['award_name']) && $awards[0]['status']) {
            $result['awardName'] = $awards[0]['award_name'];
            $result['awardType'] = $awards[0]['award_type'];
            $result['amount'] = strval(intval($result['awardName']));
            $result['awardSigni'] = $aliasName;//奖品标示 需要返回给前端
            $remark['awards'] = $awards;
            RichLottery::create([
                'user_id' => $userId,
                'amount' => $award['size'],
                'award_name' => $result['awardName'],
                'uuid' => $config['alias_name'],//区分活动
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => $result['awardType'],
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            // if($this->getUserWithdraw() == $beforeCounts){//乐观锁
            //     //请求用户中心 减去提现次数
            // }else{
            //     DB::rollBack();
            //     throw new OmgException(OmgException::API_FAILED);
            // }
            DB::commit();

        }else{
            DB::rollBack();
            throw new OmgException(OmgException::API_FAILED);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];

        //$res = SendAward::ActiveSendAward($userId,self::$attr_key);
        //return $res;

    }


    private function getUserWithdraw() {
        $res = LotteryService::getUserProfile();
        return $res['result']['data']['withdraw_num'];
    }


}

