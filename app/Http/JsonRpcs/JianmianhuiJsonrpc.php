<?php

namespace App\Http\JsonRpcs;
use App\Models\TmpWechatUser;
use Validator;
use App\Exceptions\OmgException;




class JianmianhuiJsonRpc extends JsonRpc {
    /**
     *
     *
     * 见面会签到所有用户
     *
     * @JsonRpcMethod
     */
    public  function getJianmianhuiUserInfo($params){
       $userInfo = TmpWechatUser::where(["is_signin"=>1])->orderBy('id', 'desc')->get();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $userInfo,
        ];
    }
    /**
     *
     *
     * 见面会用户签到接口
     *
     * @JsonRpcMethod
     */
    public function singInJianmianhuiUser($params){
        $validator = Validator::make(get_object_vars($params), [
            'openid'=>'required|exists:tmp_wecaht_users,openid',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        $res = TmpWechatUser::where(["openid"=>$params->openid])->first();

        if ($res->is_signin){
            throw new OmgException(OmgException::ALREADY_SIGNIN);
        }else{
            $updateRes = TmpWechatUser::where(["openid"=>$params->openid])->update(["is_signin"=>1]);

            if($updateRes){
                $res->is_signin = 1;
                return [
                    'code' => 0,
                    'message' => 'success',
                    'data' => $res,
                ];
            }else{
                throw new OmgException(OmgException::DATABASE_ERROR);
            }
        }
    }
    /**
     *
     *
     * 见面会用户抽奖接口
     *
     * @JsonRpcMethod
     */
     public  function getJianmianhuiUseAward($params){
         //后台设置优先获奖用户
         $IsAwardDefault = TmpWechatUser::where(["isdefault"=>"1","iswin"=>0])->get()->toArray();
         if($IsAwardDefault){
             //获取默认抽奖用户
             $awardDefaultUserKey = array_rand($IsAwardDefault);
             //将获奖用户iswin 置为1
             $res = TmpWechatUser::where(["openid"=>$IsAwardDefault["$awardDefaultUserKey"]["openid"]])->update(["iswin"=>1]);
             if($res){
                 //success
                 $IsAwardDefault["$awardDefaultUserKey"]["iswin"] = 1;
                 return [
                     'code' => 0,
                     'message' => 'success',
                     'data' => $IsAwardDefault["$awardDefaultUserKey"],
                 ];
             }else{
                 throw new OmgException(OmgException::DATABASE_ERROR);
             }
         }else{
            //默认用户抽取完成
             $IsAward = TmpWechatUser::where(["isdefault"=>"0","iswin"=>0,"is_signin"=>1])->get()->toArray();
             if($IsAward){
                $awardUserKey = array_rand($IsAward);
                $res = TmpWechatUser::where(["openid"=>$IsAward["$awardUserKey"]["openid"]])->update(["iswin"=>1]);
                 if($res){
                     //success
                     $IsAward["$awardUserKey"]["iswin"] = 1;
                     return [
                         'code' => 0,
                         'message' => 'success',
                         'data' => $IsAward["$awardUserKey"],
                     ];
                 }else{
                     throw new OmgException(OmgException::DATABASE_ERROR);
                 }
             }else{
                 throw new OmgException(OmgException::NO_DATA);
             }
         }


     }
    /**
     *
     *
     * 获取所有中奖用户信息
     *
     * @JsonRpcMethod
     */
    public function getAwardJianmianhuiUser($params){
        $userAwardInfo = TmpWechatUser::where(["iswin"=>1])->get();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $userAwardInfo,
        ];



    }
}

