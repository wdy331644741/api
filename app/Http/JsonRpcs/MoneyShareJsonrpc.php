<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\MoneyShareBasic;
use Illuminate\Support\Facades\Crypt;
use App\Models\MoneyShare;
use App\Models\MoneyShareInfo;

class MoneyShareJsonRpc extends JsonRpc {

    /**
     *  发送体验金
     *
     * @JsonRpcMethod
     */
    public function moneyShareSendAward($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $identify = $params->identify;
        if(empty($identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        //解密identify
        $identify = Crypt::decrypt(urldecode($identify));
        
        $result = ['award' => 0, 'isGot' => false, 'mall' =>[] , 'recentList' => [], 'topList' => []];
        
        
        // 商品是否存在
        $mallInfo = MoneyShare::where(['identify' => $identify, 'status' => 1])->lockForUpdate()->first();
        if(!$mallInfo){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $result['recentList'] = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('id', 'desc')->take(50)->get();
        $result['topList'] = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('money', 'desc')->take(50)->get();
        
        $result['mall'] = $mallInfo;
        
        // 计算剩余金额和剩余数量
        $remain = $mallInfo->total_money - $mallInfo->use_money;
        $remain = $remain > 0 ? $remain : 0;
        $remainNum = $mallInfo->total_num - $mallInfo->receive_num;
        $remainNum = $remainNum > 0 ? $remainNum : 0;
        
        //用户领取过
        $join = MoneyShareInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
        if($join){
            $result['isGot'] = true;       
            $result['award'] = $join['money'];
            return $result;
        }
        
        // 发体验金
        if(!$result['isGot']) {
            $money = MoneyShareBasic::getRandomMoney($remain,$remainNum,$mallInfo->min,$mallInfo->max);
            $mallInfo->increment('use_money', $money);
            $mallInfo->increment('receive_num', 1);

            //发送体验金
            $expRes = MoneyShareBasic::sendAward($userId, $mallInfo['award_type'], $mallInfo['award_id'], $money, $mallInfo['id']);
            MoneyShareInfo::create([
                'user_id' => $userId,
                'main_id' => $expRes['award']['main_id'],
                'uuid' => $expRes['award']['uuid'],
                'money' => $money,
                'source_id' => $expRes['award']['source_id'],
                'award_type' => $mallInfo['award_type'],
                'award_id' => $mallInfo['award_id'],
                'remark' => json_encode($expRes['remark'], JSON_UNESCAPED_UNICODE),
                'mail_status' => $expRes['mail_status'],
                'message_status' => $expRes['message_status'],
                'status' => $expRes['status'],
            ]);
            if(!$expRes['status']) {
                throw new OmgException(OmgException::API_FAILED);
            }
        }

        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }
}
