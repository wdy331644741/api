<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use App\Models\Cms\Notice;
use Validator;
use Illuminate\Pagination\Paginator;
use Lib\JsonRpcInvalidParamsException;
use Lib\JsonRpcParseErrorException as AccountException;

class ContentJsonRpc extends JsonRpc {
    
    /**
     *  获取公告列表
     *
     * @JsonRpcMethod
     */
    public function noticeList($params) {
        if (empty($params->platform)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $pagenum = isset($params->pagenum) ? $params->pagenum : 10;
        $data = Notice::selectRaw("`id`,`title`,`release_at`,UNIX_TIMESTAMP(`updated_at`) as updated_stamp")->where('release',1)->whereIn('platform',[0,$params->platform])->orderByRaw('id + sort DESC')->orderBy('release_at','DESC')->paginate($pagenum)->toArray();

        if(empty($data['data'])){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => null
            );
        }
        foreach ($data['data'] as $key=>$value){
            if($params->platform == 1){
                $data['data'][$key]['link'] = env('PC_NOTICE_LIST_H5_URL').$value['id'];
            }elseif($params->platform == 2){
                $data['data'][$key]['link'] = env('APP_NOTICE_LIST_H5_URL').$value['id'];
            }

        }
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
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $where = array(
            'id' => intval($params->id),
            'release' => 1,
        ); 
        $data = Notice::select('id','title','content','release','release_at','platform')->where($where)->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 通过别名获取分类
     *
     * @JsonRpcMethod
     */
    public function contentType($params){
        if (empty($params->alias_name)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $parent_id = ContentType::where('alias_name',$params->alias_name)->value('id');

        $data = ContentType::where('parent_id',$parent_id)->orderByRaw('id + sort desc')->get();

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 获取文章列表
     *
     * @JsonRpcMethod
     */
    public function contentList($params){
        if (empty($params->type_id)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }

        $page = isset($params->page) ? $params->page : 1;
        $where = array('type_id'=>$params->type_id,'release'=>1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $pagenum = isset($params->pagenum) ? $params->pagenum : 10;
        $data = Content::selectRaw("`id`, `cover`, `title`, `content`, `release_at`, UNIX_TIMESTAMP(`updated_at`) as updated_stamp")->where($where)->orderByRaw('id + sort desc')->paginate($pagenum);

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 获取文章列表(别名)
     *
     * @JsonRpcMethod
     */
    public function contentListAliasName($params){
        if (empty($params->alias_name)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $type_id = ContentType::where('alias_name',$params->alias_name)->value('id');
        $page = isset($params->page) ? $params->page : 1;
        $where = array('type_id'=>$type_id,'release'=>1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $pagenum = isset($params->pagenum) ? $params->pagenum : 10;
        $data = Content::selectRaw("`id`, `cover`, `title`, `content`, `release_at`, UNIX_TIMESTAMP(`updated_at`) as updated_stamp")->where($where)->orderByRaw('id + sort desc')->paginate($pagenum);

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 获取文章内容
     *
     * @JsonRpcMethod
     */
    public function contentInfo($params){

        if (empty($params->id)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $where = array(
            'id' => intval($params->id),
            'release' => 1,
        );
        $data = Content::select('id','cover','title','content','release','release_at')->where($where)->first();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
