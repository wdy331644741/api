<?php

namespace App\Http\JsonRpcs;
use App\Models\ImgPosition;
use App\Models\Banner;
use App\Exceptions\OmgException as OmgException;
class BannerJsonRpc extends JsonRpc {
    
    /**
     *  测试 
     *
     * @JsonRpcMethod
     */
    public function bannerList($params) {
        return ($params);exit;
        $where = array();
        $where['can_use'] = 1;
        //位置
        $position = $params->position;
        if (empty($position)) {
            throw new OmgException(OmgException::VALID_POSITION_FAIL);
        }else{
            $where['position'] = $position;
        }
        $data = Banner::where($where)->orderBy('id','DESC')->get()->toArray();
        if(!empty($data)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data
            );
        }else{
            throw new OmgException(OmgException::GET_BANNER_FAIL);
        }
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
}
