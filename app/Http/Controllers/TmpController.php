<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class TmpController extends Controller
{

    public function getAuth(Request $request){
        if(!isset($request->callback)){
            return "跳转地址不正确，点击 <a href=\"/\">返回首页</a>";
        }
        $session = new Session();
        
        if(empty($wxSession)){
            $session->set('weixin',array('userinfo_callback'=>$request->callback));
        }else{
            $session->set('weixin',array_merge($wxSession,array('userinfo_callback'=>$request->callback)));
        }

        $weixin = new Weixin();
        $oauth_url = $weixin->get_authorize_url('snsapi_userinfo',env('WECHAT_SHARE_REDIRECT_URI'));
        return redirect($oauth_url);
    }
}
