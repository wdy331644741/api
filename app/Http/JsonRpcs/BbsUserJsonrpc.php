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
                'data' => ''
            );
        }



    }
}

