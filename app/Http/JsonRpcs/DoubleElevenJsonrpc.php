<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\DoubleElevenService;
use Lib\JsonRpcClient;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Config, Cache,DB;

class DoubleElevenJsonrpc extends JsonRpc
{
    use DispatchesJobs;

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function novElevenInfo() {
        global $userId;
        $result = [
                'login' => false,
                'available' => 0,
                ];
        $cards = DoubleElevenService::getInitCard();
        // 用户是否登录
        if($userId) {
            $result['login'] = true;
        }
        $aliasName = Config::get('doubleeleven.alias_name');
        // 活动是否存在
        if( ActivityService::isExistByAlias($aliasName)) {
            $result['available'] = 1;
        }
        if($result['available'] && $result['login']) {
            $cards = Attributes::getJsonText($userId, $aliasName, $cards);
            // 'lai'==1 时，说明红包卡片已领取完
            if ($cards['lai'] == 0) {
                $activityIds = Activity::select('id')->where('alias_name', 'like', 'shuang11_hongbao_%')->get()->toArray();
                if ($activityIds) {
                    $activityIds = array_column($activityIds, 'id');
//                var_dump($activityIds);die;
                    //判断用户使用红包个数
                    $use_number = self::getRedPacketStatus($userId, $activityIds);
                    if ($use_number > 0) {
                        //领取红包卡片
                        DoubleElevenService::redPacketCard($userId, $use_number);
                        $cards = Attributes::getJsonText($userId, $aliasName, $cards);
                    }
                }
            }
        }
        $result['card'] = $cards;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     *  分享
     *
     * @JsonRpcMethod
     */
    public function novElevenShare() {
        global $userId;
        $result['code'] = 0;
        $result['message'] = 'success';
        if (empty($userId)) {
            $result['message'] = 'fail';
            return $result;
        }
        $aliasName = Config::get('doubleeleven.alias_name');
        $default = DoubleElevenService::getInitCard();
        $text = Attributes::getJsonText($userId, $aliasName, $default);
        if ($text['xi'] == 0) {
            $text['xi'] = 1;
            Attributes::setText($userId, $aliasName, json_encode($text));
        }
        return $result;

    }

    /**
     *  领取
     *
     * @JsonRpcMethod
     */
    public function novElevenReceive($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $aliasName = Config::get('doubleeleven.alias_name');
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
                $data = DoubleElevenService::sendAward($userId,$res->number);
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
    //红包使用个数
    public static function getRedPacketStatus($userId, $ids) {
        if(!$userId || !$ids) {
            return false;
        }
        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        $params['user_id'] = $userId;
        $params['ids'] = $ids;
        //todo 用户组提供数据
        $result = $client->couponUseStatus($params);
        if ( !empty($result['result']) && $result['result']['status'] == 1) {//成功
            $number = intval($result['result']['number']);
            //改状态属性表
        }
        return 0;
    }
}

