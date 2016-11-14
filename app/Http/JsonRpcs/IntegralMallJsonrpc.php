<?php

namespace App\Http\JsonRpcs;
use App\Http\JsonRpcs\JsonRpc;
use App\Models\IntegralMall;
use App\Models\IntegralMallExchange;
class IntegralMallJsonRpc extends JsonRpc {
    
    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function mallList($params) {
        $where = array();
        $where['groups'] = trim($params->groups);
        if(empty($where['groups'])){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $where['status'] = 1;
        $list = IntegralMall::where($where)->orderBy('release_time','desc')->get()->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }
}
