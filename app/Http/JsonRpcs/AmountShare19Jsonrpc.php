<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\GlobalAttribute;
use App\Models\Hd19AmountShare;
use App\Models\Hd19AmountShareAttribute;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\SendMessage;
use App\Service\SendAward;
use App\Service\ActivityService;
use Illuminate\Pagination\Paginator;
use DB, Request;

class AmountShare19JsonRpc extends JsonRpc
{

    /*-------------------春节红包分享-------------------*/

    /**
     *  生成分享链接
     *
     * @JsonRpcMethod
     */

    public function createShareUrl(){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $baseUrl = env('APP_URL');
        $shareCode = urlencode(authcode($userId."-".date('Ymd'),'ENCODE',env('APP_KEY')));
        $userInfo = Func::getUserBasicInfo($userId,true);
        $shareUrl = $baseUrl."/active/new_year/luck_draw.html?shareCode=".$shareCode."&inviteCode=".$userInfo['invite_code'];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $shareUrl
        );
    }

    /**
     *  用户领取总额列表
     *
     * @JsonRpcMethod
     */
    public function receiveAllList($params){
        $num = isset($params->num) ? $params->num : 10;
        $res = Hd19AmountShare::selectRaw('id,phone,user_id,SUM(amount) as sum')->groupBy('user_id')->orderBy('sum','DESC')->take($num)->get()->toArray();
        if(empty($res)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => null
            );
        }
        $responseData = [];
        foreach ($res as $val){
            $displayPhone = substr_replace($val['phone'],"******",3,6);
            $val['phone'] = $displayPhone;
            $responseData[] = $val;
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $responseData
        );
    }

    /**
     *  领取邀请人红包列表
     *
     * @JsonRpcMethod
     */
    public function receiveInviteUserList($params){
        $shareCode = $params->shareCode;
        if(empty($shareCode)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $num = isset($params->num) ? $params->num : 4;
        $string = authcode(urldecode($shareCode),'DECODE',env('APP_KEY'));
        $arr = explode('-',$string);
        $fromUserId = isset($arr['0']) ? $arr[0] : null;
        if(date('Ymd') != $arr['1'] || $fromUserId == null){
            throw new OmgException(OmgException::LINK_IS_INVALID);
        }

        $res  = Hd19AmountShare::select('id','phone','amount')->where('share_user_id',$fromUserId)->orderby('amount','DESC')->take($num)->get()->toArray();
        if(empty($res)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => null
            );
        }
        $responseData = [];
        foreach ($res as $val){
            $displayPhone = substr_replace($val['phone'],"******",3,6);
            $val['phone'] = $displayPhone;
            $responseData[] = $val;
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $responseData
        );
    }


    /**
     *  多少人领取了现金
     *
     * @JsonRpcMethod
     */
    public function receiveNum(){

        $res  = Hd19AmountShare::select('id','phone','amount')->where('date',date('Ymd'))->count();
        if(empty($res)){
            $res = 0;
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res
        );
    }

    /**
     *  领取红包中心
     *
     * @JsonRpcMethod
     */
    public function receiveCenter($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(!isset($params->type)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $type = $params->type;
        $page = isset($params->page) ? $params->page : 1;
        $isHadAll = Hd19AmountShare::where(['share_user_id'=>$userId])->where('receive_status','>=',2)->sum('amount');
        $isReceiveAll = Hd19AmountShare::where(['user_id'=>$userId,'receive_status'=>1])->sum('amount');
        $isOnwayAll = Hd19AmountShare::where(['share_user_id'=>$userId,'receive_status'=>1])->sum('amount');
        $data['isReceiveAll'] = $isReceiveAll + $isHadAll;
        $data['isNotReceive'] = $isOnwayAll;
        $userinfo = Func::getUserBasicInfo($userId,true);
        $data['display_name'] = $userinfo['display_name'];
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        switch ($type){
            case 'isHad':
                $isHad = Hd19AmountShare::selectRaw('id,phone,amount,created_at')->where(['share_user_id'=>$userId])->where('receive_status','>=',2)->orderBy('id','desc')->paginate(10)->toArray();
                if(empty($isHad['data'])){
                    $data['data'] = $isHad;
                }
                $responseData = [];
                foreach ($isHad['data'] as $val){
                    $displayPhone = substr_replace($val['phone'],"******",3,6);
                    $val['phone'] = $displayPhone;
                    $responseData[] = $val;
                }
                $isHad['data'] = $responseData;
                $data['data'] = $isHad;
                break;
            case 'isOnway':
                $isOnway = Hd19AmountShare::selectRaw('id,phone,amount,created_at')->where(['share_user_id'=>$userId,'receive_status'=>1])->orderBy('id','desc')->paginate(10)->toArray();
                if(empty($isOnway['data'])){
                    $data['data'] = $isOnway;
                }
                $responseData = [];
                foreach ($isOnway['data'] as $val){
                    $displayPhone = substr_replace($val['phone'],"******",3,6);
                    $val['phone'] = $displayPhone;
                    $responseData[] = $val;
                }
                $isOnway['data'] = $responseData;
                $data['data'] = $isOnway;
                break;
            case 'isReceive':
                $isReceive = Hd19AmountShare::selectRaw('id,share_phone as phone,amount,created_at')->where(['user_id'=>$userId,'receive_status'=>1])->orderBy('id','desc')->paginate(10)->toArray();
                if(empty($isReceive['data'])){
                    $data['data'] = $isReceive;
                }
                $responseData = [];
                foreach ($isReceive['data'] as $val){
                    $displayPhone = substr_replace($val['phone'],"******",3,6);
                    $val['phone'] = $displayPhone;
                    $responseData[] = $val;
                }
                $isReceive['data'] = $responseData;
                $data['data'] = $isReceive;
                break;
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );

    }

    /**
     *  领取分享现金
     *
     * @JsonRpcMethod
     */
    public function receiveAmount($params){
        global $userId;
        $shareCode = $params->shareCode;
        if(empty($shareCode)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $string = authcode(urldecode($shareCode),'DECODE',env('APP_KEY'));

        $arr = explode('-',$string);
        $fromUserId = isset($arr['0']) ? $arr[0] : null;
        if(date('Ymd') != $arr['1'] || $fromUserId == null){
            throw new OmgException(OmgException::LINK_IS_INVALID);
        }
        $data['share_user_id'] = $fromUserId;
        //活动过期
        $actInfo = ActivityService::GetActivityedInfoByAlias('19amountshare_send');
        if($actInfo['end_at'] < date('Y-m-d H:i:s')){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //活动全局配置
        $actGlobalConf = GlobalAttribute::where('key','19amountshare')->value('text');
        $confData = json_decode($actGlobalConf,true);
        //单日实际总成本
        $allcost_byday = GlobalAttribute::where('key','allcost_byday')->whereRaw(" to_days(created_at) = to_days(now())")->value('string');
        if(!$allcost_byday){
            $allcost_byday = 0;
        }
        if ($allcost_byday && $allcost_byday >= $confData['allcost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }

        //当日邀请人实际成本
        $inviteUserCost_byday = Hd19AmountShareAttribute::where(['key'=>'usercost_byday','user_id'=>$data['share_user_id'],'datenum'=>date('Ymd')])->value('amount');
        if(!$inviteUserCost_byday){
            $inviteUserCost_byday = 0;
        }
        if ($inviteUserCost_byday && $inviteUserCost_byday >= $confData['usercost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }

        $data['user_id'] = $userId;
        if (empty($data['user_id'])) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if($data['user_id'] == $data['share_user_id']){
            throw new OmgException(OmgException::NOT_RECEIVE_MY_REDPACK);

        }
        //判断用户当日是否领取过
        $receiveNum = Hd19AmountShare::where(['user_id'=>$data['user_id'],'date'=>date('Ymd')])->count();

        if($receiveNum >=1){
            throw new OmgException(OmgException::TODAY_IS_RECEIVE);
        }

        $userInfo = Func::getUserInfo($data['user_id'],true);

         /*
         未开户状态
         if($userInfo['fm_active_status'] == 0){
            throw new OmgException(OmgException::USER_IS_NOT_OPEN);
        }*/
        $inviteUserInfo = Func::getUserBasicInfo($data['share_user_id'],true);

        //用户类型
        $data['user_status'] = self::getuserType($userInfo,$actInfo->toArray());
        DB::beginTransaction();
        //发送金额
        $data['amount'] = self::getSendAmount($data['user_id'],$data['share_user_id'],$data['user_status'],$confData,$allcost_byday,$inviteUserCost_byday);
        $uuid = SendAward::create_guid();
        $data['share_phone'] = $inviteUserInfo['phone'];
        $data['phone'] = $userInfo['phone'];
        $data['date'] = date('Ymd');
        $data['receive_status'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = Hd19AmountShare::insertGetId($data);
        //根据用户类型发奖存储状态
        switch($data['user_status']){
            case 3:
                $res1 = Func::incrementAvailable($data['user_id'], $id, $uuid, $data['amount'], '19amountshare_newyear_cash');
                $res2 = Func::incrementAvailable($data['share_user_id'], $id, $uuid, $data['amount'], '19amountshare_newyear_cash');
                $remark = ['user'=>0,'invite_user'=>0];

                // 成功
                if(isset($res1['result'])) {
                    $remark['user'] = 1;
                    $MailTpl = "恭喜您在“新年全民红包”活动中抢到".$userInfo['display_name']."用户发送的红包奖励".$data['amount']."元，现金已发放至您网利宝账户余额。";
                    SendMessage::Mail($data['user_id'],$MailTpl);
                    SendMessage::sendPush($data['user_id'],'19as_sendPush');
                }
                if(isset($res2['result'])) {
                    $remark['invite_user'] = 1;
                    SendMessage::sendPush($data['share_user_id'],'19asi_sendPush');
                }

                if($remark['user'] == 0 && $remark['invite_user'] == 0){
                    Hd19AmountShare::where('id',$id)->update(['receive_status'=>3,'remark'=>json_encode($remark)]);
                }else{
                    Hd19AmountShare::where('id',$id)->update(['receive_status'=>2,'remark'=>json_encode($remark)]);
                }

                self::updateAttribute($data['user_id'],$data['share_user_id'],$data['user_status'],$data['amount']);
                break;
            case 2:

                self::updateAttribute($data['user_id'],$data['share_user_id'],$data['user_status'],$data['amount']);
                break;
            case 1:
                self::updateAttribute($data['user_id'],$data['share_user_id'],$data['user_status'],$data['amount']);
                break;
        }
        DB::commit();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'amount'=>$data['amount'],
                'status'=>$userInfo['fm_active_status']
            ]
        );
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

    //更新用户属性
    private function updateAttribute($userId,$fromUserId,$userType,$amount){
        GlobalAttributes::setStringByDay('allcost_byday',bcmul($amount,2,2));
        $res = self::incrementAmountByDay($userId,'usercost_byday',$amount);
        $res = self::incrementAmountByDay($fromUserId,'usercost_byday',$amount);
        switch ($userType){
            case 1:
                //top_num
                GlobalAttributes::setNumberByDay("top_num",1);
                break;
            case 3:
                self::incrementNumberByDay($fromUserId,'olduser_num_max',1);
                break;
        }
    }

    //获取用户发奖金额
    private function getSendAmount($userId,$fromUserId,$userType,$confData,$allcost_byday,$inviteUserCost_byday){
        //锁记录操作
        $num = Hd19AmountShareAttribute::where(['key'=>'usercost_byday','user_id'=>$userId,'datenum'=>date('Ymd')])->count();
        if($num < 1){
            Hd19AmountShareAttribute::insertGetId(['key'=>'usercost_byday','user_id'=>$userId,'datenum'=>date('Ymd'),'amount'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        }
        //当日被邀请人成本
        $userCost_byday_obj = Hd19AmountShareAttribute::where(['key'=>'usercost_byday','user_id'=>$userId,'datenum'=>date('Ymd')])->lockForUpdate()->first();
        $userCost_byday = isset($userCost_byday_obj->amount) ? $userCost_byday_obj->amount : 0;
        if(!$userCost_byday){
            $userCost_byday = 0;
        }
        if ($userCost_byday && $userCost_byday >= $confData['usercost_byday']){
            DB::rollback();
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
                //当用户类型等于1，并且有一个奖品状态是在途中的奖励，则活动期间只能领取一次
                $receiveNum = Hd19AmountShare::where(['user_id'=>$userId,'user_status'=>1,'receive_status'=>1])->first();
                if($receiveNum){
                    DB::rollback();
                    throw new OmgException(OmgException::UNBIND_USER_ONLY_RECEIVE_ONE,$receiveNum->amount);
                }
                //实际的新用户名次
                $realTopNum = GlobalAttribute::where('key','top_num')->whereRaw(" to_days(created_at) = to_days(now())")->value('number');
                if(empty($realTopNum)){
                    $realTopNum = 0;
                }
                if($realTopNum < $confData['top_num']){
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
                    }elseif(bccomp($userRestToday,$confData['newuser_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['newuser_reward']['min'],2) >= 0){
                        $sendAmount = mt_rand($confData['newuser_reward']['min'] * 100,$userRestToday * 100);
                    }else{
                        $sendAmount = $userRestToday *  100;
                    }
                }
                break;
            case 2:
                //注册未绑卡
                $receiveNum = Hd19AmountShare::where(['user_id'=>$userId,'user_status'=>2,'receive_status'=>1])->first();
                if($receiveNum){
                    DB::rollback();
                    throw new OmgException(OmgException::UNBIND_USER_ONLY_RECEIVE_ONE,$receiveNum->amount);
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
                $inviteNum = Hd19AmountShareAttribute::where(['key'=>'olduser_num_max','user_id'=>$fromUserId,'datenum'=>date('Ymd')])->value('number');
                if($inviteNum >= $confData['olduser_num_max']){
                    DB::rollback();
                    throw new OmgException(OmgException::TODAY_OLDUSER_RECEIVE_IS_MORE);
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
            $obj = new Hd19AmountShareAttribute();
            $obj->user_id = $userId;
            $obj->key = $key;
            $obj->amount = $num;
            $obj->datenum = date('Ymd');
            $obj->save();
            return $obj->amount;
        }
        $res->increment('amount', $num);
        return $res->amount;
    }

    private function incrementNumberByDay($userId, $key, $num=1) {
        $res = Hd19AmountShareAttribute::where(['user_id' => $userId, 'key' => $key,'datenum'=>date('Ymd')])->first();
        if(!$res) {
            $obj = new Hd19AmountShareAttribute();
            $obj->user_id = $userId;
            $obj->key = $key;
            $obj->number = $num;
            $obj->datenum = date('Ymd');
            $obj->save();
            return $obj->number;
        }

        $res->increment('number', $num);
        return $res->number;
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
