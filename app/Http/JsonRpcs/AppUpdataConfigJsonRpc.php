<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\AppUpdateConfig;
use Validator;


class AppUpdateConfigJsonRpc extends JsonRpc {
    
    /**
     * 获取当前升级包配置信息
     *
     * @JsonRpcMethod
     */
    public function currentAppConfig($params) {
        if (empty($params->platform)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $data = AppUpdateConfig::where(['platform'=>$params->platform,'toggle'=>'on'])->orderBy('publish_time','desc')->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }

}