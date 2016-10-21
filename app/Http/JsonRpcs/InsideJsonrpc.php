<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Service\SendAward;
class InsideJsonrpc extends JsonRpc {

    /**
     *  发送奖品
     *
     * @JsonRpcMethod
     */
    public function sendAward($params) {
        $userId = intval($params->userId);
        $awardType = intval($params->awardType);
        $awardId = intval($params->awardId);
        $sourceName = trim($params->sourceName);
        if(empty($userId) || empty($awardType) || empty($awardId) || empty($sourceName)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $res = SendAward::sendDataRole($userId, $awardType, $awardId, 0, $sourceName);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        );
    }
}