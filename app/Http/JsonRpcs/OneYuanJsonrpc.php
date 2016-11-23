<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\OneYuanBasic;
use App\Models\OneYuan;
use App\Models\OneYuanJoinInfo;

class OneYuanJsonRpc extends JsonRpc {

    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function oneYuanMallList() {
        $where['status'] = 1;
        //昨天的一元夺宝商品
        $yesterdayList = OneYuan::where($where)
            ->where('start_time', '>=', date("Y-m-d 00:00:00",strtotime("-1 days")))
            ->where('start_time', '<=', date("Y-m-d 23:59:59",strtotime("-1 days")))
            ->get()->toArray();
        $list['yesterday'] = $this->_formatData($yesterdayList);
        //今天的一元夺宝商品
        $todayList = OneYuan::where($where)
            ->where('start_time', '>=', date("Y-m-d 00:00:00"))
            ->where('start_time', '<=', date("Y-m-d 23:59:59"))
            ->get()->toArray();
        $list['today'] = $this->_formatData($todayList);
        //明天的一元夺宝商品
        $tomorrowList = OneYuan::where($where)
            ->where('start_time', '>=', date("Y-m-d 00:00:00",strtotime("+1 days")))
            ->where('start_time', '<=', date("Y-m-d 23:59:59",strtotime("+1 days")))
            ->get()->toArray();
        $list['tomorrow'] = $this->_formatData($tomorrowList);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }
    protected function _formatData($data){
        if(empty($data)){
            return $data;
        }
        foreach($data as &$item){
            $item['time_diff'] = strtotime($item['start_time']) - time();
            //去掉不需要的数据
            unset($item['status']);
            unset($item['buy_id']);
            unset($item['total_times']);
            unset($item['priority']);
        }
        return $data;
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
            $return = OneYuanBasic::addNum($userId,$num,'buy',array('buy'=>$num));
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
        //判断当前商品还能不能参加抽奖
        $info = OneYuan::where("id",$mallId)->where("status",1)->select('total_num','buy_num')->first();
        if(empty($info)){
            throw new OmgException(OmgException::NO_DATA);
        }
        if($info->buy_num < $info->total_num){
            if((($info->total_num-$info->buy_num) - $num) < 0){
                throw new OmgException(OmgException::EXCEED_NUM_FAIL);
            }
        }
        //获取用户的抽奖次数判断是否够用
        $totalNum = OneYuanBasic::getUserLuckNum($userId);
        if(isset($totalNum['status']) && !empty($totalNum['status'])){
            if($totalNum['data'] >= $num){
                //添加到抽奖记录表中
                $return = OneYuanBasic::insertJoinInfo($userId,$mallId,$num);
                if(isset($return['status']) && $return['status'] === true){
                    //商品抽奖次数增加
                    OneYuan::where("id",$mallId)->where("status",1)->increment('buy_num',$num);
                    //用户减少抽奖次数
                    $return = OneYuanBasic::reduceNum($userId,$num,'mall',array('mall'=>$mallId));
                    if(isset($return['status']) && $return['status'] === true){
                        return array(
                            'code' => 0,
                            'message' => 'success'
                        );
                    }
                }
            }else{
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
        }
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }
}
