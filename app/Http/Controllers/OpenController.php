<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;
use Lib\Weixin;
use Lib\JsonRpcClient;
use Lib\Session;

class OpenController extends Controller
{
    private $_weixin = 'wechat';
    private $_openid;
    private $_user_api_url = "http://account.dev.wanglibao.com/service.php?c=account";

    public function postBind(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'openid' => 'required',
            'open_src' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $model_name = config('open.'.$request->open_src.'.model');
        $model = new $model_name;
        $model->user_id = $request->user_id;
        $model->open_id = $request->open_id;
        $res = $model->save();
        if($res->id){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    public function postLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'open_src' => 'required',
            'openid' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $model_name = config('open.'.$request->open_src.'.model');
        $model = new $model_name;
        $isbind = $model->where('openid',$request->openid)->get();
        if(!empty($isbind)){
            return $this->outputJson(0,array('token'=>$this->getTokenByUserId($isbind->user_id)));
        }else{
            return $this->outputJson(10004,array('error_msg'=>'The User Is UnBind'));
        }
    }

    public function postUnbind(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'openid' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $model_name = config('open.'.$request->open_src.'.model');
        $model = new $model_name;
        $res = $model->where('openid',$request->openid)->delete();
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10001,array('error_msg'=>'Database Error'));
        }
    }

    //---------------------------微信相关----------------------------//

    //wechat_auth_uri
    public function getLogin(Request $request){
        if(isset($request->callback)){
            Session::set('weixin',array('callback'=>$request->callback));
        }
        Session::set('weixin',array('callback'=>'http://www.baidu.com'));
        global $userId;
        if($userId){
            header("Location:$request->callback");
        }else{
            $weixin = new Weixin();
            $oauth_url = $weixin->get_authorize_url();
            header("Location:$oauth_url");
        }
    }

    //获取用户的open_id
    public function getOpenid(Request $request){
        if(!$request->code){
            $this->outputJson(10008,array('error_msg'=>'Authorization Fails'));
        }
        $weixin = new Weixin();
        $this->_openid =  $weixin->get_access_token($request->code);
        $weixin = Session::get('weixin');
        $new_weixin = array();
        if(is_array($weixin)){
            $new_weixin = array_merge($weixin,array('openid'=>$this->_openid));
        }
        Session::set('weixin',$new_weixin);
        $client = new JsonRpcClient($this->_user_api_url);
        $res = $client->accountSignIn(array('channel'=>$this->_weixin,'openId'=>$this->_openid));
        if($res['code'] == 0){
            header("Location:{$weixin['callback']}");
        }
        if(isset($res['error']) && $res['error']['code'] == 1442){
            header("Location:http://weixin.wanglibao.com/user/login");
        }

    }

    //绑定用户
    public function postWechatBind(){
        global $userId;
        $weixin = Session::get('weixin');
        $client = new JsonRpcClient($this->_user_api_url);
        $res = $client->accountBind(array('channel'=>$this->_weixin,'openid'=>$weixin['openid'],'userId'=>$userId));
        return $res;

    }


    //接触绑定
    public function postWechatUnbind(){
        $weixin = Session::get('weixin');
        $client = new JsonRpcClient($this->_user_api_url);
        $res = $client->accountUnbind(array('channel'=>$this->_weixin,'openid'=>$weixin['openid']));
        return $res;
    }

    //测试
    public function getHaha(){
        $request = new Client([
            'base_uri'=>'http://yunying.dev.wanglibao.com',
            'timeout'=>9999.0
        ]);
        $response = $request->get('/cms/content/detail/1');
        if($response->getStatusCode() == 200){
            echo $response->getBody();
        }
    }

    public function getIndex(){
        Session::set('weixin',array('231231','qweqwe'));
    }

    public function getId(){
        dd(Session::get('weixin'));
    }
}
