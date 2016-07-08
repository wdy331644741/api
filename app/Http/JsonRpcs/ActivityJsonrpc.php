<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;

class ActivityJsonRpc extends JsonRpc {
    
    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin($params) {
        global $userId;
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $userId,
        );
    }
}