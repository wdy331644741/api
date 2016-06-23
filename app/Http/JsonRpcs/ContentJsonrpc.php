<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use Lib\JsonRpcInvalidParamsException;
use Lib\JsonRpcParseErrorException as AccountException;

class ContentJsonRpc extends JsonRpc {
    
    /**
     *  获取公告
     *
     * @JsonRpcMethod
     */
    public function noticeList($params) {
        $type_id = ContentType::where('alias_name','notice')->value('id');
        $filter = [
            'type_id' => intval($type_id),
            'release' => 1
        ];
        $pagenum = isset($params->pagenum) ? $params->pagenum : 10;
        $data = Content::select('id','title','release_at')->where($filter)->orderBy('release_at','desc')->paginate($pagenum)->toArray();
        foreach ($data['data'] as $key=>$value){
            $data['data'][$key]['link'] = 'https://www.wanglibao.com/announcement/detail/714/';
        }
        if(isset($data['data'][0]['release_at']))
            $data['Etag'] = strval(strtotime($data['data'][0]['release_at']));
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 获取公告详情
     *
     * @JsonRpcMethod
     */
    public function noticeInfo($params)
    {
        if (empty($params->id)) {
            throw new OmgException(4101);
        }
        $data = Content::select('id','type_id','title','content','release','release_at','platform','release_at')->where('id',intval($params->id))->first()->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
