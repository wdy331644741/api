<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Lib\Weixin;
use Lib\JsonRpcClient;
use Lib\Session;
use Lib\McQueue;

class OpenController extends Controller
{
    private $_weixin = 'wechat';
    private $_openid;

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
        if(!isset($request->callback)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        Session::set('weixin',array('callback'=>$request->callback));
        global $userId;
        if($userId){
            return redirect($request->callback);
        }else{
            $weixin = new Weixin();
            $oauth_url = $weixin->get_authorize_url();
            return redirect($oauth_url);
        }
    }

    //获取用户的open_id
    public function getOpenid(Request $request){
        if(!$request->code){
            return $this->outputJson(10008,array('error_msg'=>'Authorization Fails'));
        }
        $weixin = new Weixin();
        $this->_openid =  $weixin->get_access_token($request->code);
        $weixin = Session::get('weixin');
        $new_weixin = array();
        if(is_array($weixin)){
            $new_weixin = array_merge($weixin,array('openid'=>$this->_openid));
        }
        Session::set('weixin',$new_weixin);
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountSignIn(array('channel'=>$this->_weixin,'openId'=>$this->_openid));
        if(isset($res['error']) && $res['error']['code'] == 1442){
            if(isset($weixin['callback'])){
                return redirect(env('WECHAT_BASE_HOST')."/wechat/verify?client=fuwuhao&next=".$weixin['callback']);
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/verify");
            }
        }
        if(!isset($res['error'])){
            if(isset($weixin['callback'])){
                return redirect($weixin['callback']);
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/");
            }
        }
    }

    //绑定用户
    public function postWechatBind(){
        global $userId;
        $weixin = Session::get('weixin');
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountBind(array('channel'=>$this->_weixin,'openId'=>$weixin['openid'],'userId'=>$userId));
        if(!isset($res['error'])){
            $mcQueue = new McQueue();
            $data =  ['user_id' => $userId ,'datetime' => date('Y-m-d H:i:s')];
            $putStatus = $mcQueue->put('binding',$data);
            if(!$putStatus)
            {
                $error = $mcQueue->getErr();//  ['err_code' => $mcQueue->errCode ,'err_msg' => $mcQueue->errMsg];
                file_put_contents(storage_path('logs/McQueue-Error-'.date('Y-m-d')).'.log','【userId:'.$userId.'-err_code:'.$error['err_code'].'-err_msg:'.$error['err_msg'].'】-Send Msg Fails'.date('Y-m-d'),FILE_APPEND);
            }
        }
        return $res;

    }


    //接触绑定
    public function postWechatUnbind(){
        $weixin = Session::get('weixin');
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountUnbind(array('channel'=>$this->_weixin,'openId'=>$weixin['openid']));
        return $res;
    }
    
}
