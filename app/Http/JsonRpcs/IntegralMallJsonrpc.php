<?php

namespace App\Http\JsonRpcs;

use App\Models\IntegralMall;
use App\Models\IntegralMallExchange;
use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Config;

class IntegralMallJsonRpc extends JsonRpc {

    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function mallList($params) {
        $where = array();
        $where['groups'] = trim($params->groups);
        if(empty($where['groups'])){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $where['status'] = 1;
        $list = IntegralMall::where($where)->orderBy('release_time','desc')->get()->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }

    /**
     * 积分兑换接口
     * @JsonRpcMethod
     */
    public function integralExchange($params) {
        $userId = intval($params->userId);
        $mallId = intval($params->mallId);
        if(empty($userId) || empty($mallId)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //获取用户的积分额
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $userBase = $client->userBasicInfo(array('userId' =>$userId));
        $integralTotal = isset($userBase['result']['data']['integral']) ? $userBase['result']['data']['integral'] : 1000;
        //判断积分值够不够买该奖品
        $where = array();
        $where['id'] = $mallId;
        $where['status'] = 1;
        $data = IntegralMall::where($where)->get()->toArray();
        //判断数据是否存在
        if(empty($data)){
            throw new OmgException(OmgException::AWARD_NOT_EXIST);
        }
        $data = isset($data[0]) ? $data[0] : array();
        if(empty($data)){
            throw new OmgException(OmgException::AWARD_NOT_EXIST);
        }
        //判断值是否有效
        if($data['integral'] < 1){
            throw new OmgException(OmgException::INTEGRAL_FAIL);
        }
        //判断该用户是否超过了购买
        $whereEX = array();
        $whereEX['user_id'] = $userId;
        $whereEX['mall_id'] = $mallId;
        $whereEX['send_status'] = 1;
        $count = IntegralMallExchange::where($whereEX)->count();
        if($data['user_quantity'] != 0 &&$count >= $data['user_quantity']){
            throw new OmgException(OmgException::EXCEED_FAIL);
        }
        //如果花费大于于拥有的总积分
        if($data['integral'] > $integralTotal) {
            throw new OmgException(OmgException::INTEGRAL_LACK_FAIL);
        }
        //插入交易日志
        $insert = array();
        $insert['user_id'] = $userId;
        $insert['mall_id'] = $mallId;
        $insert['snapshot'] = json_encode($data);
        $insert['send_status'] = 0;
        //发送奖品
        $return = SendAward::sendDataRole($userId,$data['award_type'],$data['award_id'],0,'积分兑换',0,0);
        if($return['status'] === true){
            //调用孙峰接口减去积分
            $url = Config::get("award.reward_http_url");
            $client = new JsonRpcClient($url);
            //用户积分
            $iData['user_id'] = $userId;
            $iData['uuid'] = SendAward::create_guid();
            $iData['source_id'] = 0;
            $iData['name'] = $data['integral']."积分";
            $iData['integral'] = $data['integral'];
            //获取奖品名
            $awardInfo = SendAward::_getAwardInfo($data['award_type'],$data['award_id']);
            if(empty($awardInfo)){
                throw new OmgException(OmgException::AWARD_NOT_EXIST);
            }
            $iData['limit_desc'] = "积分兑换 '".$awardInfo['name']."' 消耗了".$data['integral']."积分";
            //发送接口
            $result = $client->integralUsageRecord($iData);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //修改发送成功人数+1
                IntegralMall::where($where)->increment('send_quantity');
                $insert['send_status'] = 1;
            }else{
                //积分扣除失败
                throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
            }
        }
        $id = IntegralMallExchange::insertGetId($insert);
        if($id && $insert['send_status'] == 1){
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
}
