<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\OneYuanBasic;
use App\Models\OneYuan;

class OneYuanJsonRpc extends JsonRpc {

    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function oneYuanMallList() {
        $where['status'] = 1;
        $list = OneYuan::where($where)->orderBy('exhibition','asc')->get()->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }

    /**
     *  购买积分
     *
     * @JsonRpcMethod
     */
    public function buyLuckNum($params) {
        $userId = intval($params->userId);
        $num = intval($params->num);
        if(empty($userId) || empty($num)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //调用孙峰接口余额购买次数
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $status = $client->userBasicInfo(array('userId' =>$userId));
        $status = 1;
        if($status) {
            //如果成功
            $return = OneYuanBasic::addNum($userId,$num);
            if(isset($return['status']) && $return['status'] === true){
                return array(
                    'code' => 0,
                    'message' => 'success'
                );
            }
            return array(
                'code' => -1,
                'message' => 'fail'
            );
        }
        //如果失败
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }
    /**
     * 参与抽奖
     * @JsonRpcMethod
     */
    public function oneYuanJoin($params) {
        $userId = intval($params->userId);
        $mallId = intval($params->mallId);
        $num = intval($params->num);
        if(empty($userId) || empty($mallId) || empty($num)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //获取用户的抽奖次数判断是否够用
        $totalNum = OneYuanBasic::getUserLuckNum($userId);
        if(isset($totalNum['status']) && !empty($totalNum['status'])){
            if($totalNum['status'] >= $num){
                //添加到抽奖记录表中
                $return = OneYuanBasic::insertBuyInfo($userId,$mallId,$num);
                if(isset($return['status']) && $return['status'] === true){
                    return array(
                        'code' => 0,
                        'message' => 'success'
                    );
                }
            }
        }
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }
}
