<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\OneYuanBasic;
use App\Models\OneYuan;
use App\Models\OneYuanJoinInfo;
use App\Service\Func;
use Illuminate\Pagination\Paginator;

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
            ->where('end_time', '<=', date("Y-m-d H:i:s"))
            ->orderBy('end_time','desc')->take(1)
            ->get()->toArray();
        $list['pass'] = $this->_formatData($yesterdayList);
        //今天的一元夺宝商品
        $todayList = OneYuan::where($where)
            ->where('start_time', '<=', date("Y-m-d H:i:s"))
            ->where('end_time', '>=', date("Y-m-d H:i:s"))
            ->take(1)->get()->toArray();
        $list['now'] = $this->_formatData($todayList);
        //明天的一元夺宝商品
        $tomorrowList = OneYuan::where($where)
            ->where('start_time', '>=', date("Y-m-d H:i:s"))
            ->orderBy('start_time','asc')->take(1)
            ->get()->toArray();
        $list['next'] = $this->_formatData($tomorrowList);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }
    /**
     *  往期列表
     *
     * @JsonRpcMethod
     */
    public function oneYuanHistoryMallList() {
        $where['status'] = 1;
        //昨天的一元夺宝商品
        $yesterdayList = OneYuan::where($where)
            ->where('start_time', '<=', date("Y-m-d 23:59:59",strtotime("-1 days")))
            ->orderBy('id','desc')->get()->toArray();
        $list = $this->_formatData($yesterdayList);
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
            //如果有获奖的用户
            $item['phone'] = '';
            if(isset($item['user_id']) && !empty($item['user_id'])){
                //获取用户手机号
                $userBase = Func::getUserBaseInfo($item['user_id']);
                $item['phone'] = isset($userBase['result']['data']['phone']) ? substr_replace($userBase['result']['data']['phone'], '****', 3, 4) : '';
            }
            if(!empty($item['luck_code'])){
                $item['luck_code'] = $item['luck_code']+10000000;
            }
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
        if($info->buy_num >= $info->total_num){
            throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
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
    /**
     * 夺宝记录
     * @JsonRpcMethod
     */
    public function oneYuanJoinList($params) {
        $page = isset($params->page) ? $params->page : 1;
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $where['status'] = 1;
        //今天的一元夺宝商品
        $todayList = OneYuan::where($where)
            ->where('start_time', '<=', date("Y-m-d H:i:s"))
            ->where('end_time', '>=', date("Y-m-d H:i:s"))
            ->select('id')
            ->first();
        if(empty($todayList)){
            throw new OmgException(OmgException::NO_DATA);
        }
        //获取正在夺宝的记录
        $list = OneYuanJoinInfo::where('mall_id',$todayList->id)
            ->orderBy('id','desc')
            ->paginate(5);
        if(empty($list)){
            throw new OmgException(OmgException::NO_DATA);
        }
        foreach($list as &$item){
            $userBase = Func::getUserBaseInfo($item['user_id']);
            $item['phone'] = isset($userBase['result']['data']['phone']) ? substr_replace($userBase['result']['data']['phone'], '****', 3, 4) : '';
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>$list
        );
    }
}
