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
use Lib\JsonRpcClient;
use Illuminate\Support\Facades\Redis;
use Config, Request, DB, Cache;

class WithdrawLottJsonRpc extends JsonRpc
{

    protected static $attr_key = 'withdraw_lott';//储存在用户属性表中的key && 活动名称(时间控制)
    protected $_result = [
            'awardName' => '',
            'awardType' => 0,
            'amount' => 0,
            'awardSigni' => '',
        ];

    /**
     * 拆礼物 info
     *
     * @JsonRpcMethod
     */
    public function withDrawLottInfo() {
        global $userId;
        $res = [
            'is_login'      => false,
            // 'num'           => 0,
            'list'          => [],
            'all_user_list' => [],
        ];
        // 活动是否存在
        if(!ActivityService::isExistByAlias(self::$attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
            //查取用户数据
            // $res['num'] = Attributes::getNumber($userId, self::$attr_key,0);
            $_award = RichLottery::select('award_name','created_at')->where('user_id',$userId)->where('status','>=',1)->where('uuid',self::$attr_key)->orderBy('created_at','DESC')->get()->toArray();
            $res['list'] = $_award;
        }
        $res['all_user_list'] = LotteryService::allUserList(self::$attr_key);
        // $_act = ActivityService::GetActivityInfoByAlias(self::$attr_key);//获取活动id
        // if(date('Y-m-d H:i:s' ,strtotime("+5 days") ) > $_act->end_at){
        //     $makeArr['user'] = '131****6448';
        //     $makeArr['award'] = 'iphone xs max 512G';
        //     $res['all_user_list'][0] = $makeArr;
        //     // array_unshift($newArr, $makeArr);
        // }
        return $res;
    }
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

        //是否触发间隔限制
        if(LotteryService::isTooOften($userId, $config)) {
            throw new OmgException(OmgException::API_BUSY);
        }

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

        // 根据别名发活动奖品
        // LotteryService::getAttrNumber($userId,$config['drew_daily_key']);

        if($award['alias_name'] == 'withdraw_again'){
            $result = LotteryService::sendSpaAward($userId,self::$attr_key, $award);
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result,
            ]; 
        }else{
            $result = LotteryService::sendLottAward($userId,self::$attr_key, $award);
             //乐观锁  核对提现次数
            if($beforeCounts == $this->getUserWithdraw() && $result && $this->subUserChanges() ){
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

    }


    private function getUserWithdraw() {
        $res = LotteryService::getUserProfile();
        if(isset($res['error']) ){
            throw new OmgException(OmgException::NO_DATA);
        }
        return $res['result']['data']['withdraw_num'];
    }

    //用户中心rpc  减去用户提现次数

    private function subUserChanges() {
        $rpcClient = new JsonRpcClient(env('ACCOUNT_HTTP_URL') );
        $res = $rpcClient->editFreeWithdrawNum(array("action" => "sub"));
        return isset($res['result']) && $res['result']['code'] == 0;
    }
}

