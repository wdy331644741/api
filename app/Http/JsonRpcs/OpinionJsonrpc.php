<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Cms\Opinion;
use Validator, Cache;

class OpinionJsonRpc extends JsonRpc {
    
    /**
     * 提交反馈
     *
     * @JsonRpcMethod
     */
    public function opinionAdd($params) {
        global $userId;

        $validator = Validator::make([
            'content' => $params->content,
            'platform' => $params->platform,
        ], [
            'content' => 'required',
            'platform' => 'required|in:1,2,3,4',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }

        $opinion = new Opinion();
        $opinion->user_id = $userId;
        $opinion->content = $params->content;
        $opinion->platform = $params->platform;
        $opinion->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $opinion->save();
        if($opinion->id){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array('insert_id'=>$opinion->id),
           );
        }else{
            throw new OmgException(OmgException::DATABASE_ERROR);
        }

    }

    /**
     * 晴空redis cash-key
     *
     * @JsonRpcMethod
     */
    public function refreshCache($params) {
        return Cache::forget($params->key);
        //
        //Cache::flush();

    }


}