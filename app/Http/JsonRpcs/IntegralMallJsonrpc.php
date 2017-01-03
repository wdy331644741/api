<?php

namespace App\Http\JsonRpcs;

use App\Models\IntegralMall;
use App\Models\IntegralMallExchange;
use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use App\Http\Controllers\AwardCommonController;
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
        $list = IntegralMall::where($where)
            ->where(function($query) {
                $query->whereNull('start_time')->orWhereRaw('start_time < now()');
            })
            ->where(function($query) {
                $query->whereNull('end_time')->orWhereRaw('end_time > now()');
            })
            ->orderByRaw('id + priority desc')->get()->toArray();
        $awardCommon = new AwardCommonController;
        foreach($list as &$item){
            $params = array();
            $params['award_type'] = $item['award_type'];
            $params['award_id'] = $item['award_id'];
            $awardList = $awardCommon->_getAwardList($params,1);
            if(!empty($awardList) && isset($awardList['name']) && !empty($awardList['name'])){
                $item['name'] = $awardList['name'];
            }else{
                $item['name'] = '';
            }
        }
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
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mallId = intval($params->mallId);
        if(empty($mallId)){
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
        $data = IntegralMall::where($where)
            ->where(function($query) {
                $query->whereNull('start_time')->orWhereRaw('start_time < now()');
            })
            ->where(function($query) {
                $query->whereNull('end_time')->orWhereRaw('end_time > now()');
            })
            ->get()->toArray();
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
        //交易日志数据
        $insert = array();
        $insert['user_id'] = $userId;
        $insert['mall_id'] = $mallId;
        $insert['snapshot'] = json_encode($data);
        $insert['send_status'] = 0;
        //调用孙峰接口减去积分
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //用户积分
        $iData['user_id'] = $userId;
        $iData['uuid'] = SendAward::create_guid();
        //获取奖品名
        $awardInfo = SendAward::_getAwardInfo($data['award_type'],$data['award_id']);
        if(empty($awardInfo)){
            throw new OmgException(OmgException::AWARD_NOT_EXIST);
        }
        $iData['source_id'] = 0;
        $iData['source_name'] = "兑换".$awardInfo['name'];
        $iData['integral'] = $data['integral'];
        $iData['remark'] = $awardInfo['name']." * 1";
        //发送接口
        $result = $client->integralUsageRecord($iData);
        //发送消息&存储到日志
        if (isset($result['result']) && $result['result']) {//成功
            //发送奖品
            $return = SendAward::sendDataRole($userId,$data['award_type'],$data['award_id'],0,'积分兑换');
            if($return['status'] === true){
                //修改发送成功人数+1
                IntegralMall::where($where)->increment('send_quantity');
                $insert['send_status'] = 1;
            }
        }else{
            //积分扣除失败
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }
        //判断是否成功
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
