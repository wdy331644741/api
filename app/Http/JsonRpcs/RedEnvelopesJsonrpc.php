<?php

namespace App\Http\JsonRpcs;


use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use Config,DB;

class RedEnvelopesJsonRpc extends JsonRpc {


    /**
     * 红包领取状态
     *
     * @JsonRpcMethod
     */
    public function redEnvelopesInfo() {
        global $userId;

        $config = Config::get('red_envelopes');
        $awards = isset($config['awards']) ? $config['awards'] : [];
        $key = isset($config['key']) ? $config['key'] : '';
        if(!$userId) {//未登录的时候也返回红包列表
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $awards
            ];
        }
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->first();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($res->text)
            ];
        }
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = $key;
        $userAttr->text = json_encode($awards);
        $res = $userAttr->save();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $awards
            ];
        }
    }
    /**
     * 红包领取状态
     *
     * @JsonRpcMethod
     */
    public function redEnvelopesDrew($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('red_envelopes');
        $key = isset($config['key']) ? $config['key'] : '';
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $data = SendAward::ActiveSendAward($userId,$params->key);
            if(isset($data[0]['status'])){
                $redPackList = json_decode($res->text,1);
                $redPackList[$params->key]['status'] = 1;
                $updatestatus = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->update(['text'=>json_encode($redPackList)]);
            }
        }
        if(isset($updatestatus)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'领取成功'
            ];
        }
        DB::rollBack();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>isset($data['msg']) ? $data['msg'] : '领取失败'
        ];
    }
}
