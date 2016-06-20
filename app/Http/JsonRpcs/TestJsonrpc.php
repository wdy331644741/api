<?php

namespace App\Http\JsonRpcs;
use App\Http\JsonRpcs\JsonRpc;

class TestJsonRpc extends JsonRpc {
    
    /**
     *  测试 
     *
     * @JsonRpcMethod
     */
    public function add($params) {
        return $params->x + $params->y;
    }
}
