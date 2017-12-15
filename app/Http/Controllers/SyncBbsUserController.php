<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\Bbs\User;
use App\Exceptions\OmgException;

class SyncBbsUserController extends Controller
{
    //
    function postSync(Request $request)
    {
        $requests = $request->all();

        if(empty($request['user_id'])){

            return  $this->outputJson('0',["message"=>"用户id为空"]);
        }
        //

        $userData =[];
        if(!empty($request['avater'])){
            $userData['head_img'] = $request['avater'];
        }
        if(!empty($request['nickname'])){
            $userData['nickname'] = $request['nickname'];
        }

        $res = User::where(['nickname'=>$request['nickname']])->whereNotIn('user_id', [$request["user_id"]])->first();


        if($res){
            //有昵称抛出重复异常
            return  $this->outputJson('10001',["message"=>"昵称重复"]);
        }
        //是否登陆过社区
        $isExitUser = User::where(['user_id'=>$request['user_id']])->first();
        if(!$isExitUser){
            return  $this->outputJson('0',['user_id'=>$request['user_id'],"message"=>"不存在该用户"]);
        }

        $updateRes = User::where(['user_id'=>$request['user_id']])
                    ->update($userData);
        if($updateRes){
           return  $this->outputJson('0',['user_id'=>$request['user_id'],"message"=>"更新成功"]);
        }else{
            return  $this->outputJson('10002',["message"=>"更新失败"]);

        }


    }

}
