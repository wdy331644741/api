<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\AppUpdateConfig;
use App\Models\Examine;
use Validator;
use Request;


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
     * 获取当前升级包配置信息（h5用）
     *
     * @JsonRpcMethod
     */
    public function examineConfig($params) {
        $versions = $params->versions;
        $app_name = isset($params->app_name) ? $params->app_name : '';
        //获取请求头
        @$header = Request::header();
        $userAgent = isset($header['user-agent'][0]) ? $header['user-agent'][0] : "";
        if((strpos($userAgent, 'iPhone') || strpos($userAgent, 'iPad')) && strpos($userAgent, 'AppleWebKit')){
            $where['versions'] = $versions;
            if(empty($app_name)){
                $where['type'] = 1;
            }else{
                $where['app_name'] = $app_name;
            }
            $where['status'] = 1;
            //h5页面请求
            $config = Examine::where($where)->first();
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $config,
            );
        }
        //ios请求
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => null,
        );

    }
    /**
     * 获取当前升级包配置信息（移动端专用）
     *
     * @JsonRpcMethod
     */
    public function examineConfigApp($params) {
        $versions = $params->versions;
        $type = isset($params->type) ? $params->type : 1;
        $app_name = isset($params->app_name) ? $params->app_name : '';
        if(empty($versions)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => null,
            );
        }
        $where['status'] = 1;
        $where['versions'] = $versions;
        $where['type'] = $type;
        if(!empty($app_name)){
            $where['app_name'] = $app_name;
        }
        $config = Examine::where($where)->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $config,
        );
    }
}