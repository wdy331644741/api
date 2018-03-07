<?php

namespace App\Http\JsonRpcs;
use App\Models\Channel;

class ChannelJsonrpc extends JsonRpc {

    /**
     *  查询禁用渠道的字符串
     *
     * @JsonRpcMethod
     */
    public function getChannelDisable() {
        $data = Channel::where('is_disable',1)->get();
        $channels = [];
        foreach($data as $item){
            $channels[] = $item['alias_name'];
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => !empty($channels) ? implode(",",$channels) : ''
        );
    }
}
