<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\MoneyShareBasic;
use Illuminate\Support\Facades\Crypt;


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
        //发送体验金
        $data = MoneyShareBasic::sendMoney($identify,$userId);
        if(isset($data['status']) && $data['status'] === false){
            if($data['code'] === -1){
                throw new OmgException(OmgException::MALL_NOT_EXIST);
            }elseif($data['code'] === -2){
                throw new OmgException(OmgException::MALL_IS_HAS);
            }elseif($data['code'] === -3){
                throw new OmgException(OmgException::DATA_ERROR);
            }elseif($data['code'] === -4){
                throw new OmgException(OmgException::AWARD_NOT_EXIST);
            }elseif($data['code'] === -5){
                throw new OmgException(OmgException::SEND_ERROR);
            }
        }
        return array(
            'code' => 0,
            'message' => 'success'
        );
    }
}
