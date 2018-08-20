<?php

namespace App\Http\JsonRpcs;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;

class FourYearZhengshiJsonrpc extends JsonRpc {

    /**
     *  查询用户领奖状态
     *
     * @JsonRpcMethod
     */
    public function getRedPackList() {
        global $userId;
        $userId = 1716617;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where(['key'=>'4year_hongbao','user_id'=>$userId])->get()->toArray();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($res['text'])
            ];
        }
        $redPack = [
            '4year_hongbao_58'=>[
                'name'=>'58元红包',
                'status'=>0
            ],
            '4year_hongbao_88'=>[
                'name'=>'88元红包',
                'status'=>0
            ],
            '4year_hongbao_288'=>[
                'name'=>'288元红包',
                'status'=>0
            ],
            '4year_hongbao_508'=>[
                'name'=>'508元红包',
                'status'=>0
            ],
            '4year_hongbao_988'=>[
                'name'=>'988元红包',
                'status'=>0
            ],
            '4year_hongbao_1888'=>[
                'name'=>'1888元红包',
                'status'=>0
            ]
        ];
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
        $userId = 1716617;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where(['key'=>'4year_hongbao','user_id'=>$userId])->get()->toArray();
        if($res){

        }
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = '4year_hongbao';
        $userAttr->text = json_encode();
        $res = $userAttr->save();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>11
            ];
        }
    }



}
