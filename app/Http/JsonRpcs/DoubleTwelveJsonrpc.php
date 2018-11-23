<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Jobs\DoubleTwelveJob;
use App\Models\Activity;
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
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function twelveInfo() {
        global $userId;
        $result = [
                'login' => 0,
                'available' => 0,
                'number' => 0,
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
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function twelveList() {
        $data = HdTwelve::select('user_id', 'award_name')->orderBy('id', 'desc')->take(20)->get();
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

    /**
     *  实时显示福利券
     *
     * @JsonRpcMethod
     */
    public function twelveShow($params) {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($params->amount) || empty($params->period)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $amount = intval($params->amount);
        $period = intval($params->period);
        if ( $amount < 5000 || $amount > 1000000 || 0 != $amount % 100) { // $amount >= 5000 && $amount <= 1000000 && 0 == $amount % 100
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
        $interest = bcdiv($amount * $period * $award['jiaxi'], 12, 2);
        $return['type'] = 'hongbao';//类型红包
        $return['award_name'] = $award['val'];
        if (bccomp($interest, $award['val'], 2) == 1) {
            $return['type'] = 'jiaxi';//加息券
            $return['award_name'] = $award['jiaxi'];
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
        if( !ActivityService::isExistByAlias($aliasname)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        if( empty($params->amount) || empty($params->period) || empty($params->type) ){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $amount = intval($params->amount);
        $period = intval($params->period);
        if ( $amount < 5000 || $amount > 1000000 || 0 != $amount % 100) {
            throw new OmgException(OmgException::VALID_AMOUNT_ERROR);
        }
        $award = self::getWelfareTicket($amount, $period);
        if (empty($award)) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //  判断用户定制机会
        DB::beginTransaction();
        $user_num = Attributes::getNumberByDay($userId, $aliasname);
        if ($user_num <= 0) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $sendAward['type'] = $params->type;
        $sendAward['amount'] = $amount;
        $sendAward['period'] = $period;
        if ($params->type == 'hongbao') {
            $sendAward['awardName'] = $award['val'];
        } elseif ($params->type == 'jiaxi') {
            $sendAward['awardName'] = $award['jiaxi'];
        } else {
            DB::rollback();
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $this->dispatch(new DoubleTwelveJob($userId, $sendAward));
        Attributes::decrement($userId, $aliasname);
        Attributes::increment($userId, $aliasname . '_total');
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>true,
        ];
    }

    protected static function getWelfareTicket($amount, $period) {
        $awards = Config::get('doubletwelve.awards');
        $return = [];
        foreach ($awards as $v) {
            if ($v['period'] == $period) {
                foreach ($v['award'] as $vv) {
                    if ($amount >= $vv['min'] && $amount <= $vv['max']) {
                        return $vv;
                    }
                }
            }
        }
        return $return;
    }
}

