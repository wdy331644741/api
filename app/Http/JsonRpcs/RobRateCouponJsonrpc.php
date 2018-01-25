<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\HdRatecouponFirendhelp;
use App\Models\HdRatecouponFriend;
use App\Models\UserAttribute;
use App\Models\WechatUser;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use FastDFS\Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;

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
        $userId = 2555555;
        $result = ['login' => false, 'available' => 0, 'rate_coupon'=> 0, 'invite_code'=>'', 'status'=>0, 'rate_coupon_name'=> '0%'];
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
            $result['rate_coupon_name'] = $rateCoupon . "%";
            //分享链接
            $result['invite_code'] = base64_encode($userId);
        }
        $attribute = UserAttribute::where('key', $config['drew_total_key'])->first();
        if($attribute) {
           $result['status'] = 1;
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
        $userId = 2444444;
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
        //是否已兑换加息券，只能兑换一次
        $hasRateFlag = UserAttribute::where('user_id',$p_userid)->where('key',$config['drew_total_key'])->first();
        if($hasRateFlag) {
            throw new OmgException(OmgException::MALL_IS_HAS);
        }
        try {
            //事务开始
            DB::beginTransaction();
            UserAttribute::where('user_id',$p_userid)->where('key',$config['drew_user_key'])->lockForUpdate()->get();
            $amount = $this->getUserRateCoupon($p_userid, $config);//当前加息券值
            $return = ['rate_coupon'=>0, 'flag'=> false];
            $award = $this->getAward($amount, $config);//获取加息的力度值
            dd($award);die;
            if ($award > 0) {
                $addAmount = $amount + $award;
                if ($addAmount > $config['limit']) {
                    Attributes::increment($p_userid, $config['drew_user_key']);
                }
                $this->setUserRateCoupon($p_userid,$config,$addAmount);
                $return['rate_coupon'] = $award;
                $return['flag'] = true;
            }
            //
            $friendParams['f_userid'] = $params['f_userid'] = $userId;
            $friendParams['p_userid'] = $params['p_userid'] = $p_userid;
            $params['amount'] = $return['rate_coupon'];
            $params['alias_name'] = $return['rate_coupon'] ."%";
            //好友加息日志表
            HdRatecouponFirendhelp::insertGetId($params);
            //好友总加息表
            if($return['flag']) {   //加息不为0时，好友总加息+加上当前抽到的加息值
                $friendCoupon = HdRatecouponFriend::where('f_userid', $userId)->where('p_userid', $p_userid)->first();
                $friendParams['total_amount'] = $return['rate_coupon'];
                if(!$friendCoupon) {
                    HdRatecouponFriend::insertGetId($friendParams);
                } else{
                    $friendCoupon->total_amount += $return['rate_coupon'];
                    $friendCoupon->save();
                }
            }
        } finally {
            //事务提交结束
            DB::commit();
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $return,
        ];
    }

    /**
     * 好友查看好友的加息列表
     *
     * @JsonRpcMethod
     */
    public function robratecouponFriendlist($params) {
        $num = isset($params->num) ? $params->num : 10;
        $page = isset($params->page) ? $params->page : 1;
        $invitecode = isset($params->invitecode) ? $params->invitecode : '';
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $userId = intval(base64_decode($invitecode));
        if(!$invitecode || !$userId){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $data = HdRatecouponFriend::select('f_userid', 'total_amount', 'updated_at')
            ->where('p_userid', $userId)
            ->orderBy('updated_at', 'desc')->paginate($num)->toArray();
        $rData = array();
        if(!empty($data['data'])) {
            foreach ($data['data'] as &$item){
                $wechatInfo = WechatUser::where('uid', $item['f_userid'])->toArray()->first();
                $item['nick_name'] = !empty($wechatInfo['nick_name']) ? $wechatInfo['nick_name'] : "";
                $item['headimgurl'] = !empty($wechatInfo['headimgurl']) ? $wechatInfo['headimgurl'] : "";
            }
            $rData['total'] = $data['total'];
            $rData['per_page'] = $data['per_page'];
            $rData['current_page'] = $data['current_page'];
            $rData['last_page'] = $data['last_page'];
            $rData['from'] = $data['from'];
            $rData['to'] = $data['to'];
            $rData['list'] = $data['data'];
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $rData,
        ];
    }

    /**
     * 当前用户有查看加息列表
     *
     * @JsonRpcMethod
     */
    public function robratecouponMyFriendlist($params) {
        global $userId;
        $num = isset($params->num) ? $params->num : 10;
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = HdRatecouponFriend::select('f_userid', 'total_amount', 'updated_at')
            ->where('p_userid', $userId)
            ->orderBy('updated_at', 'desc')->paginate($num)->toArray();
        $rData = array();
        if(!empty($data['data'])) {
            foreach ($data['data'] as &$item){
                $wechatInfo = WechatUser::where('uid', $item['f_userid'])->toArray()->first();
                $item['nick_name'] = !empty($wechatInfo['nick_name']) ? $wechatInfo['nick_name'] : "";
                $item['headimgurl'] = !empty($wechatInfo['headimgurl']) ? $wechatInfo['headimgurl'] : "";
            }
            $rData['total'] = $data['total'];
            $rData['per_page'] = $data['per_page'];
            $rData['current_page'] = $data['current_page'];
            $rData['last_page'] = $data['last_page'];
            $rData['from'] = $data['from'];
            $rData['to'] = $data['to'];
            $rData['list'] = $data['data'];
        }

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
    public function robratecouponMyFriendlis() {
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
        try {
            //事务开始
            DB::beginTransaction();
            UserAttribute::where('user_id',$userId)->where('key',$config['drew_user_key'])->lockForUpdate()->get();
            $amount = $this->getUserRateCoupon($userId, $config);//当前加息券值
            $aliasName = 'jiaxi'.($amount * 10);
            $awardName = $amount . "%加息券";
            //发奖
            $activityInfo = ActivityService::GetActivityInfoByAlias($config['alias_name']);
            $awards = $this->sendPrize($userId, $activityInfo);
            $remark['award'] = $awards;
            $addData['user_id'] = $userId;
            $addData['award_name'] = $awardName;
            $addData['alias_name'] = $aliasName;
            $addData['ip'] = Request::getClientIp();
            $addData['user_agent'] = Request::header('User-Agent');
            $addData['type'] = 'activity';
            $addData['remark'] = json_encode($remark, JSON_UNESCAPED_UNICODE);
            if(isset($awards[0]['status'])) {
                $addData['status'] = 1;
            }
            HdRatecoupon::create($addData);
            Attributes::setItem($userId, $config['drew_total_key'], 1, $amount);
        } finally {
            //事务提交结束
            DB::commit();
        }
        $return['name'] = $awardName;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $return,
        ];



    }
    //获取加息券的增加值
    private function getAward($amount, $config) {
        $rateList = $config['rate'];
        // 获取权重总值
        $totalWeight = $config['weight'];
        $target = mt_rand(1, $totalWeight);
        foreach($rateList as $rate) {
            if( $amount >= $rate['min'] && $$amount < $rate['max'] ) {
                $target = $target - $rate['weight'];
                if($target <= 0) {
                        $round = mt_rand(1,3);
                        return $config['awards'][$round];
                }
                break;
            }
        }
        return 0;
    }

    //获取用户的加息券数值
    private function getUserRateCoupon($userId,$config, $default=0){
        $item = Attributes::getItem($userId, $config['drew_user_key']);
        if($item && $item->string) {
            $default = floor($item->string * 10) / 10;
        }
        return $default;
    }

    private function setUserRateCoupon($userId,$config,$string){
        if(!$string){
            return false;
        }
        Attributes::setItem($userId, $config['drew_user_key'], 0, $string);
        return true;
    }

    private function sendPrize($userId, $activity) {
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');
        $info['name'] = '9%加息券';
        $info['rate_increases'] = 0.09;//加息值
        $info['rate_increases_type'] = 1; //1 全周期  2 加息天数
        $info['effective_time_type'] = 1;//有效期类型 1有效天数
        $info['effective_time_day'] = 7;
        $info['investment_threshold'] = '';//投资门槛
        $info['project_duration_type'] = 1;//项目期限类型 1不限
        $info['project_type'] = 0;//项目类型
        $info['platform_type'] = 0;
        $info['created_at'] = date("Y-m-d HH:ii:ss");
        $info['updated_at'] = date("Y-m-d HH:ii:ss");;
        //$info['limit_desc'] = ;
        //$info['product_id'] = ;
        //$info['rate_increases_start '] = ;
        //$info['rate_increases_end'] = ;
        //$info['effective_time_start'] = ;
        //$info['effective_time_end'] = ;
        //$info['rate_increases_time'] = ;
        //$info['project_duration_time'] = ;
        //$info['message'] = ;
        $info['mail'] = '恭喜你在"{{sourcename}}"活动中获得了"'.$info['name'].'全周期加息券"奖励,请在我的奖励中查看。';//邀请好友抢4%加息券
        $info['source_id'] = $activity->id;////来源id
        $info['source_name'] = isset($activity->name) ? $activity->name : '';//来源名称
        //触发类型
        $info['trigger'] = isset($activity->trigger_type) ? $activity->trigger_type : -1;
        //用户id
        $info['user_id'] = $userId;
        $info['award_type'] = 1;
        $info['uuid'] = null;
        $info['status'] = 0;

        $result = SendAward::increases($info);
        //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
        return self::addJoins($userId, $activity, 3, json_encode($result));

    }
}

