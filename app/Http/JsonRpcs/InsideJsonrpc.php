<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Service\SendAward;
use App\Service\SendAwardBatch;
class InsideJsonrpc extends JsonRpc {

    /**
     *  发送奖品
     *
     * @JsonRpcMethod
     */
    public function sendAward($params) {
        $userId = $params->userId;
        $awardType = intval($params->awardType);
        $awardId = intval($params->awardId);
        $sourceName = trim($params->sourceName);
        if(empty($userId) || empty($awardType) || empty($awardId) || empty($sourceName)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        file_put_contents(storage_path('logs/wechat_access_token_error_'.date('Y-m-d').'.log'),date('Y-m-d H:i:s')."=>[".$userId."-".$awardType."-".$awardId."-".$sourceName."]".PHP_EOL,FILE_APPEND);
        $userIdArray = explode(',',$userId);
        if(empty($userIdArray)){
            throw new OmgException(OmgException::VALID_USERID_FAIL);
        }
        $uniArray = array();
        foreach($userIdArray as $item){
            $uniArray[$item] = SendAward::create_guid();
        }
        $res = SendAwardBatch::sendDataRole($userId,$uniArray, $awardType, $awardId, 0, $sourceName);
        if(isset($res['status']) && $res['status']) {
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $res,
            );
        }
        return array(
            'code' => -1,
            'message' => 'fail',
            'data' => $res,
        );
    }
}