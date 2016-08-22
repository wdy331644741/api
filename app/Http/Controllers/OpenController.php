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
        $weixin = new Weixin();
        $oauth_url = $weixin->get_authorize_url();
        return redirect($oauth_url);
    }

    //获取用户的open_id
    public function getOpenid(Request $request){
        if(!$request->code){
            return $this->outputJson(10008,array('error_msg'=>'Authorization Fails'));
        }
        $weixin = new Weixin();
        $this->_openid =  $weixin->get_openid($request->code);
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
    public function getWechatBind(){
        global $userId;
        $wxObj = new Weixin();
        $oauth_url = $wxObj->get_authorize_url();
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'userId'=>$userId));
        if(isset($res['result'])){
            if($res['result']['data']){
                return redirect(env('WECHAT_BASE_HOST')."/wechat/bindWechat");
            }else{
                return redirect($oauth_url);
            }
        }
        return redirect($oauth_url);
    }


    //解除绑定
    public function getWechatUnbind(){
        global $userId;
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'userId'=>$userId));
        if(isset($res['result'])){
            if($res['result']['data']){
                return redirect(env('WECHAT_BASE_HOST')."/wechat/unbindWechat");
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/unbindWechat/finish");
            }
        }
        return redirect(env('YY_BASE_HOST')."/open/login?client=fuwuhao&callback=".env('WECHAT_BASE_HOST')."/wechat/unbindWechat");
    }

    //获取响应事件
    public function getEvent(Request $request)
    {
        $this->valid();
        $this->responseMsg();
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $type = $postObj->MsgType;

            //$keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            
            $msgType = "text";
            if($type == 'event'){
                $contentStr = $this->receiveEvent($postObj);
                if($contentStr['key'] == "bind_weixin"){
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr['content']);
                }
            }

            /*if($contentStr !="您的留言我们已经收到，感谢您对我们的关注和支持！"){
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            }  */

            echo $resultStr;

        }else{
            echo "";
            exit;
        }
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
        }
    }

    private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "关注";
                if (isset($object->EventKey)){
                    /**
                     *场景扫码
                     */
                }
                break;
            case "unsubscribe":

                /**
                 *取消关注
                 */

                $content = "取消关注";
                break;
            case "SCAN":
                $content = '您已经关注我们了哟~';
                break;
            case "CLICK":
                if (isset($object->EventKey)){
                    $content['key'] = $object->EventKey;
                }
                $content['content'] = '点击发送消息-Test【<a href="http://www.baidu.com">立即绑定</a>】';
                break;

        }
        return $content;
    }


    private function  checkSignature($request)
    {
        $token = env('WECHAT_TOKEN');
        if(!$token){
            return $this->outputJson(10000,array('error_msg'=>'token is not defined!'));
        }

        $signature = $request->signature;
        $timestamp = $request->timestamp;
        $nonce = $request->nonce;

        $tmpArr = array($token, $timestamp, $nonce);

        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    
}
