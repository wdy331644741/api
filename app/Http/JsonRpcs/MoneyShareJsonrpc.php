<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\MoneyShareBasic;


class MoneyShareJsonRpc extends JsonRpc {

    /**
     *  发送体验金
     *
     * @JsonRpcMethod
     */
    public function moneyShareSendAward($params) {
        global $userId;
        $userId = 222;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $id = intval($params->id);
        if(empty($id)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        //发送体验金
        $data = MoneyShareBasic::sendMoney($id,$userId);
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
