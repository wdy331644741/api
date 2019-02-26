<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Jobs\DoubleTwelveJob;
use App\Models\Activity;
use App\Models\HdCustom;
use App\Models\HdCustomAward;
use App\Models\HdTwelve;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\DoubleTwelveService;
use App\Service\Func;
use Lib\JsonRpcClient;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Config, Cache,DB;

class DoubleTwelveJsonrpc extends JsonRpc
{
    use DispatchesJobs;

    /**
     *  实时显示福利券
     *
     * @JsonRpcMethod
     */
    public function twelveShow($params) {
        global $userId;
        $userId = 11;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($params->amount) || empty($params->period)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $amount = intval($params->amount);
        $period = intval($params->period);
        if ( $amount < 5000 || $amount > 1000000 || 0 != $amount % 1000) { // $amount >= 5000 && $amount <= 1000000 && 0 == $amount % 1000
            throw new OmgException(OmgException::VALID_AMOUNT_ERROR);
        }
        $award = self::getWelfareTicket($amount, $period);
        if (empty($award)) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>$award,
            ];
        }
        $return['award_name'] = $award['name'];
//        $return['value'] = $award['`award_money'];
        if ($award['type'] == 1) {
            $return['type'] = 'hongbao';
        } elseif ($award['type'] == 2 ) {
            $return['type'] = 'jiaxi';
            $interest = bcdiv($amount * $period * $award['award_money'], 12, 2);
            $return['interest'] = round($interest);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$return,
        ];
    }

    /**
     *  领取
     *
     * @JsonRpcMethod
     */
    public function twelveReceive($params) {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $aliasname = Config::get('doubletwelve.alias_name');
        if( empty($params->amount) || empty($params->period) || empty($params->type) ){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $amount = intval($params->amount);
        $period = intval($params->period);
        if ( $amount < 5000 || $amount > 1000000 || 0 != $amount % 1000) {
            throw new OmgException(OmgException::VALID_AMOUNT_ERROR);
        }
        //  判断用户账户中是否有该期限的福利券
        if ( !self::getUserCouponByTime($params->period) ) {
            //相同期限福利券已存在，请重新定制
            throw new OmgException(OmgException::CUSTOM_AWARD);
        }
        $award = self::getWelfareTicket($amount, $period);
        $type = $params->type == 'hongbao' ? 1 : ($params->type == 'jiaxi' ? 2 : 0);
        if (empty($award) || $award['type'] != $type) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $sendAward['type'] = $params->type;
        $sendAward['amount'] = $award['min'];
        $sendAward['period'] = $period;
        $sendAward['awardName'] = $award['award_money'];
        $sendAward['effective_time_day'] = $award['effective_time_day'];
        $sendAward['name'] = $award['name'];
        $this->dispatch(new DoubleTwelveJob($userId, $sendAward));
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>true,
        ];
    }

    protected static function getWelfareTicket($amount, $period) {
        $custom_id = HdCustom::where(['status'=>1])->where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->value('id');
        $return = [];
        if (!$custom_id) {
            return $return;
        }
        $award = HdCustomAward::where(['custom_id'=> $custom_id, 'investment_time'=> $period])->where('min', '<=', $amount)->where('max', '>=', $amount)->first();
        if (!$award) {
            return $return;
        }
        return $award;
    }

    //获取用户账户是否有指定期限的福利券
    protected static function getUserCouponByTime($period)
    {
        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        if ($period == 1) {
            $params['coupon_type'] = 6;
            $params['coupon_time'] = 30;
        } else {
            $params['coupon_type'] = 3;
            $params['coupon_time'] = $period;
        }
        $result = $client->getCouponInfoByTypeTime($params);
        if ( isset($result['result']) ) {//成功
            return $result['result']['data']['coupon_status'];
        }
        if (isset($result['error'])) {
            throw new OmgException(OmgException::API_FAILED);
        }
        return false;
    }

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    /*
    public function twelveInfo() {
        global $userId;
        $result = [
                'login' => 0,
                'available' => 0,
                'number' => 0,
                'alert'=> 0,
                ];
        // 用户是否登录
        if($userId) {
            $result['login'] = 1;
        }
        $aliasName = Config::get('doubletwelve.alias_name');
        // 活动是否存在
        if( ActivityService::isExistByAlias($aliasName)) {
            $result['available'] = 1;
        }
        if($result['available'] && $result['login']) {
            $aliasNameTmp = $aliasName.'_day';
            $initNumber = Attributes::getNumberByDay($userId, $aliasNameTmp);
            if ($initNumber == 0) {
                Attributes::incrementByDay($userId, $aliasNameTmp);
                $result['number'] = Attributes::incrementByDay($userId, $aliasName);
            } else {
                $result['number'] = Attributes::getNumberByDay($userId, $aliasName);
            }
            $global_key = $aliasName . '_alert';
            $alert = Attributes::getNumber($userId, $global_key);
            if ($alert) {
                $result['alert'] = 1;
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }
    */

    /**
     * 点击
     *
     * @JsonRpcMethod
     */
    /*
    public function twelveAlert() {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $aliasName = Config::get('doubletwelve.alias_name');
        $global_key = $aliasName . '_alert';
        Attributes::increment($userId, $global_key);
        return [
            'code' => 0,
            'message' => 'success',
        ];
    }
    */

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function twelveList() {
        $data = HdTwelve::select('user_id', 'award_name')->where('status', 1)->orderBy('id', 'desc')->take(20)->get();
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }
}

