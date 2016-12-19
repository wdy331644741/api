<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\MoneyShareBasic;
use App\Models\MoneyShare;
use App\Models\MoneyShareInfo;
use App\Service\Func;
use Illuminate\Contracts\Encryption\DecryptException;

class MoneyShareJsonRpc extends JsonRpc {

    /**
     *  发送体验金
     *
     * @JsonRpcMethod
     */
    public function moneyShareSendAward($params) {
        global $userId;
        $result = ['isLogin'=>1, 'award' => 0, 'isGot' => false, 'mall' =>[] , 'recentList' => [], 'topList' => []];
        if(empty($userId)){
            $result['isLogin'] = 0;
        }
        $identify = $params->identify;
        if(empty($identify)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }

        // 商品是否存在
        $date = date("Y-m-d H:i:s");
        $mallInfo = MoneyShare::where(['identify' => $identify, 'status' => 1])
            ->where("start_time","<=",$date)
            ->where("end_time",">=",$date)
            ->lockForUpdate()->first();
        if(!$mallInfo){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $recentList = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('id', 'desc')->take(5)->get();
        $topList = MoneyShareInfo::where('main_id', $mallInfo['id'])->orderBy('money', 'desc')->orderBy('created_at', 'asc')->take(5)->get();
        $result['recentList'] = self::_formatData($recentList);
        $result['topList'] = self::_formatData($topList);
        
        $result['mall'] = $mallInfo;
        // 计算剩余金额和剩余数量
        $remain = $mallInfo->total_money - $mallInfo->use_money;
        $remain = $remain > 0 ? $remain : 0;
        $remainNum = $mallInfo->total_num - $mallInfo->receive_num;
        $remainNum = $remainNum > 0 ? $remainNum : 0;
        
        //用户领取过
        if($result['isLogin']){
            $join = MoneyShareInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
            if($join){
                $result['isGot'] = 1;
                $result['award'] = $join['money'];
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result
                );
            }
            //奖品已抢光
            if($remain == 0){
                $result['isGot'] = 2;
            }
        }

        
        // 发体验金
        if($result['isLogin'] && !$result['isGot']) {
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
            $result['award'] = $money;
        }

        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }
    //将列表的数据整理出手机号
    public static function _formatData($data){
        if(empty($data)){
            return $data;
        }
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return $data;
    }
}
