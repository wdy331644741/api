<?php

namespace App\Http\JsonRpcs;
use Lib\JsonRpcService;

class JsonRpc extends JsonRpcService {
    
    /**
     *  测试 
     *
     * @JsonRpcMethod
     */
    public function add($params) {
        return $params->x + $params->y;
    }
}