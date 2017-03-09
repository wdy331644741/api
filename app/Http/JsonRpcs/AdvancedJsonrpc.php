<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\UserAttribute;
use App\Service\Func;
use App\Service\SendAward;
use DB;

class AdvancedJsonRpc extends JsonRpc {

    /**
     *  获取当前进阶状态
     *
     * @JsonRpcMethod
     */
    public function getAdvancedStatus() {
        global $userId;
        $result = ['number'=> 0 ,'statusList' => []];
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取当前用户进阶状态
        $where = array();
        $where['key'] = 'advanced';
        $where['user_id'] = $userId;
        $status = UserAttribute::where($where)->first();
        if(empty($status)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $text = json_decode($status->text,1);
        $result['number'] = $status->number;
        $result['statusList'] = $text;
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }

    /**
     *  终极大奖发送
     *
     * @JsonRpcMethod
     */
    public function advancedSendAward($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mark = trim($params->mark);
        if(empty($mark)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        //获取当前用户进阶状态
        $where = array();
        $where['key'] = 'advanced';
        $where['user_id'] = $userId;
        $data = UserAttribute::where($where)->first();
        if(isset($data->number) && isset($data->text) && !empty($data->text)){
            $status = json_decode($data->text,1);
            if(isset($status['advanced_signin_3']) && isset($status['advanced_invite_3']) && isset($status['advanced_wechat_binding_first']) &&
                isset($status['advanced_target_term_1']) && isset($status['advanced_target_term_3']) && isset($status['advanced_target_term_6'])
                && isset($status['advanced_target_term_12'])
            ){
                if(!empty($status) && $mark == "active"){
                    //活跃分子
                    if($status['advanced_signin_3'] == 1 && $status['advanced_wechat_first'] == 1 && $status['advanced_invite_3'] == 1){
                        //发奖
                        $sendData = SendAward::ActiveSendAward($userId,"advanced_integral_8888");
                    }else{
                        throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
                    }
                }elseif(!empty($status) && $mark == "investment"){
                    //首投1、3、6、12标奖励
                    if($status['advanced_target_term_1'] == 1 && $status['advanced_target_term_3'] == 1 && $status['advanced_target_term_6'] == 1 && $status['advanced_target_term_12'] == 1){
                        //发奖
                        $sendData = SendAward::ActiveSendAward($userId,"advanced_integral_15000");
                    }else{
                        throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
                    }
                }elseif(!empty($status) && $mark == "ultimate"){
                    //终极大奖
                    $not_full = 0;
                    foreach($status as $item){
                        if($item == 0){
                            $not_full = 1;
                        }
                    }
                    //判断进阶是否参与满
                    if($not_full == 1){
                        throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
                    }
                    //发奖
                    $sendData = SendAward::ActiveSendAward($userId,"advanced_big_prize");
                }
            }
        }
        if(isset($sendData[0]) && isset($sendData[0]['status']) && $sendData[0]['status'] == true){
            //成功
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data[0]
            );
        }else{
            //失败
            return array(
                'code' => -1,
                'message' => isset($data['msg']) ? $data['msg'] : ""
            );
        }
    }

}
