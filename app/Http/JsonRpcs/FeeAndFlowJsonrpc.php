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
        //运营商类型1移动2联通3电信
        $type = isset($params->type) && !empty($params->type) ? $params->type : 0;
        if(strlen($phone) != 11 || $id <= 0 || $type <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $FeeAndFlowBasic = new FeeAndFlowBasic();
        //获取殴飞相应的面值
        $values = FeeAndFlowBasic::getValues($id,1,$type);
        $perValue = $values['perValue'];
        if($perValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //配置的面值
        $configValue = $values['configValue'];
        if($configValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //唯一订单id(殴飞)
        $uuid = FeeAndFlowBasic::create_guid();
        //生成订单
        $id = LifePrivilege::insertGetId([
            'user_id' => $userId,
            'phone' => $phone,
            'order_id' => $uuid,
            'amount' => $configValue,
            'amount_of' => $perValue,
            'name' => $values['name'],
            'type' => 1,
            'operator_type' => $type,
            'created_at'=>date("Y-m-d H:i:s"),
            'updated_at'=>date("Y-m-d H:i:s")
        ]);
        //事务开始
        DB::beginTransaction();
        $orderData = LifePrivilege::where('id',$id)->lockForUpdate()->first();
        //判断是否可充值
        $isCan = $FeeAndFlowBasic->FeeInfo($phone,$values['name']);
        if($isCan['retcode'] != 1){
            LifePrivilege::where('id',$orderData->id)->update([
                'remark_of' => json_encode($isCan)
            ]);
            //事务提交结束
            DB::commit();
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }
        //充值
        $res = $FeeAndFlowBasic->FeeSend($phone,$values['name'],$uuid);
        //充流量成功
        if($res['retcode'] == 1){
            //扣款
            $config = Config::get("feeandflow");
            $record_id = $config['activity_id']+$orderData->id;
            $uuidActivity = SendAward::create_guid();
            $debitRes = Func::decrementAvailable($userId,$record_id,$uuidActivity,$configValue,'call_cost_refill');
            $debitStatus = 0;
            if(isset($debitRes['result'])){
                //扣款成功
                $debitStatus = 1;
            }
            $orderStatus = 1;
            if(isset($res['game_state']) && $res['game_state'] == 9){
                $orderStatus = 2;
            }

            //修改订单状态
            LifePrivilege::where('id',$orderData->id)->update([
                'debit_status' => $debitStatus,
                'order_status' => $orderStatus,
                'remark' => json_encode($debitRes),
                'remark_of' => json_encode($res)
            ]);
            //事务提交结束
            DB::commit();
            if($orderStatus == 2){
                throw new OmgException(OmgException::FAILED_RECHARGE_OFPAY);
            }
            return [
                'code' => 0,
                'message' => 'success'
            ];
        }
        //修改订单状态为充值失败
        LifePrivilege::where('id',$orderData->id)->update([
            'order_status' => 2,
            'remark_of' => json_encode($res)
        ]);
        //事务提交结束
        DB::commit();
        return [
            'code' => -1,
            'message' => isset($res['err_msg']) ? $res['err_msg']: "failed"
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
        //运营商类型1移动2联通3电信
        $type = isset($params->type) && !empty($params->type) ? $params->type : 0;
        if(strlen($phone) != 11 || $id <= 0 || $type <= 0){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $FeeAndFlowBasic = new FeeAndFlowBasic();
        //获取殴飞相应的面值
        $values = FeeAndFlowBasic::getValues($id,2,$type);
        $perValue = $values['perValue'];
        if($perValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //配置的面值
        $configValue = $values['configValue'];
        if($configValue <= 0){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }

        //唯一订单id(殴飞)
        $uuid = FeeAndFlowBasic::create_guid();
        //生成订单
        $id = LifePrivilege::insertGetId([
            'user_id' => $userId,
            'phone' => $phone,
            'order_id' => $uuid,
            'amount' => $configValue,
            'amount_of' => $perValue,
            'name' => $values['name'],
            'type' => 2,
            'operator_type' => $type,
            'created_at'=>date("Y-m-d H:i:s"),
            'updated_at'=>date("Y-m-d H:i:s")
        ]);
        //事务开始
        DB::beginTransaction();
        $orderData = LifePrivilege::where('id',$id)->lockForUpdate()->first();

        //判断是否可充值
        $isCan = $FeeAndFlowBasic->FlowInfo($phone,$perValue,$values['name']);
        if($isCan['retcode'] != 1){
            LifePrivilege::where('id',$orderData->id)->update([
                'remark_of' => json_encode($isCan)
            ]);
            //事务提交结束
            DB::commit();
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }

        //发送流量
        $res = $FeeAndFlowBasic->FlowSend($phone,$perValue,$values['name'],$uuid);
        //充流量成功
        if($res['retcode'] == 1){
            //扣款
            $config = Config::get("feeandflow");
            $record_id = $config['activity_id']+$orderData->id;
            $uuidActivity = SendAward::create_guid();
            $debitRes = Func::decrementAvailable($userId,$record_id,$uuidActivity,$configValue,'networks_flow_refill');
            $debitStatus = 0;
            if(isset($debitRes['result'])){
                //扣款成功
                $debitStatus = 1;
            }
            $orderStatus = 1;
            if(isset($res['game_state']) && $res['game_state'] == 9){
                $orderStatus = 2;
            }
            //修改订单状态
            LifePrivilege::where('id',$orderData->id)->update([
                'debit_status' => $debitStatus,
                'order_status' => $orderStatus,
                'remark' => json_encode($debitRes),
                'remark_of' => json_encode($res)
            ]);
            //事务提交结束
            DB::commit();
            if($orderStatus == 2){
                //如果失败
                throw new OmgException(OmgException::FAILED_RECHARGE_OFPAY);
            }
            return [
                'code' => 0,
                'message' => 'success'
            ];
        }
        //修改订单状态为充流量失败
        LifePrivilege::where('id',$orderData->id)->update([
            'order_status' => 2,
            'remark_of' => json_encode($res)
        ]);
        //事务提交结束
        DB::commit();
        return [
            'code' => -1,
            'message' => isset($res['err_msg']) ? $res['err_msg']: "failed"
        ];
    }

}
