<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\GlobalAttribute;
use App\Models\Hd19AmountShare;
use App\Models\Hd19AmountShareAttribute;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\AmountShareBasic;
use App\Models\HdAmountShareMasterNew;
use App\Models\HdAmountShareMasterNewInfo;
use App\Service\Func;
use DB, Request;

class AmountShare19JsonRpc extends JsonRpc
{

    /*-------------------春节红包分享-------------------*/


    /**
     *  生成分享链接
     *
     * @JsonRpcMethod
     */

    public function createShareUrl($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

    }

    /**
     *  领取分享现金
     *
     * @JsonRpcMethod
     */
    public function receiveAmount($params){
        global $userId;
        $data['user_id'] = 2250796;
        if (empty($data['user_id'])) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data['share_user_id'] = $params->from_user_id;
        if(empty($data['share_user_id'])){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $actInfo = ActivityService::GetActivityedInfoByAlias('19amountshare_send');
        if($actInfo['end_at'] < date('Y-m-d H:i:s')){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //活动全局配置
        $actGlobalConf = GlobalAttribute::where('key','19amountshare')->value('text');
        $confData = json_decode($actGlobalConf,true);
        $userInfo = Func::getUserBasicInfo($userId,true);
        $inviteUserInfo = Func::getUserBasicInfo($data['user_id'],true);
        $data['user_status'] = self::getuserType($userInfo,$actInfo->toArray());

        //用户类型
        $data['amount'] = self::getSendAmount($userId,$data['share_user_id'],$data['user_status'],$confData);
        //根据用户类型发奖存储状态
        if($data['user_status'] == 3){
            $uuid = SendAward::create_guid();

            $data['share_phone'] = $inviteUserInfo['phone'];
            $data['phone'] = $userInfo['phone'];
            $data['date'] = date('Ymd');
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = Hd19AmountShare::insertGetId($data);
            $res1 = Func::incrementAvailable($userId, $id, $uuid, $data['amount'], '19amountshare_newyear_cash');
            $res2 = Func::incrementAvailable($data['share_user_id'], $id, $uuid, $data['amount'], '19amountshare_newyear_cash');
            $remark = ['user'=>0,'invite'=>0];
            // 成功
            if(isset($res1['result'])) {

            }
        }


    }


    /**
     *  领取红包记录
     *
     * @JsonRpcMethod
     */
    public function receiveLog($params){

    }


    //获取用户类型
    private function getuserType($userInfo,$actInfo){
        $userType = null;
        if($userInfo['open_status'] == 2){
            return 3;
        }else{
            if($userInfo['create_time'] < $actInfo['start_at']){
                $userType = 2;
            }elseif($userInfo['create_time'] >= $actInfo['start_at']){
                $userType = 1;
            }
        }
        return $userType;
    }

    //获取用户发奖金额
    private function getSendAmount($userId,$fromUserId,$userType,$confData){
        //单日实际总成本
        $allcost_byday = GlobalAttribute::where('key','allcost_byday')->whereRaw(" to_days(created_at) = to_days(now())")->value('string');
        if(!$allcost_byday){
            $allcost_byday = 0;
        }
        if ($allcost_byday && $allcost_byday >= $confData['allcost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }
        //当日被邀请人成本
        $userCost_byday = Hd19AmountShareAttribute::where(['key'=>'usercost_byday','user_id'=>$userId,'datenum'=>date('Ymd')])->value('amount');
        if(!$userCost_byday){
            $userCost_byday = 0;
        }
        if ($userCost_byday && $userCost_byday >= $confData['usercost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }
        //当日邀请人实际成本
        $inviteUserCost_byday = Hd19AmountShareAttribute::where(['key'=>'usercost_byday','user_id'=>$fromUserId,'datenum'=>date('Ymd')])->value('amount');
        if(!$inviteUserCost_byday){
            $inviteUserCost_byday = 0;
        }
        if ($inviteUserCost_byday && $inviteUserCost_byday >= $confData['usercost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }

        //获取当天用户最高还能领取的红包金额
        $userRestToday = null;
        $isUserOrall = 0;
        //当日总成本剩余
        $restCost = bcsub($confData['allcost_byday'],$allcost_byday,2);
        //当日用户成本剩余
        $user_restCost = bcsub($confData['usercost_byday'],$userCost_byday,2);
        //邀请人当日剩余成本
        $inviteUserCost = bcsub($confData['usercost_byday'],$inviteUserCost_byday,2);

        if(bccomp($user_restCost,$inviteUserCost,2) >= 0){
            $userRestToday = $inviteUserCost;
        }else{
            $userRestToday = $user_restCost;
        }

        if(bccomp($userRestToday,$restCost,2) >= 0){
            $isUserOrall = 1;
            $userRestToday = $restCost;
        }

        //如果当日总成本最小，最大发送剩余总成本/2的红包
        if($isUserOrall){
            $userRestToday = bcdiv($userRestToday, 2, 2);
        }

        $sendAmount = 0;
        switch ($userType){
            case 1:
                //实际的新用户名次
                $realTopNum = GlobalAttribute::where('key','top_num')->whereRaw(" to_days(created_at) = to_days(now())")->value('number');
                if($realTopNum <= $confData['top_num']){
                    if(bccomp($userRestToday,$confData['top_onehundred']['max'],2) >= 0){
                        $sendAmount = mt_rand($confData['top_onehundred']['min'] * 100,$confData['top_onehundred']['max'] * 100);
                    }elseif(bccomp($userRestToday,$confData['top_onehundred']['max'],2) == -1 && bccomp($userRestToday,$confData['top_onehundred']['min'],2) >= 0){
                        $sendAmount = mt_rand($confData['top_onehundred']['min'] * 100,$userRestToday * 100);
                    }else{
                        $sendAmount = $userRestToday *  100;
                    }
                }else{
                    if(bccomp($userRestToday,$confData['newuser_reward']['max'],2) >= 0){
                        $sendAmount = mt_rand($confData['newuser_reward']['min'] * 100,$confData['newuser_reward']['max'] * 100);
                    }elseif(bccomp($userRestToday,$confData['newuser_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['top_onehundred']['min'],2) >= 0){
                        $sendAmount = mt_rand($confData['newuser_reward']['min'] * 100,$userRestToday * 100);
                    }else{
                        $sendAmount = $userRestToday *  100;
                    }
                }
                break;
            case 2:
                //注册为绑卡
                $receiveNum = Hd19AmountShare::where(['user_id'=>$userId,'user_status'=>2])->count();
                if($receiveNum >= 1){
                    throw new OmgException(OmgException::UNBIND_USER_ONLY_RECEIVE_ONE);
                }
                if(bccomp($userRestToday,$confData['olduser_unbind_reward']['max'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_unbind_reward']['min'] * 100,$confData['olduser_unbind_reward']['max'] * 100);
                }elseif(bccomp($userRestToday,$confData['olduser_unbind_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['olduser_unbind_reward']['min'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_unbind_reward']['min'] * 100,$userRestToday * 100);
                }else{
                    $sendAmount = $userRestToday *  100;
                }
                break;
            case 3:
                //老用户
                $inviteNum = Hd19AmountShareAttribute::where(['key'=>'olduser_num_max','user_id'=>$userId])->value('number');
                if($inviteNum )
                if($inviteNum >= 20){
                    throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
                }
                if(bccomp($userRestToday,$confData['olduser_reward']['max'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_reward']['min'] * 100,$confData['olduser_reward']['max'] * 100);
                }elseif(bccomp($userRestToday,$confData['olduser_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['olduser_reward']['min'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_reward']['min'] * 100,$userRestToday * 100);
                }else{
                    $sendAmount = $userRestToday *  100;
                }
                break;
        }

        return bcdiv($sendAmount, 100, 2);
    }


    private function incrementAmountByDay($userId, $key, $num=0) {
        $res = Hd19AmountShareAttribute::where(['user_id' => $userId, 'key' => $key,'datenum'=>date('Ymd')])->first();

        if(!$res) {
            $res = UserAttribute::create(['user_id' => $userId, 'key' => $key,  'amount' => $num,'detenum'=>date('Ymd')]);
            return $res->amount;
        }

        $res->increment('amount', $num);
        return $res->amount;
    }

    private function incrementNumberByDay($userId, $key, $num=1) {
        $res = Hd19AmountShareAttribute::where(['user_id' => $userId, 'key' => $key,'datenum'=>date('Ymd')])->first();

        if(!$res) {
            $res = UserAttribute::create(['user_id' => $userId, 'key' => $key,  'number' => $num,'detenum'=>date('Ymd')]);
            return $res->number;
        }

        $res->increment('number', $num);
        return $res->number;
    }



    /**
     *  现金红包被分完后给自己发送余额
     *
     * @JsonRpcMethod
     */
    public function amountShareMineAward($params){
        global $userId;

        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if($id <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        DB::beginTransaction();
        //判断该红包是否被全部领取
        $where['user_id'] = $userId;
        $where['id'] = $id;
        $where['status'] = 1;
        $where['award_status'] = 0;
        $isFinish = HdAmountShareMasterNew::where($where)->lockForUpdate()->first();
        if(!empty($isFinish) && $isFinish->total_money === $isFinish->use_money && $isFinish->total_num === $isFinish->receive_num){
            //判断有没有新注册的用户领取
            $newList = HdAmountShareMasterNewInfo::select(DB::raw('SUM(money) as money'))
                ->where('main_id',$isFinish->id)
                ->where('is_new',1)->first();
            if(!empty($newList) && isset($newList['money']) && $newList['money'] > 0){
                //获取应得金额
                $sendMoney = AmountShareBasic::getNewUserMoney($isFinish);
                //发奖
                $uuid = Func::create_guid();
                $res = Func::incrementAvailable($userId, $isFinish->id, $uuid, $sendMoney, 'share');
                if (!isset($res['result']['code'])) {
                    throw new OmgException(OmgException::API_FAILED);
                }
                $result['money'] = $sendMoney;
                //添加记录
                HdAmountShareMasterNewInfo::insertGetId([
                    'user_id' => $userId,
                    'main_id' => $id,
                    'uuid' => $uuid,
                    'is_new' => 2,//2为最后领取的金额
                    'money' => $sendMoney,
                    'remark' => json_encode($res, JSON_UNESCAPED_UNICODE),
                    'status' => 1,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                //修改为本人领取完状态
                HdAmountShareMasterNew::where('id',$isFinish->id)->update(['award_status'=>1]);

                DB::commit();
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result
                );
            }
        }
        DB::commit();
        throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
    }
    //将列表的数据整理出手机号
    public static function _formatData($data)
    {
        if (empty($data)) {
            return $data;
        }
        foreach ($data as &$item) {
            if (!empty($item) && isset($item['user_id']) && !empty($item['user_id'])) {
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                //获取微信信息
                $wechatInfo = Func::wechatInfoByUserID($item['user_id']);
                $item['user_name'] = isset($wechatInfo['nick_name']) && !empty($wechatInfo['nick_name']) ? $wechatInfo['nick_name'] : "";
                $item['user_photo'] = isset($wechatInfo['headimgurl']) && !empty($wechatInfo['headimgurl']) ? $wechatInfo['headimgurl'] : "";
            }
        }
        return $data;
    }
}
