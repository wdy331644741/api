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
    public function withDrawLott() {
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

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //符合用户会员等级的奖品列表
        $item = $config['lists'][max($_userInfo['level'],0)];
        $award = LotteryService::getAward($item);

        //查询是否 剩余抽奖次数
        $beforeCounts = $this->getUserWithdraw();$beforeCounts = 0;
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

        // 根据别名发活动奖品
        // LotteryService::getAttrNumber($userId,$config['drew_daily_key']);
        $result = LotteryService::sendLottAward($userId,self::$attr_key, $award);
        //乐观锁  核对提现次数
        if($beforeCounts == $this->getUserWithdraw() && $result){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result,
            ]; 
        }else {
            DB::rollBack();
            throw new OmgException(OmgException::API_FAILED);
        }

    }


    private function getUserWithdraw() {
        $res = LotteryService::getUserProfile();
        return $res['result']['data']['withdraw_num'];
    }


}

