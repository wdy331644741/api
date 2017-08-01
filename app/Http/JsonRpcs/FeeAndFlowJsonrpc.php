<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\LifePrivilege;
use App\Service\FeeAndFlowBasic;
use App\Service\Func;
use App\Service\SendAward;
use DB,Config;

class FeeAndFlowJsonRpc extends JsonRpc {
    /**
     *  查询归属地返回
     *
     * @JsonRpcMethod
     */
    public function attributionInfo($params){
        $result = ['attribution'=>'','fee_list'=>[],'flow_list'=>[]];
        //手机号
        $phone = isset($params->phone) && !empty($params->phone) ? $params->phone : 0;
        if(strlen($phone) != 11){
            throw new OmgException(OmgException::VALID_PHONE_TYPE_FAIL);
        }
        //1话费2流量
        $type = isset($params->type) && !empty($params->type) ? $params->type : 0;
        if($type <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $phoneNum = substr($phone,0,7);
        $FeeAndFlowBasic = new FeeAndFlowBasic();
        $res = $FeeAndFlowBasic->AttributionInfo($phoneNum);
        $res = explode("|",mb_convert_encoding($res, "UTF-8", "GB2312"));
        if(count($res) == 1){
            throw new OmgException(OmgException::VALID_PHONE_TYPE_FAIL);
        }
        if(count($res) == 3){
            $attr = mb_substr($res[1],0,2).$res[2];
            $result['attribution'] = $attr;
        }
        //获取运营商拼音和类型
        $operatorList = FeeAndFlowBasic::getOperator($res[2],$type);
        $result['fee_list'] = $operatorList['fee_list'];
        $result['flow_list'] = $operatorList['flow_list'];
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }
    /**
     *  话费充值
     *
     * @JsonRpcMethod
     */
    public function feeSendToPhone($params)
    {
        global $userId;

        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //手机号
        $phone = isset($params->phone) && !empty($params->phone) ? $params->phone : 0;
        //商品id
        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        //交易密码
        $tradePwd = isset($params->tradePwd) && !empty($params->tradePwd) ? $params->tradePwd : 0;
        if(strlen($phone) != 11 || $id <= 0 || $tradePwd <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //验证交易密码
        $tradePwdRes = Func::checkTradePwd($tradePwd);
        if(!isset($tradePwdRes['result'])){
            return [
                'code' => -2,
                'message' => $tradePwdRes['error']['message']
            ];
        }
        //获取殴飞相应的价格
        $values = FeeAndFlowBasic::getValues($id,1);
        $operator_type = $values['operatorType'];//运营商类型1移动2联通3电信
        $name = $values['name'];//面值
        $perValue = $values['perValue'];//殴飞价格
        if($perValue <= 0 || $operator_type <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //配置的价格
        $configValue = $values['configValue'];//后台配置的价格
        if($configValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //判断余额是否足够
        $amountIsEnough = FeeAndFlowBasic::AmountIsEnough($userId,$configValue);
        if(!$amountIsEnough){
            throw new OmgException(OmgException::FAILED_AMOUNT_NOT_ENOUGH);
        }
        //判断是否可充值
        $FeeAndFlowBasic = new FeeAndFlowBasic();
        $isCan = $FeeAndFlowBasic->FeeInfo($phone,$name);
        if($isCan['retcode'] != 1){
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }
        //生成订单操作
        //事务开始
        DB::beginTransaction();
        $res = $FeeAndFlowBasic->CreateOrders($userId,$phone,$name,$perValue,$configValue,1,$operator_type);
        //事务提交结束
        DB::commit();

        //返回值
        if(isset($res['code']) && $res['code'] == 0){
            return [
                'code' => 0,
                'message' => 'success'
            ];
        }
        //订单失败
        if(isset($res['code']) && $res['code'] == -1){
            throw new OmgException(OmgException::FAILED_RECHARGE_OFPAY);
        }
        //扣款失败
        if(isset($res['code']) && $res['code'] == -2){
            throw new OmgException(OmgException::FAILED_AMOUNT_REDUCE);
        }
        return [
            'code' => -1,
            'message' => 'failed',
            'data' => $res
        ];
    }
    /**
     *  流量充值
     *
     * @JsonRpcMethod
     */
    public function flowSendToPhone($params)
    {
        global $userId;

        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //手机号
        $phone = isset($params->phone) && !empty($params->phone) ? $params->phone : 0;
        //商品ID
        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        //交易密码
        $tradePwd = isset($params->tradePwd) && !empty($params->tradePwd) ? $params->tradePwd : 0;
        if(strlen($phone) != 11 || $id <= 0 || $tradePwd <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //验证交易密码
        $tradePwdRes = Func::checkTradePwd($tradePwd);
        if(!isset($tradePwdRes['result'])){
            return [
                'code' => -2,
                'message' => $tradePwdRes['error']['message']
            ];
        }
        //获取殴飞相应的价格
        $values = FeeAndFlowBasic::getValues($id,2);
        $operator_type = $values['operatorType'];//运营商类型1移动2联通3电信
        $name = $values['name'];//面值
        $perValue = $values['perValue'];//殴飞对应的价格
        if($perValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //获取配置的价格
        $configValue = $values['configValue'];//后台配置的价格
        if($configValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //判断余额是否足够
        $amountIsEnough = FeeAndFlowBasic::AmountIsEnough($userId,$configValue);
        if(!$amountIsEnough){
            throw new OmgException(OmgException::FAILED_AMOUNT_NOT_ENOUGH);
        }
        //判断是否可充值
        $FeeAndFlowBasic = new FeeAndFlowBasic();
        $isCan = $FeeAndFlowBasic->FlowInfo($phone,$perValue,$name);
        if($isCan['retcode'] != 1){
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }
        //生成订单操作
        //事务开始
        DB::beginTransaction();
        $res = $FeeAndFlowBasic->CreateOrders($userId,$phone,$name,$perValue,$configValue,2,$operator_type);
        //事务提交结束
        DB::commit();

        //返回值
        if(isset($res['code']) && $res['code'] == 0){
            return [
                'code' => 0,
                'message' => 'success'
            ];
        }
        //订单失败
        if(isset($res['code']) && $res['code'] == -1){
            throw new OmgException(OmgException::FAILED_RECHARGE_OFPAY);
        }
        //扣款失败
        if(isset($res['code']) && $res['code'] == -2){
            throw new OmgException(OmgException::FAILED_AMOUNT_REDUCE);
        }
        return [
            'code' => -1,
            'message' => 'failed',
            'data' => $res
        ];
    }

}
