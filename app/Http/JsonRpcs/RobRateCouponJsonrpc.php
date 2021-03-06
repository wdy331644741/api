<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\HdRatecouponFriendhelp;
use App\Models\HdRatecouponFriend;
use App\Models\HdRatecoupon;
use App\Models\SendRewardLog;
use App\Models\UserAttribute;
use App\Models\WechatUser;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\RobRateCouponService;
use App\Service\SendAward;
use FastDFS\Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;
use Lib\JsonRpcClient;

use Config, Request, Cache,DB;

class RobRateCouponJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function robratecouponInfo() {
        global $userId;
        $result = [
                'login' => false,
                'available' => 0,
                'rate_coupon'=> 0,
                'invite_code'=>'',
                'status'=>0,
                'alias_name'=> '0%',
                'shareurl'=> '',
                ];
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }
        $config = Config::get('robratecoupon');
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = 1; //活动开始
        }
        if($result['available'] && $result['login']) {
            //获取用户当前加息券数值
            $rateCoupon = $this->getUserRateCoupon($userId,$config);
            $result['rate_coupon'] = $rateCoupon;
            $result['alias_name'] = $rateCoupon . "%";
            $result['invite_code'] = base64_encode($userId);
            //分享链接
            $userInfo = Func::getUserBasicInfo($userId);
            $baseUrl = 'https://' . env('ACCOUNT_BASE_HOST');
            $scallback = urlencode($baseUrl . '/active/help/receive.html?invitecode=' . $result['invite_code']);
            $fcallback = urlencode($baseUrl . '/active/help/share_again.html?invite_code=' . $userInfo['invite_code'] .'&invitecode='.$result['invite_code']);
            $shareurl =  $baseUrl .'/yunying/open/help-login?scallback='.$scallback .'&fcallback='.$fcallback;
            $result['shareurl'] = $shareurl;
            if(Attributes::getItem($userId, $config['drew_total_key'])) {
                $result['status'] = 1;//已兑换
            }

            //加息券是否已使用
            $activityInfo = ActivityService::GetActivityInfoByAlias($config['alias_name']);
            $sendRewardLog = SendRewardLog::where(['user_id'=> $userId, 'activity_id'=> $activityInfo->id, 'status'=> 1])->first();
            if( !empty($sendRewardLog->uuid) && $this->couponUseStatus($sendRewardLog->uuid)) {
                    $result['status'] = 2; //已使用
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 好友助力加息
     *
     * @JsonRpcMethod
     */
    public function robratecouponFriendhelp($params) {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $invitecode = isset($params->invitecode) ? $params->invitecode : '';
        if(empty($invitecode)){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('robratecoupon');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $p_userid = intval(base64_decode($invitecode));
        if(!$p_userid) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //获取用户微信昵称和头像
        $wechatInfo = WechatUser::where('uid', $p_userid)->first();
        $inick_name = !empty($wechatInfo->nick_name) ? $wechatInfo->nick_name : "";
        $headimgurl = !empty($wechatInfo->headimgurl) ? $wechatInfo->headimgurl : "";
        $return = ['rate_coupon'=>0, 'flag'=> false, 'nick_name'=>$inick_name, 'headimgurl'=>$headimgurl, 'myself'=>false, 'message'=>''];
        $returnMess = ['code' => 0,'message' => 'success'];
        //是否已兑换加息券，只能兑换一次
        $hasRateFlag = UserAttribute::where('user_id',$p_userid)->where('key',$config['drew_total_key'])->first();
        if($hasRateFlag) {
            throw new OmgException(OmgException::EXCHANGE_ERROR);
//            $returnMess['data'] = $return;
//            return $returnMess;
        }
        //自己不能给自己加息
        if($userId == $p_userid) {
            $return['myself'] = true;
            $return['message'] = '自己不能为自己加息呦！';
            $returnMess['data'] = $return;
            return $returnMess;
        }
        //一天只能助力一次
        $where['f_userid'] = $userId;
        $startTime = date('Y-m-d 00:00:00', time());
        $endTime = date('Y-m-d 23:59:59', time());
        $hasHelp = HdRatecouponFriendhelp::where($where)->whereBetween('created_at', [$startTime, $endTime])->first();
        if($hasHelp) {
//            throw new OmgException(OmgException::HELP_ERROR);
            $return['message'] = '一天只能为一名好友助力一次呦～';
            $returnMess['data'] = $return;
            return $returnMess;
        }
        //事务开始
        DB::beginTransaction();
        $amount = $this->getUserRateCoupon($p_userid, $config);//当前加息券值
        $userAttr = UserAttribute::where('user_id', $p_userid)->where('key', $config['drew_user_key'])->lockForUpdate()->first();
        if(!$userAttr) {
            $userAttr = new UserAttribute();
            $userAttr->user_id = $p_userid;
            $userAttr->key = $config['drew_user_key'];
            $userAttr->string = "0.0";
            $userAttr->number = 0;
        }
        $success = true;
        if($userAttr->number > $config['draw_number']) {
            $success = false;//超过2.4只能加息一次，一次加息完，成功率为0;
        }
        $award = $this->getAward($amount, $config, $success);//获取加息的力度值
        $addAmount = $amount + $award;
        //加息值 >= 2.4, 标记一次,(标记两次后，成功率为0)
        if ($addAmount >= $config['limit']) {
            if($userAttr->number <= $config['draw_number']) {
                $userAttr->number += 1;
            }
        }
        if ($award > 0) {
            $userAttr->string = $addAmount;
            $return['rate_coupon'] = $award;
            $return['flag'] = true;
        }
        $userAttr->updated_at = date('Y-m-d H:i:s');
        $userAttr->save();
        //好友总加息表
        $friendCoupon = HdRatecouponFriend::where('f_userid', $userId)->where('p_userid', $p_userid)->first();
        if (empty($friendCoupon)) {
            $friendParams['f_userid'] = $userId;
            $friendParams['p_userid'] = $p_userid;
            $friendCoupon = HdRatecouponFriend::create($friendParams);
        }
        //加息不为0时，好友总加息+加上当前抽到的加息值
        $friendCoupon->total_amount += $award;
        $friendCoupon->updated_at = date('Y-m-d H:i:s');
        $friendCoupon->save();
        $fhParams['f_userid'] = $userId;
        $fhParams['p_userid'] = $p_userid;
        $fhParams['amount'] = $award;
        $fhParams['alias_name'] = $award . "%";
        //好友加息日志表
        HdRatecouponFriendhelp::create($fhParams);
        //事务提交结束
       DB::commit();
       $returnMess['data'] = $return;
       return $returnMess;
    }

    /**
     * 助力记录
     *
     * @JsonRpcMethod
     */
    public function robratecouponFriendlist($params) {
        global $userId;
        $num = isset($params->num) ? $params->num : 10;
        $page = isset($params->page) ? $params->page : 1;
        $invitecode = isset($params->invitecode) ? $params->invitecode : '';
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        //传入invitedcode参数
        if(isset($params->invitecode)) {
            $userId = intval(base64_decode($invitecode));
            if(!$userId){
                throw new OmgException(OmgException::PARAMS_ERROR);
            }
        } else {
            //不传invitedcode,默认$userId
            if(!$userId){
                throw new OmgException(OmgException::NO_LOGIN);
            }
        }
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $data = HdRatecouponFriend::select('f_userid', 'total_amount', 'updated_at')
            ->where('p_userid', $userId)
            ->orderBy('updated_at', 'desc')->paginate($num)->toArray();
        $rData = array();
        if(!empty($data['data'])) {
            foreach ($data['data'] as &$item){
                $wechatInfo = WechatUser::where('uid', $item['f_userid'])->first();
                $item['nick_name'] = !empty($wechatInfo->nick_name) ? $wechatInfo->nick_name : "";
                $item['headimgurl'] = !empty($wechatInfo->headimgurl) ? $wechatInfo->headimgurl : "";
                $item['alias_name'] = $item['total_amount'] . "%";
            }
        }
        $rData['total'] = $data['total'];
        $rData['per_page'] = $data['per_page'];
        $rData['current_page'] = $data['current_page'];
        $rData['last_page'] = $data['last_page'];
        $rData['from'] = $data['from'];
        $rData['to'] = $data['to'];
        $rData['list'] = $data['data'];

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $rData,
        ];
    }

    /**
     * 兑换加息券（发奖）
     *
     * @JsonRpcMethod
     */
    public function robratecouponExchange() {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('robratecoupon');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $hasRateFlag = UserAttribute::where('user_id',$userId)->where('key',$config['drew_total_key'])->first();
        if($hasRateFlag) {
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }
        $item = UserAttribute::where('user_id',$userId)->where('key',$config['drew_user_key'])->first();
        if(!$item || !$item->string) {
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }
        $amount = floor($item->string * 10) / 10;
        if(!$amount || $amount > $config['max'] ) {
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }

        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_user_key'])->lockForUpdate()->get();
        $amount = $this->getUserRateCoupon($userId, $config);//当前加息券值
        $aliasName = 'jiaxi'.($amount * 10);
        $awardName = $amount . "%加息券";
        //发奖
        $activityInfo = ActivityService::GetActivityInfoByAlias($config['alias_name']);
        $awards = RobRateCouponService::sendAward($amount, $awardName, $userId, $activityInfo);
        $remark['award'] = json_decode($awards['remark'], 1);
        $addData['user_id'] = $userId;
        $addData['award_name'] = $awardName;
        $addData['alias_name'] = $aliasName;
        $addData['ip'] = Request::getClientIp();
        $addData['user_agent'] = Request::header('User-Agent');
        $addData['type'] = 'activity';
        $addData['remark'] = json_encode($remark, JSON_UNESCAPED_UNICODE);
        if(isset($awards['status'])) {
            $addData['status'] = 1;
        }
        HdRatecoupon::create($addData);
        Attributes::setItem($userId, $config['drew_total_key'], 1, $amount);
        //事务提交结束
        DB::commit();
        $return['name'] = $awardName;
        $return['alias_name'] = $aliasName;
        $return['size'] = $amount;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $return,
        ];



    }
    //获取加息券的增加值
    private function getAward($amount, $config, $success=true) {
        if(!$success) {
            return 0;
        }
        $rateList = $config['rate'];
        // 获取权重总值
        $totalWeight = $config['weight'];
        $target = mt_rand(1, $totalWeight);
        foreach($rateList as $rate) {
            if( $amount >= $rate['min'] && $amount < $rate['max'] ) {
                $target = $target - $rate['weight'];
                if($target <= 0) {
                        $round = mt_rand(1,3);
                        return $config['awards'][$round - 1];
                }
                break;
            }
        }
        return 0;
    }

    //获取用户的加息券数值
    private function getUserRateCoupon($userId,$config, $default=0){
        $item = Attributes::getItem($userId, $config['drew_user_key']);
        if(empty($item)) {
            Attributes::setItem($userId, $config['drew_user_key'], 0, "0.0");
        }
        return isset($item->string) ? floor($item->string * 10) / 10 : $default;
    }

    private function couponUseStatus($uuid) {
        if(!$uuid) {
            return false;
        }
        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        $params['uuid'] = $uuid;
        $result = $client->couponUseStatus($params);
        if (isset($result['result']) && $result['result'] && $result['result']['status'] == 1) {//成功
            return true;
        }
        return false;
    }

}

