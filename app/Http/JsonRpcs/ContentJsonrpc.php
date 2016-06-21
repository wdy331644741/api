<?php

namespace App\Http\JsonRpcs;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use Lib\JsonRpcInternalErrorException;
use Lib\JsonRpcParseErrorException as AccountException;
class ContentJsonRpc extends JsonRpc {
    
    /**
     *  获取公告
     *
     * @JsonRpcMethod
     */
    public function noticeList($params) {
        if(!$params->alias_name){
            throw new JsonRpcInternalErrorException();
        }
        $type_id = ContentType::where('alias_name',$params->alias_name)->value('id');

        $filter = [
            'type_id' => intval($type_id),
            'release' => 1
        ];
        $pagenum = isset($params->pagenum) ? $params->pagenum : 5;
        $data = Content::select('id','type_id','title','content','release','release_time','platform','created_at')->where($filter)->orderBy('id','desc')->paginate($pagenum)->toArray();
            dd($data);
        if(isset($data['data'][0]['release_time']))
            $data['Etag'] = $data['data'][0]['release_time'];
        unset($data['data'][0]['']);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
