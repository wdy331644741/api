<?php

namespace App\Http\JsonRpcs;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\SendAward;
use DB;

class FourYearZhengshiJsonrpc extends JsonRpc {

    /**
     *  查询用户领奖状态
     *
     * @JsonRpcMethod
     */
    public function getRedPackList() {
        global $userId;

        $redPack = [
            '4year_hongbao_58'=>[
                'name'=>'58',
                'status'=>0
            ],
            '4year_hongbao_88'=>[
                'name'=>'88',
                'status'=>0
            ],
            '4year_hongbao_288'=>[
                'name'=>'288',
                'status'=>0
            ],
            '4year_hongbao_508'=>[
                'name'=>'508',
                'status'=>0
            ],
            '4year_hongbao_988'=>[
                'name'=>'988',
                'status'=>0
            ],
            '4year_hongbao_1888'=>[
                'name'=>'1888',
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
        $res = UserAttribute::where(['key'=>'4year_hongbao','user_id'=>$userId])->first();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($res->text)
            ];
        }
        
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = '4year_hongbao';
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
     *  用户领取红包接口
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
        $res = UserAttribute::where(['key'=>'4year_hongbao','user_id'=>$userId])->first();
        if($res){
            DB::beginTransaction();
            $data = SendAward::ActiveSendAward($userId,$params->key);
            if(isset($data[0]['status'])){
                $redPackList = json_decode($res->text,1);
                $redPackList[$params->key]['status'] = 1;
                $updatestatus = UserAttribute::where(['key'=>'4year_hongbao','user_id'=>$userId])->update(['text'=>json_encode($redPackList)]);
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
