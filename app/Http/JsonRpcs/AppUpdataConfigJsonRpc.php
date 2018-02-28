<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\AppUpdateConfig;
use App\Models\Examine;
use Validator;


class AppUpdateConfigJsonRpc extends JsonRpc {
    
    /**
     * 获取当前升级包配置信息
     *
     * @JsonRpcMethod
     */
    public function currentAppConfig($params) {
        if (empty($params->platform) || empty($params->version)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $structure = '';
        $version_arr = explode('.',$params->version);
        for($i=0; $i<count($version_arr); $i++){
            $structure.=str_pad($version_arr[$i],3,'0',STR_PAD_LEFT);
        }
        $data = AppUpdateConfig::where(['platform'=>$params->platform,'toggle'=>1])->where('structure','>',intval($structure))->orderBy('publish_time','desc')->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }

    /**
     * 获取当前升级包配置信息
     *
     * @JsonRpcMethod
     */
    public function examineConfig() {
        $config = Examine::where('status',1)->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $config,
        );
    }
}