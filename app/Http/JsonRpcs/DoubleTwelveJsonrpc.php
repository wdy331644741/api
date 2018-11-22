<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\HdTwelve;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\Attributes;
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
        $userId = 5101480;
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
     *  领取
     *
     * @JsonRpcMethod
     */
    public function twelveReceive($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $aliasName = Config::get('doubletwelve.alias_name');
        if (!ActivityService::isExistByAlias($aliasName)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$aliasName,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $redPackList = json_decode($res->text,1);
            if (isset($redPackList[$params->key]) && $redPackList[$params->key] == 1) {
                $redPackList[$params->key] = 2;
                $res->text = json_encode($redPackList);
                $res->number += 1;
                $updatestatus = $res->save();
                //发奖
                $data = doubletwelveService::sendAward($userId,$res->number);
            }
        }
        if(isset($updatestatus)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>true
            ];
        }
        DB::rollback();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>false
        ];
    }
}

