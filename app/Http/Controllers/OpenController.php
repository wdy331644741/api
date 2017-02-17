<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\FlowRechargeLog;
use App\Models\WechatUser;
use Illuminate\Http\Request;
use Lib\Weixin;
use Lib\JsonRpcClient;
use Lib\Session;
use Config;
use App\Http\JsonRpcs\ActivityJsonRpc;
use App\Models\Channel;
use App\Service\SendMessage;

class OpenController extends Controller
{
    private $_weixin = 'wechat';
    private $_openid;

    //---------------------------微信相关----------------------------//

    //wechat_auth_uri
    public function getLogin(Request $request){
        if(!isset($request->callback)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $session = new Session();
        $wxSession = $session->get('weixin');
        if(empty($wxSession)){
            $session->set('weixin',array('callback'=>$request->callback));
        }else{
            $session->set('weixin',array_merge($wxSession,array('callback'=>$request->callback)));
        }
        $weixin = new Weixin();
        $oauth_url = $weixin->get_authorize_url();
        return redirect($oauth_url);
    }

    //wwchat_userinfo_auth_uri
    public function getUserinfoLogin(Request $request){
        if(!isset($request->callback)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $session = new Session();
        $wxSession = $session->get('weixin');
        if(empty($wxSession)){
            $session->set('weixin',array('userinfo_callback'=>$request->callback));
        }else{
            $session->set('weixin',array_merge($wxSession,array('userinfo_callback'=>$request->callback)));
        }

        $weixin = new Weixin();
        $oauth_url = $weixin->get_authorize_url('snsapi_userinfo',env('WECHAT_SHARE_REDIRECT_URI'));
        return redirect($oauth_url);
    }

    /**
     * 获取用户的open_id
     * @param Request $request
     * @return mixed
     */
    public function getOpenid(Request $request){
        if(!$request->code){
            return $this->outputJson(10008,array('error_msg'=>'Authorization Fails'));
        }
        $weixin = new Weixin();
        $this->_openid =  $weixin->get_openid($request->code);
        $session = new Session();
        $weixin = $session->get('weixin');
        $new_weixin = array();
        if(is_array($weixin)){
            $new_weixin = array_merge($weixin,array('openid'=>$this->_openid));
        }
        $session->set('weixin',$new_weixin);
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountSignIn(array('channel'=>$this->_weixin,'openId'=>$this->_openid));
        if(isset($res['error']) && $res['error']['code'] == 1442){
            if(isset($weixin['callback'])){
                return redirect(env('WECHAT_BASE_HOST')."/wechat/verify?client=fuwuhao&next=".$weixin['callback']);
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/verify?client=fuwuhao");
            }
        }
        if(!isset($res['error'])){
            if(isset($weixin['callback'])){
                return redirect($weixin['callback']);
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/");
            }
        }
        return redirect(env('WECHAT_BASE_HOST')."/wechat/");
    }

    /**
     * 获取用户的信息
     * @param Request $request
     * @return mixed
     */
    public function getUserInfo(Request $request){
        $session = new Session();
        $wxSession= $session->get('weixin');
        if(!$request->code){
            return redirect($this->convertUrlQuery($wxSession['userinfo_callback']).'code=40001');//用户未授权或者授权失败
        }
        $weixin = new Weixin();
        $data = $weixin->get_web_access_token($request->code);
        if(!$data){
            return redirect($this->convertUrlQuery($wxSession['userinfo_callback']).'code=40002');//获取access_token失败
        }
        $this->_openid =  $data['openid'];
        $wxSession = $session->get('weixin');
        $new_weixin = array();
        if(is_array($wxSession)){
            $new_weixin = array_merge($wxSession,array('openid'=>$this->_openid));
        }
        $session->set('weixin',$new_weixin);
        if(isset($this->_openid)){
            $userData = WechatUser::where('openid',$this->_openid)->first();
            if(!$userData){
                $userData = $weixin->get_web_user_info($data['access_token'],$data['openid']);
                if(!$userData){
                    return redirect($this->convertUrlQuery($wxSession['userinfo_callback']).'code=40003');//拉取用户信息失败
                }
                //存储微信用户数据
                $wxModel = new WechatUser();
                $wxModel->openid = $userData['openid'];
                $wxModel->sex = $userData['sex'];
                $wxModel->nick_name = $userData['nickname'];
                $wxModel->province = $userData['province'];
                $wxModel->city = $userData['city'];
                $wxModel->country = $userData['country'];
                $wxModel->headimgurl = $userData['headimgurl'];
                $wxModel->save();
            }
        }

        //判断微信用户是否绑定
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>$this->_openid));
        if(isset($res['error'])){
            return redirect($this->convertUrlQuery($wxSession['userinfo_callback']).'code=40004');//接口出错
        }
        if($res['result']['data']){
            $client->accountSignIn(array('channel'=>$this->_weixin,'openId'=>$this->_openid));
            WechatUser::where('openid',$this->_openid)->update(array('uid'=>intval($res['result']['data'])));
            return redirect($wxSession['userinfo_callback']);
        }
        return redirect($this->convertUrlQuery($wxSession['userinfo_callback']).'code=40005');//用户未绑定
    }

