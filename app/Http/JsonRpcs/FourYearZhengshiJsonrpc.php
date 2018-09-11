<?php

namespace App\Http\JsonRpcs;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\SendAward;
use DB;

class FourYearZhengshiJsonrpc extends JsonRpc {

    /**
     * 查询用户领奖状态
     * 4周年领取红包
     * 中秋领取红包
     *
     * @JsonRpcMethod
     */
    public function getRedPackList() {
        global $userId;

        $redPack = [
            'zhongqiu_hongbao_16'=>[
                'name'=>'16',
                'status'=>0
            ],
            'zhongqiu_hongbao_66'=>[
                'name'=>'66',
                'status'=>0
            ],
            'zhongqiu_hongbao_126'=>[
                'name'=>'126',
                'status'=>0
            ],
            'zhongqiu_hongbao_266'=>[
                'name'=>'266',
                'status'=>0
            ],
            'zhongqiu_hongbao_506'=>[
                'name'=>'506',
                'status'=>0
            ],
            'zhongqiu_hongbao_816'=>[
                'name'=>'816',
                'status'=>0
            ]
        ];

        if(!$userId) {//未登录的时候也返回红包列表
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $redPack
            ];
        }
        $res = UserAttribute::where(['key'=>'zhongqiu_hongbao','user_id'=>$userId])->first();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($res->text)
            ];
        }
        
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = 'zhongqiu_hongbao';
        $userAttr->text = json_encode($redPack);
        $res = $userAttr->save();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $redPack
            ];
        }
    }

    /**
     * 用户领取红包接口
     * 4周年领取红包
     * 中秋领取红包
     *
     * @JsonRpcMethod
     */
    public function receiveRedPack($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where(['key'=>'zhongqiu_hongbao','user_id'=>$userId])->first();
        if($res){
            DB::beginTransaction();
            $data = SendAward::ActiveSendAward($userId,$params->key);
            if(isset($data[0]['status'])){
                $redPackList = json_decode($res->text,1);
                $redPackList[$params->key]['status'] = 1;
                $updatestatus = UserAttribute::where(['key'=>'zhongqiu_hongbao','user_id'=>$userId])->update(['text'=>json_encode($redPackList)]);
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
        DB::rollback();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>isset($data['msg']) ? $data['msg'] : '领取失败'
        ];
    }

    /**
     *  分享领取体验金
     *
     * @JsonRpcMethod
     */
    public function receiveExperience() {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where(['key'=>'4year_share_receive_experience','user_id'=>$userId])->value('number');
        if($res){
            return [
                'code' => -1,
                'message' => 'fail',
                'data' => '已领取过奖励'
            ];
        }
        DB::beginTransaction();
        $data = SendAward::ActiveSendAward($userId,'4year_share_receive_experience');
        if(isset($data[0]['status'])){
            $userAttr = new UserAttribute();
            $userAttr->user_id = $userId;
            $userAttr->key = '4year_share_receive_experience';
            $userAttr->number = 1;
            $cRes = $userAttr->save();
        }
        if(isset($cRes)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'领取成功'
            ];
        }
        DB::rollback();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>'领取失败'
        ];
    }

}
