<?php
namespace App\Http\JsonRpcs;
use App\Http\Controllers\MessageCenterController;
use App\Exceptions\OmgException;
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
        //放入队列
        $obj = new MessageCenterController();
        $res = $obj->_putRewardMore($userId,$awardType,$awardId,$sourceName);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        );
    }
}