    //绑定用户
    public function getWechatBind(){
        $wxObj = new Weixin();
        $session = new Session();
        $session->set('weixin',array('callback'=>env('WECHAT_BASE_HOST')."/wechat/bindWechat"));
        $oauth_url = $wxObj->get_authorize_url();
        return redirect($oauth_url);
    }


    //解除绑定
    public function getWechatUnbind(){
        global $userId;
        if(!$userId){
            return redirect(env('YY_BASE_HOST')."/yunying/open/login?client=fuwuhao&callback=".env('WECHAT_BASE_HOST')."/wechat/unbindWechat");
        }
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>$userId));
        if(isset($res['result'])){
            if($res['result']['data']){
                return redirect(env('WECHAT_BASE_HOST')."/wechat/unbindWechat");
            }else{
                return redirect(env('WECHAT_BASE_HOST')."/wechat/unbindWechat/finish");
            }
        }
        return redirect(env('WECHAT_BASE_HOST')."/wechat/unbindWechat/finish");
    }

    //获取响应事件
    public function getEvent(Request $request)
    {
        $this->valid($request);
    }

    public function postEvent(Request $request)
    {
        $this->responseMsg();
    }

    private function responseMsg()
    {
        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $type = $postObj->MsgType;
            //$keyword = trim($postObj->Content);
            $time = time();
            $textTpl = config('open.weixin.xml_template.textTpl');
            $msgType = "text";
            if($type == 'event'){
                $content = $this->receiveEvent($postObj,$fromUsername);
                if($content['error']){
                    $resultStr = 'success';
                }else{
                    switch ($content['content']){
                        case 'template':
                            $resultStr = 'success';
                            break;
                        default :
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content['content']);
                            break;
                    }
                }
            }else{
                $resultStr = "success";
            }
            echo $resultStr;
        }else{
            echo "";
            exit;
        }
    }

    //微信验证
    private function valid($request)
    {
        $echoStr = $request->echostr;
        if($this->checkSignature($request)){
            echo $echoStr;
        }
    }

    //获取响应Key回复相应内容
    private function receiveEvent($object,$openid)
    {
        $content = array('error'=>1,'content'=>'success');
        switch ($object->Event)
        {
            case "subscribe":
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
                break;
            case "SCAN":
                break;
            case "CLICK":
                if (isset($object->EventKey)){
                    $EventKey = $object->EventKey;
                }
                $content['error'] = 1;
                switch ($EventKey){
                    case 'bind_weixin':
                        $bindHref = env('WECHAT_BASE_HOST').'/yunying/open/wechat-bind';
                        $unbindHref = env('WECHAT_BASE_HOST').'/yunying/open/wechat-unbind';
                        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
                        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>strval($openid)));
                        if(isset($res['result'])){
                            if($res['result']['data']){
                                $userId = intval($res['result']['data']);
                                $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
                                $userBase = $client->userBasicInfo(array('userId'=>$userId));
                                $content['error'] = 0;
                                $content['content'] = "您的微信账号为:{$userBase['result']['data']['username']}，如需解绑当前账号。请点击<a href='$unbindHref'>【立即解绑】</a>";
                            }else{
                                $content['error'] = 0;
                                $content['content'] = "终于等到你，还好我没放弃。绑定网利宝账号，轻松投资，随时随地查看收益!<a href='$bindHref'>【立即绑定】</a>";
                            }
                        }else{
                            $content = array('error'=>1,'content'=>'success');
                        }
                        break;
                    case 'daily_sign':
                        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
                        $res = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>strval($openid)));
                        $url = env('WECHAT_BASE_HOST').'/app/check';
                        if($res['result']['data']){
                            $userId = intval($res['result']['data']);
                            $actRpcObj = new ActivityJsonRpc();
                            $res = $actRpcObj->innerSignin($userId);
                            if(!$res['code']){
                                $is_sign = $res['data']['isSignin'];
                                if($is_sign){
                                    $content['error'] = 0;
                                    $content['content'] = "今日你已签到，连续签到可获得更多奖励，记得明天再来哦！";
                                }else{
                                    $data = array(
                                        'first'=>array(
                                            'value'=>'恭喜您签到成功，连续签到可获得更多奖励，记得明天再来哦！',
                                            'color'=>'#000000'
                                        ),
                                        'keyword1'=>array(
                                            'value'=>$res['data']['award'][0],
                                            'color'=>'#000000'
                                        ),
                                        'keyword2'=>array(
                                            'value'=>date('Y-m-d H:i'),
                                            'color'=>'#000000'
                                        ),
                                        'keyword3'=>array(
                                            'value'=>$res['data']['current'],
                                            'color'=>'#00000'
                                        ),
                                        'remark'=>array(
                                            'value'=>'分享活动拿更多奖励，速戳详情领取！',
                                            'color'=>'#173177'
                                        )
                                    );
                                    $wxObj = new Weixin();
                                    $status = $wxObj->send_template_msg(strval($openid),Config::get('open.weixin.msg_template.sign_daily'),$data,$url);
                                    if($status['errcode'] == 40001){
                                        $status = $wxObj->send_template_msg(strval($openid),Config::get('open.weixin.msg_template.sign_daily'),$data,$url,true);
                                    }
                                    $content['error'] = 0;
                                    $content['content'] = 'template';
                                }
                            }
                        }else{
                            $content['error'] = 0;
                            $bindHref = env('WECHAT_BASE_HOST').'/yunying/open/wechat-bind';
                            $content['content'] = "终于等到你，还好我没放弃。绑定网利宝账号，轻松投资，随时随地查看收益!<a href='$bindHref'>【立即绑定】</a>";
                        }
                        break;

                }
                break;
        }
        return $content;
    }

    //---------------------------------爱有钱----------------------------------//

    public function postAyqRegister(Request $request){
        if(!$request->sign){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        if(!$request->mobile){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $phone = $request->mobile;
        if(!$request->realname){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $realname = $request->realname;
        if(!$request->uid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $uid = $request->uid;
        if(!$request->cardno){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $cardno = $request->cardno;
        if(!$request->service){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $service = $request->service;
        if(!$request->time){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $time = $request->time;
        if(!$request->cid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $cid = $request->cid;
        $channel_id = octdec($cid)-100000;
        $channels = Channel::where('coop_status',0)->where('is_abandoned',0)->where('id',$channel_id)->value('alias_name');
        if(!$channels){
            return response()->json(array('result'=>0,'remark'=>"非法渠道",'data'=>array()));
        }
        if($service !== "register_bind"){
            return response()->json(array('result'=>0,'remark'=>"接口不存在",'data'=>array()));
        }
        $nowtime = time();
        if($nowtime-$time>60){
            return response()->json(array('result'=>0,'remark'=>"请求超时",'data'=>array()));
        }
        $sign = $request->sign;
        $signStr = $this->createSignStr(array('uid'=>$uid,'mobile'=>$phone,'realname'=>$realname,'cardno'=>$cardno,'service'=>$service,'time'=>$time,'cid'=>$cid));
        $createSign = md5($signStr);
        if($sign !== $createSign){
            return response()->json(array('result'=>0,'remark'=>"签名认证失败",'data'=>array()));
        }
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountRegister(array('channel'=>$channels,'phone'=>$phone));
        if(isset($res['error']) && $res['error']['code'] == 1104){
            return response()->json(array('result'=>2,'remark'=>$res['error']['message'],'data'=>array()));
        }
        if(isset($res['error'])){
            file_put_contents(storage_path('logs/register-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'  code:'.$res['error']['code'].'  msg:'.$res['error']['message'].'  phone:'.$phone.PHP_EOL,FILE_APPEND);
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-REGISTER",'data'=>array()));
        }
        $bindres = $client->accountBind(array('channel'=>$channels,'openId'=>$uid,'userId'=>$res['result']['data']['id']));
        if(isset($bindres['error'])){
            file_put_contents(storage_path('logs/bind-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'  code:'.$bindres['error']['code'].'  msg:'.$bindres['error']['message'].'  uid:'.$uid.PHP_EOL,FILE_APPEND);
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-BIND",'data'=>array()));
        }
        /*$signRes = $client->accountSignIn(array('channel'=>$channels,'openId'=>$uid));
        if(isset($signRes['error'])){
            file_put_contents(storage_path('logs/login-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'  code:'.$signRes['error']['code'].'  msg:'.$signRes['error']['message'].'  uid:'.$uid.PHP_EOL,FILE_APPEND);
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-LOGIN",'data'=>array()));
        }*/
        $verifRes = $client->verified(array('name'=>$realname,'id_number'=>$cardno,'userId'=>$res['result']['data']['id']));
        if(isset($verifRes['error']) && in_array($verifRes['error']['code'],array(1106,1112,1206,1209,1210,1405))){
            file_put_contents(storage_path('logs/verifed-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'  code:'.$verifRes['error']['code'].'  msg:'.$verifRes['error']['message'].'  name:'.$realname.PHP_EOL,FILE_APPEND);
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-VERIFED",'data'=>array()));
        }
        return response()->json(array('result'=>1,'remark'=>$res['result']['message'],'data'=>array('bind_uid'=>$res['result']['data']['id'],'is_realname'=>1)));
    }

    //爱有钱个人中心
    public function getAyqLogin(Request $request){
        if(!$request->sign){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        if(!$request->bind_uid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $bind_uid = $request->bind_uid;
        if(!$request->service){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $service = $request->service;
        if(!$request->time){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $time = $request->time;
        if(!$request->cid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $cid = $request->cid;
        $channel_id = octdec($cid)-100000;
        $channels = Channel::where('coop_status',0)->where('is_abandoned',0)->where('id',$channel_id)->value('alias_name');
        if(!$channels){
            return response()->json(array('result'=>0,'remark'=>"非法渠道",'data'=>array()));
        }
        $nowtime = time();
        if($nowtime-$time>60){
            return response()->json(array('result'=>0,'remark'=>"请求超时",'data'=>array()));
        }
        $sign = $request->sign;
        $signStr = $this->createSignStr(array('bind_uid'=>$bind_uid,'service'=>$service,'time'=>$time,'cid'=>$cid));
        $createSign = md5($signStr);
        if($sign !== $createSign){
            return response()->json(array('result'=>0,'remark'=>"签名认证失败",'data'=>array()));
        }
        $client = new JsonRpcClient(env("ACCOUNT_HTTP_URL"));
        $res = $client->accountIsBind(array('channel'=>$channels,'key'=>$bind_uid));
        if(!$res['result']['data']){
            return response()->json(array('result'=>0,'remark'=>"账户未绑定",'data'=>array()));
        }
        if($service === 'member_info'){
            $res = $this->redirectUrl($bind_uid,'profile');
            if(!$res){
                return response()->json(array('result'=>0,'remark'=>"服务器内部错误-USER",'data'=>array()));
            }
            return redirect($res);
        }elseif ($service === 'member_recharge'){
            $res = $this->redirectUrl($bind_uid,'recharge');
            if(!$res){
                return response()->json(array('result'=>0,'remark'=>"服务器内部错误-USER",'data'=>array()));
            }
            return redirect($res);
        }elseif ($service === 'member_withdraw'){
            $res = $this->redirectUrl($bind_uid,'withdraw');
            if(!$res){
                return response()->json(array('result'=>0,'remark'=>"服务器内部错误-USER",'data'=>array()));
            }
            return redirect($res);
        }else{
            return response()->json(array('result'=>0,'remark'=>"接口不存在",'data'=>array()));
        }
    }

    //爱有钱老用户绑定
    public function getAyqBind(Request $request){
        if(!$request->sign){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        if(!$request->uid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $uid = $request->uid;
        if(!$request->service){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $service = $request->service;
        if(!$request->time){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        if(!$request->time){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $time = $request->time;
        if(!$request->cid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $cid = $request->cid;
        $channel_id = octdec($cid)-100000;
        $channels = Channel::where('coop_status',0)->where('is_abandoned',0)->where('id',$channel_id)->value('alias_name');
        if(!$channels){
            return response()->json(array('result'=>0,'remark'=>"非法渠道",'data'=>array()));
        }
        if($service !== "login_bind"){
            return response()->json(array('result'=>0,'remark'=>"接口不存在",'data'=>array()));
        }
        $nowtime = time();
        if($nowtime-$time>60){
            return response()->json(array('result'=>0,'remark'=>"请求超时",'data'=>array()));
        }
        $sign = $request->sign;
        $signStr = $this->createSignStr(array('uid'=>$uid,'service'=>$service,'time'=>$time,'cid'=>$cid));
        $createSign = md5($signStr);
        if($sign !== $createSign){
            return response()->json(array('result'=>0,'remark'=>"签名认证失败",'data'=>array()));
        }
        $time = time();
        $sign = hash('sha256',$time.'3d07dd21b5712a1c221207bf2f46e4ft');
        $client = new JsonRpcClient(env('CNANNEL_HTTP_URL'));
        $res = $client->getSigninUrl(array('uid'=>$uid,'channel'=>'aiyouqian','timestamp'=>$time,'sign'=>$sign));
        if(isset($res['error'])){
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-USER",'data'=>array()));
        }
        return redirect($res['result']['url']);
    }

    //爱有钱用户资产接口
    public function postAyqUserinfo(Request $request){
        if(!$request->sign){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        if(!$request->bind_uid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $bind_uid = $request->bind_uid;
        if(!$request->service){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $service = $request->service;
        if(!$request->time){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $time = $request->time;
        if(!$request->cid){
            return response()->json(array('result'=>0,'remark'=>"参数不正确",'data'=>array()));
        }
        $cid = $request->cid;
        $channel_id = octdec($cid)-100000;
        $channels = Channel::where('coop_status',0)->where('is_abandoned',0)->where('id',$channel_id)->value('alias_name');
        if(!$channels){
            return response()->json(array('result'=>0,'remark'=>"非法渠道",'data'=>array()));
        }
        if($service !== "get_userinfo"){
            return response()->json(array('result'=>0,'remark'=>"接口不存在",'data'=>array()));
        }
        $nowtime = time();
        if($nowtime-$time>60){
            return response()->json(array('result'=>0,'remark'=>"请求超时",'data'=>array()));
        }
        $sign = $request->sign;
        $signStr = $this->createSignStr(array('bind_uid'=>$bind_uid,'service'=>$service,'time'=>$time,'cid'=>$cid));
        $createSign = md5($signStr);
        if($sign !== $createSign){
            return response()->json(array('result'=>0,'remark'=>"签名认证失败",'data'=>array()));
        }
        $time = time();
        $sign = hash('sha256',$time.'3d07dd21b5712a1c221207bf2f46e4ft');
        $client = new JsonRpcClient(env('CNANNEL_HTTP_URL'));
        $res = $client->accountCallback(array('userId'=>$bind_uid,'channel'=>$channels,'timestamp'=>$time,'sign'=>$sign));
        if(isset($res['error'])){
            return response()->json(array('result'=>0,'remark'=>"服务器内部错误-USER",'data'=>array()));
        }
        return response()->json(array('result'=>1,'remark'=>'请求成功','data'=>$res['result']['data']));
    }

    //---------------------------流量充值--------------------------//

    //充值流量接口回调地址
    public function postFlowCallback(Request $request){
        if(!$request->customerOrderId || !$request->phoneNo || !$request->orderId || !$request->scope || !$request->spec || !$request->status){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【参数错误】'.json_encode($request->all()).PHP_EOL,FILE_APPEND);
            exit;
        }
        file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【参数】'.json_encode($request->all()).PHP_EOL,FILE_APPEND);
        $cmd5Str= md5('customerOrderId='.$request->customerOrderId.'&orderId='.$request->orderId.'&phoneNo='.$request->phoneNo.'&scope='.$request->scope.'&spec='.$request->spec.'&status='.$request->status);
        $pub_key = file_get_contents(config_path('key/xy_public_key.pem'));
        $res = openssl_pkey_get_public($pub_key);
        if(!$res){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【私钥不可用】'.$pub_key.PHP_EOL,FILE_APPEND);
            exit;
        }
        $sign = $request->signature;
        $rsaStr = pack("H*",$sign);
        openssl_public_decrypt($rsaStr,$md5Str,$pub_key);
        if($cmd5Str !== $md5Str){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【签名认证失败】csign='.$md5Str.'<=>'.$cmd5Str.'|===|sign='.$sign.PHP_EOL,FILE_APPEND);
            exit;
        }
        $updata = array(
            'order_id'=>$request->orderId,
        );
        switch ($request->status){
            case 'success':
                $updata['status'] =1;
                break;
            case 'fail':
                $updata['status'] =0;
                break;
            default:
                file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【未知状态】'.$request->status.PHP_EOL,FILE_APPEND);
                break;
        }
        $res = FlowRechargeLog::where('corder_id',$request->customerOrderId)->update($updata);
        if(!$res){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【修改订单状态失败】'.json_encode($res).PHP_EOL,FILE_APPEND);
            exit;
        }else{
            $user_id = FlowRechargeLog::where('corder_id',$request->customerOrderId)->value('user_id');
            if($user_id){
                $content = '恭喜您在活动中获取到'.$request->spec.'MB全国通用流量，请拨打运营商客服查询，感谢您对网利宝的支持。';
                $res = SendMessage::Mail($user_id,$content);
                if(!$res)
                file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【站内信发送失败】'.PHP_EOL,FILE_APPEND);
                exit;
            }
            
        }
    }

    //拼接url后边参数
    private function convertUrlQuery($url){
        $check = strpos($url, '?');
        if($check !== false) {
            if(substr($url, $check+1) == '') {
                $new_url = $url;
            } else {
                $new_url = $url.'&';
            }
        } else {
            $new_url = $url.'?';
        }
        return $new_url;
    }

    //爱有钱生成签名字符串
    private function createSignStr($data){
        if(!is_array($data)){
            return '';
        }
        ksort($data);
        $sign_str='';
        foreach($data as $key=>$val){
            if(isset($val) && !is_null($val) && @$val!=''){
                if($key == "realname"){
                    $sign_str.='&'.$key.'='.trim($val);
                }else{
                    $sign_str.='&'.$key.'='.trim($val);
                }
            }
        }
        if ($sign_str!='') {
            $sign_str = substr ( $sign_str, 1 );
        }
        return $sign_str.env('AYQ_MD5_KEY');//"4b701c4aca7dd5ee6ddc78c9e0b741df";
    }

    //获取登录页面地址
    private function redirectUrl($userId,$action){
        $time = time();
        $sign = hash('sha256',$time.'3d07dd21b5712a1c221207bf2f46e4ft');
        $client = new JsonRpcClient(env('CNANNEL_HTTP_URL'));
        $res = $client->getUrl(array('userId'=>$userId,'channel'=>'aiyouqian','timestamp'=>$time,'action'=>$action,'sign'=>$sign));
        if(isset($res['error'])){
            return false;
        }
        return $res['result']['url'];
    }

    //微信验签
    private function checkSignature($request)
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