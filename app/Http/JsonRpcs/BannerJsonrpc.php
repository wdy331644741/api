<?php

namespace App\Http\JsonRpcs;
use App\Models\ImgPosition;
use App\Models\Banner;
use App\Models\AppStartpage;
use App\Exceptions\OmgException as OmgException;
class BannerJsonRpc extends JsonRpc {
    
    /**
     *  banner列表
     *
     * @JsonRpcMethod
     */
    public function bannerList($params) {
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
            $rData['bannerList'] = $data;
            $rData['tag'] = isset($data[0]['release_time']) && !empty($data[0]['release_time']) ? $data[0]['release_time'] : null;
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $rData
            );
        }else{
            throw new OmgException(OmgException::GET_BANNER_FAIL);
        }
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
    /**
     *  获取启动页
     *
     * @JsonRpcMethod
     */
    public function appStartpages($params){
        if (!isset($params->platform)) {
            throw new OmgException(4101);
        }
        $filter = [
            'platform'=>$params->platform,
            'enable'=>1,
        ];
        $newdate = date('Y-m-d H:i:s');
        $data = AppStartpage::select('id','img1','img2','img3','img4','target_url','release_at')
            ->where($filter)
            ->where('online_time','<=',$newdate)
            ->where('offline_time','>=',$newdate)
            ->orderByRaw("offline_time - now() ASC")
            ->first()->toArray();
        $data['Etag'] = strval(strtotime($data['release_at']));
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
