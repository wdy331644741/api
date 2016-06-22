<?php

namespace App\Http\JsonRpcs;
use App\Models\ImgPosition;
use App\Models\Banner;
use Lib\JsonRpcParseErrorException as AccountException;
class BannerJsonRpc extends JsonRpc {
    
    /**
     *  测试 
     *
     * @JsonRpcMethod
     */
    public function bannerList($params) {
        $where = array();
        $where['can_use'] = 1;
        //位置
        $position = $params->position;
        if (empty($position)) {
            throw new AccountException();
        }else{
            $where['position'] = $position;
        }
        $data = Banner::where($where)->orderBy('id','DESC')->get();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
            );
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
}
