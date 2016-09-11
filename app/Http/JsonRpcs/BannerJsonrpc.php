<?php

namespace App\Http\JsonRpcs;
use App\Models\ImgPosition;
use App\Models\Banner;
use App\Models\AppStartpage;
use App\Exceptions\OmgException as OmgException;
use Illuminate\Pagination\Paginator;

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
        $pageNum = isset($params->pageNum) ? $params->pageNum : 5;
        $page = isset($params->page) ? $params->page : 1;
        if (empty($position)) {
            throw new OmgException(OmgException::VALID_POSITION_FAIL);
        }else{
            $where['position'] = $position;
        }
        switch($position) {
            // 发现页 不做时间限制
            case 'discover':
                $data1 = BANNER::select('id', 'name', 'type','img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
                    ->where($where)
                    ->where(function($query) {
                        $query->whereNull('end')->orWhereRaw('end > now()');
                    })
                    ->orderByRaw('sort DESC')->get()->toArray();
                $data2 = BANNER::select('id', 'name', 'type','img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')
                    ->where($where)
                    ->whereRaw('end < now()')
                    ->orderByRaw('sort DESC')->get()->toArray();
                $data  = array_merge($data1, $data2);
                break;
            // 大事记 增加分页
            case 'memorabilia':
                Paginator::currentPageResolver(function () use ($page) {
                    return $page;
                });
                
                $res = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
                    ->where(function($query) {
                        $query->whereNull('start')->orWhereRaw('start < now()');
                    })
                    ->where(function($query) {
                        $query->whereNull('end')->orWhereRaw('end > now()');
                    })
                    ->orderByRaw('sort DESC')->paginate($pageNum)->toArray();
                $data = $res['data'];
                $rData['total'] = $res['total'];
                $rData['per_page'] = $res['per_page'];
                $rData['current_page'] = $res['current_page'];
                $rData['last_page'] = $res['last_page'];
                $rData['from'] = $res['from'];
                $rData['to'] = $res['to'];
                break;
            // 默认
            default:
                $data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
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
     * 分享配置
     *
     * @JsonRpcMethod
     */   
      public function shareConfig($params) {
        $where = array(
            'can_use' => 1,
        );
        $position = $params->position;
        if (empty($position)) {
            throw new OmgException(OmgException::VALID_POSITION_FAIL);
        }else{
            $where['position'] = $position;
        }
        $data = BANNER::select('id', 'name', 'desc', 'short_desc', 'img_path', 'url', 'start', 'end', 'created_at', 'updated_at', 'release_time')
          ->where($where)
          ->where(function($query) {
              $query->whereNull('end')->orWhereRaw('end > now()');
          })
          ->orderByRaw('sort DESC')->first();
          
        if(!$data) {
            throw new OmgException(OmgException::NO_DATA);
        }

        $data['Etag'] = isset($data['release_time']) && !empty($data['release_time']) ? $data['release_time'] : '';
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }

    /**
     * 渠道落地页
     *
     * @JsonRpcMethod
     */
     public function bannerChannel($params) {
         if(!isset($params->channel)) {
             throw new OmgException(OmgException::VALID_POSITION_FAIL);
         }
         $where = array(
             'can_use' => 1,
             'position' => 'channel',
             'name' => strtolower($params->channel),
         );
         $data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
             ->where(function($query) {
                 $query->whereNull('start')->orWhereRaw('start < now()');
             })
             ->where(function($query) {
                 $query->whereNull('end')->orWhereRaw('end > now()');
             })
             ->orderByRaw('id + sort DESC')->first();
         if(!$data) {
             $where = array(
                 'can_use' => 1,
                 'position' => 'channel',
                 'name' => '',
             );
             $data = BANNER::select('id', 'name', 'type', 'img_path', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
                 ->where(function($query) {
                     $query->whereNull('start')->orWhereRaw('start < now()');
                 })
                 ->where(function($query) {
                     $query->whereNull('end')->orWhereRaw('end > now()');
                 })
                 ->orderByRaw('id + sort DESC')->first();
         }
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

    /**
     * 活动弹窗
     *
     * @JsonRpcMethod
     */
    public function activityPop() {
         $where = array(
            'can_use' => 1,
             'position' => 'pop',
        );
        $data = BANNER::select('id', 'name', 'type', 'img_path', 'url as img_url', 'url', 'start', 'end', 'sort', 'can_use', 'created_at', 'updated_at', 'release_time')->where($where)
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
            $data['img'] = $data["img{$params->value}"];
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
