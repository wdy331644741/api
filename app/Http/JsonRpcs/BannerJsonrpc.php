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
        $where = array(
            'can_use' => 1,
        );
        $position = $params->position;
        if (empty($position)) {
            throw new OmgException(OmgException::VALID_POSITION_FAIL);
        }else{
            $where['position'] = $position;
        }
        switch($position) {
            case 'discover':
                $data = BANNER::where($where)
                    ->orderByRaw('id + sort DESC')->get()->toArray();
                break;
            default:
                $data = BANNER::where($where)
                    ->where(function($query) {
                        $query->whereNull('start')->orWhereRaw('start < now()');
                    })
                    ->where(function($query) {
                        $query->whereNull('end')->orWhereRaw('end > now()');
                    })

                    ->orderByRaw('id + sort DESC')->get()->toArray();
        }

        $rData['list'] = $data;
        $rData['Etag'] = isset($data[0]['release_time']) && !empty($data[0]['release_time']) ? $data[0]['release_time'] : '';
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $rData
        );
    }

    /**
     * 活动弹窗
     *
     * @JsonRpcMethod
     */
    public function activityPop($params) {
         $where = array(
            'can_use' => 1,
             'position' => 'pop',
        );
        $data = BANNER::where($where)
            ->where(function($query) {
                $query->whereNull('start')->orWhereRaw('start < now()');
            })
            ->where(function($query) {
                $query->whereNull('end')->orWhereRaw('end > now()');
            })
            ->orderByRaw('id + sort DESC')->first();

        if(!$data) {
            throw new OmgException(OmgException::NO_DATA);
        }
        $data['Etag'] = $data['release_time'];
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
    /**
     *  获取启动页
     *
     * @JsonRpcMethod
     */
    public function appStartpages($params){
        if (!isset($params->platform) || !isset($params->value)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $filter = [
            'platform'=>$params->platform,
            'enable'=>1,
        ];
        $newdate = date('Y-m-d H:i:s');
        $data = AppStartpage::select('id','img1','img2','img3','img4','target_url','release_at', 'online_time', 'offline_time')
            ->where($filter)
            ->where('online_time','<=',$newdate)
            ->where('offline_time','>=',$newdate)
            ->orderByRaw("id + sort DESC")
            ->first();
        if($data){
            $data['Etag'] = strval(strtotime($data['release_at']));
        }
        $data['img'] = $data["img{$params->value}"];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
