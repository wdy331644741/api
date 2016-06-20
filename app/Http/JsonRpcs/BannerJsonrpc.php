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
    public function getList($params) {
        $where = array();
        $where['can_use'] = 1;
        //位置
        $position = $params->position;
        if (empty($position)) {
            throw new AccountException();
        }else{
            $typeID = $this->_getPostion(array('nickname' => $position));
            if(empty($typeID)){
                $where['position'] = $position;
            }else {
                if (isset($typeID[0]['id']) && !empty($typeID[0]['id'])) {
                    $where['position'] = $typeID[0]['id'];
                }
            }
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
