<?php

namespace App\Http\Controllers;

use App\Service\Func;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\TmpWechatUser;
use Lib\Weixin;
use Lib\Session;
use App\Service\GlobalAttributes;

class TmpController extends Controller
{

    public function getAuth(Request $request){
        if(!isset($request->callback)){
            return "跳转地址不正确，点击 <a href=\"/\">返回首页</a>";
        }
        $session = new Session();
        $session->set('tmp_callback_url',$request->callback);
        $weixin = new Weixin();
        $oauth_url = $weixin->get_authorize_url('snsapi_userinfo',env('WECHAT_TMP_REDIRECT_URI'));
        return redirect($oauth_url);
    }


    /**
     * 获取用户的信息
     * @param Request $request
     * @return mixed
     */
    public function getUserInfo(Request $request)
    {
        $session = new Session();
        $callback_url = $session->get('tmp_callback_url');

        if ($callback_url) {
            $userinfo_callback = $callback_url;
        } else {
            return "跳转地址不正确，点击 <a href=\"/\">返回首页</a>";
        }
        if (!$request->code) {
            return "未对公众号授权，无法参与活动，点击 <a href=\"/\">返回首页</a>";
        }
        $weixin = new Weixin();
        $data = $weixin->get_web_access_token($request->code);
        if (!$data) {
            return redirect(convertUrlQuery($userinfo_callback).'wlerrcode=40002');//获取access_token失败
        }
        $this->_openid = $data['openid'];
        if ($this->_openid) {
            $encode = authcode($this->_openid,'ENCODE');
            $userData = TmpWechatUser::where('openid', $this->_openid)->first();
            if (!$userData) {
                $userData = $weixin->get_web_user_info($data['access_token'], $data['openid']);
                if (!$userData) {
                    return redirect(convertUrlQuery($userinfo_callback).'id='.$encode.'&wlerrcode=40003');//拉取用户信息失败
                }
                $wxModel = new TmpWechatUser();
                $winStr = GlobalAttributes::getText('hd_jianmianhui');
                if(strpos($winStr,$this->_openid) !== false){
                    $wxModel->isdefault = 1;
                }
                //存储微信用户数据
                $wxModel->openid = $userData['openid'];
                $wxModel->sex = $userData['sex'];
                $wxModel->nick_name = $userData['nickname'];
                $wxModel->province = $userData['province'];
                $wxModel->city = $userData['city'];
                $wxModel->country = $userData['country'];
                $wxModel->headimgurl = $userData['headimgurl'];
                $wxModel->save();
                return redirect(convertUrlQuery($userinfo_callback).'id='.$encode);
            }
            return redirect(convertUrlQuery($userinfo_callback).'id='.$encode.'&wlerrcode=40004');//已经获取过用户信息
        }

        return redirect(convertUrlQuery($userinfo_callback).'wlerrcode=40005');//获取openid失败
    }
}
