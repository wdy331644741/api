<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\HdSpring;
use App\Service\Attributes;
use App\Service\ActivityService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;
use Lib\JsonRpcClient;

use Config, Request, Cache,DB;

class SpringJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function springInfo() {
        global $userId;
        $result = [
                'login' => 0,
                'available' => 0,
                'join' => 0,
                'fund' => 0,
                ];
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = 1;
        }
        $config = Config::get('spring');
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = 1; //活动开始
        }
        if ($result['login'] && $result['available']) {
            $join = Attributes::getNumber($userId, $config['spring_join_key']);
            $result['join'] = is_null($join) ? 0 : $join;
            if ($result['join']) {
                $fund = Attributes::getNumber($userId, $config['alias_name']);
                $result['fund'] = is_null($fund) ? 0 : $fund;
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 点击参与活动
     *
     * @JsonRpcMethod
     */
    public function springJoin() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $join_key = Config::get('spring.spring_join_key');
        Attributes::setItem($userId, $join_key, 1);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => true,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function springList() {
        $data = HdSpring::select('user_id', 'name')->where('type', '!=', 'empty')->orderBy('id', 'desc')->groupBy('user_id')->take(20)->get();
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
//
//        return [
//            'code' => 0,
//            'message' => 'success',
//            'data' => $list,
//        ];
    }

    /**
     * 兑换记录
     *
     * @JsonRpcMethod
     */
    public function springMyList($params) {
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

}

