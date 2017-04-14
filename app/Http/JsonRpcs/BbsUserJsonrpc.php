<?php

namespace App\Http\JsonRpcs;
use App\Models\Bbs\User;
use Lib\JsonRpcClient;




class BbsUserJsonRpc extends JsonRpc {

    /**
     *  用户上传头像
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserHeadimg($param){
        global $user_id;

        $res = User::select('user_id',$user_id)->update(['head_img'=>$param->head_img]);
        if($res){
            $data = array(
                'user_id'=>$user_id,
                'head_img'=>$param->head_img,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data
            );

        }else{
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '上传头像失败'
            );
        }

    }

    /**
     *  用户更改昵称
     *
     * @JsonRpcMethod
     */
    public function updateBbsUserNickname($param){
        global $user_id;

        $res = User::select('user_id',$user_id)->update(['head_img'=>$param->nickname]);
        if($res){
            $data = array(
                'user_id'=>$user_id,
                'head_img'=>$param->nickname,
            );
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data
            );

        }else{
            return array(
                'code' => -1,
                'message' => 'fail',
                'data' => '更改昵称失败'
            );
        }

    }
}

