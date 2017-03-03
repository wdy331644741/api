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
    public function advancedSendAward() {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取当前用户进阶状态
        $where = array();
        $where['key'] = 'advanced';
        $where['user_id'] = $userId;
        $status = UserAttribute::where($where)->first();
        $not_full = 0;
        foreach($status as $item){
            if($item == 0){
                $not_full = 1;
            }
        }
        //判断进阶是否参与满
        if($status->number != 9 || $not_full == 1){
            throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
        }
        //发奖
        $data = SendAward::ActiveSendAward($userId,"advanced_big_prize");
        if(isset($data[0]) && isset($data[0]['status']) && $data[0]['status'] == true){
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
