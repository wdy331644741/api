<?php

namespace App\Http\JsonRpcs;
use App\Models\Cms\Content;
use Lib\JsonRpcInternalErrorException;
use Lib\JsonRpcParseErrorException as AccountException;
class ContentJsonRpc extends JsonRpc {
    
    /**
     *  获取公告
     *
     * @JsonRpcMethod
     */
        public function getContentList($params) {
        if(!$params->type_id){
            throw new JsonRpcInternalErrorException();
        }
        $filter = [
            'type_id' => $params->type_id,
            'release' => 1
        ];
        $pagenum = isset($params->pagenum) ? $params->pagenum : 5;
        $data = Content::where($filter)->orderBy('id','desc')->paginate($pagenum)->toArray();
        if(isset($data['data'][0]['release_time']))
            $data['Etag'] = $data['data'][0]['release_time'];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
