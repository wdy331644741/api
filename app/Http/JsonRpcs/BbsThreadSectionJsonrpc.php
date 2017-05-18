<?php

namespace App\Http\JsonRpcs;

use App\Models\Bbs\ThreadSection;
use Lib\JsonRpcClient;
use Validator;



class BbsThreadSectionJsonRpc extends JsonRpc {

    /**
     *  版块列表
     *
     *
     * @JsonRpcMethod
     */
    public function getBbsThreadSectionList($params){

        $data = ThreadSection::where(['isuse'=>'1'])
            ->orderByRaw('sort DESC')
            ->get()
            ->toArray();

        return array(
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        );

    }
}

