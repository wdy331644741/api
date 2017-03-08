<?php
namespace Lib;

use  \GuzzleHttp\Client;
use Cache;

class Weixin
{
    private $_appid = "";
    private $_appsecret = "";
    private $_client = null;
    private $_oauth_base_uri = "https://open.weixin.qq.com";
    private $_redirect_uri = "";
    private $_state = "wanglibao";

    function __construct(){
        $this->_appid = env('WECHAT_APPID');
        $this->_appsecret = env('WECHAT_APP_SECRET');
        $this->_redirect_uri = env('WECHAT_REDIRECT_URI');
        $this->_client = new Client([
            'base_uri'=>"https://api.weixin.qq.com",
            'timeout'=>9999.0
        ]);
    }

    /*
     * 获取微信授权链接
     * @params: string $callback_url 回调地址
     * @params：string $state 重定向后会带上state参数
     * @params: string $scope snsapi_base(默认)和snsapi_userinfo（需要手动授权）
     */
    public function get_authorize_url($scope='snsapi_base'){
        $callback_url = urlencode($this->_redirect_uri);
        return $this->_oauth_base_uri."/connect/oauth2/authorize?appid=".$this->_appid."&redirect_uri={$callback_url}&response_type=code&scope={$scope}&state=".$this->_state."#wechat_redirect";

    }

    /*
     * 通过code换取网页授权access_token
     * @params: string $app_id 公众号的唯一标识
     * @params: string $secret 公众号的appsecret
     * @params：string $code  用户同意授权后得到的code
     */

    public function get_openid($code){
        $access_token_url = "/sns/oauth2/access_token?appid=".$this->_appid."&secret=".$this->_appsecret."&code={$code}&grant_type=authorization_code";
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            if(isset($data['openid']))
            return $data['openid'];
        }
        return false;
    }

    /*
<<<<<<< HEAD
     * 通过code换取网页授权access_token
     * @params: string $app_id 公众号的唯一标识
     * @params: string $secret 公众号的appsecret
     * @params：string $code  用户同意授权后得到的code
     */

    public function get_web_access_token($code=""){
        $access_token_url = "/sns/oauth2/access_token?appid=".$this->_appid."&secret=".$this->_appsecret."&code={$code}&grant_type=authorization_code";
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200) {
            $data = (array)json_decode($res->getBody());
            if (isset($data['openid'])) {
                return $data;
            }
        }
        file_put_contents(storage_path('logs/wechat_access_token_error_'.date('Y-m-d').'.log'),date('Y-m-d H:i:s')."=>[web]code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
        return false;

    }

    /*
     * 通过access_token换取用户信息
     * @params: string $access_token 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * @params: string $openid 	用户的唯一标识
     * @params：string $lang  返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     */

    public function get_web_user_info($access_token,$openid){
        $access_token_url = "/sns/userinfo?access_token=".urlencode($access_token)."&openid=".urlencode($openid)."&lang=zh_CN";
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            if(isset($data['openid']))
                return $data;
        }
        file_put_contents(storage_path('logs/wechat_userinfo_error_'.date('Y-m-d').'.log'),date('Y-m-d H:i:s')."=> [web]code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
        return false;
    }

    /*
=======
>>>>>>> feature-integral
     *通过access_token获取用户信息
     * @params：string access_token
     * @params：string open_id
     */

    public function get_user_info($open_id){
        $access_token = $this->get_access_token();
        $get_user_url = "/cgi-bin/user/info?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
        $res = $this->_client->get($get_user_url);
        if($res->getStatusCode() == 200){
            $data =  (array)json_decode($res->getBody());
            if(!isset($data['errcode'])){
                return $data;
            }
            if($data['errcode'] == 40001){
                $access_token = $this->get_access_token(true);
                $get_user_url = "/cgi-bin/user/info?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
                $res = $this->_client->get($get_user_url);
                if($res->getStatusCode() == 200){
                    $data =  (array)json_decode($res->getBody());
                    if(!isset($data['errcode'])){
                        return $data;
                    }
                }
            }
            file_put_contents(storage_path('logs/wechat_userinfo_error_'.date('Y-m-d').'log'),date('Y-m-d H:i:s')."=> code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
        }
        return false;
    }

    /*
     *通过appid,secret获取access_token
     * @params：string access_token
     * @params：int expires_in
     */

    public function get_access_token($refresh = false){
        if (!Cache::has('wechat_access_token') || $refresh) {
            $access_token_url = "/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";
            $res = $this->_client->get($access_token_url);
            if($res->getStatusCode() == 200){
                $data = (array)json_decode($res->getBody());
                if (isset($data['access_token'])){
                    Cache::put('wechat_access_token',$data['access_token'],120);
                    return $data['access_token'];
                }else{
<<<<<<< HEAD
                    if($data['errcode'] == 40001){
                        Cache::forget('wechat_access_token');
                    }
                    file_put_contents(storage_path('logs/wechat_access_token_error_'.date('Y-m-d').'.log'),date('Y-m-d H:i:s')."=> code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
=======
                    file_put_contents(storage_path('logs/wechat_access_token_error_'.date('Y-m-d').'log'),date('Y-m-d H:i:s')."=> code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
>>>>>>> feature-integral
                    return false;
                }
            }

        }else{
            return Cache::get('wechat_access_token');
        }

    }


    /*
     *通过openid,template_id发送模板消息
     * @params：string access_token
     * @params：string openid
     * @params：string template_id
     * @params：array data
     */

    public function send_template_msg($openid,$template_id,$data,$url=null,$refresh=false){
        $access_token_url = '';
        $access_token = $this->get_access_token($refresh);
        if($access_token){
            $access_token_url = "/cgi-bin/message/template/send?access_token={$access_token}";
        }
        //$access_token_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $postData = array(
            'touser'=>$openid,
            'template_id'=>$template_id,
            'data'=>$data
        );
        if($url !== null){
            $postData['url'] = $url;
        }

        $res = $this->_client->post($access_token_url,['json'=>$postData]);
        if($res->getStatusCode() == 200){
            $result = json_decode($res->getBody());
            if($result->errcode == 0){
                return ['errcode'=>0];
            }elseif ($result->errcode == 40001){
                file_put_contents(storage_path('logs/send_template_msg-error'.date('Y-m-d').'.log'),date('y-m-d H:i:s').'=> code:【'.$result->errcode.'】-errormsg:【'.$result->errmsg.'】'.PHP_EOL,FILE_APPEND);
                return ['errcode'=>40001 ];
            }else{
                file_put_contents(storage_path('logs/send_template_msg-error'.date('Y-m-d').'.log'),date('y-m-d H:i:s').'=> code:【'.$result->errcode.'】-errormsg:【'.$result->errmsg.'】'.PHP_EOL,FILE_APPEND);
                return ['errcode'=>1 ];
            }
        }
        return false;
    }

    /*
     *通过当前url获取签名数组
     * @params：string url
     */
    public function get_sign_package($url){
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $jsapiTicket = $this->get_js_apiticket();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => env('WECHAT_APPID'),
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
        );
        return $signPackage;
    }


    /*
     *获取js_api_ticket
     */
    private function get_js_apiticket(){
        if(!Cache::has('wechat_js_apiticket')){
            $accessToken = $this->get_access_token();
            $url = "/cgi-bin/ticket/getticket?type=jsapi&access_token=".$accessToken;
            $res = $this->_client->get($url);
            if($res->getStatusCode() == 200){
                $data = (array)json_decode($res->getBody());
                if (isset($data['ticket'])){
                    Cache::put('wechat_js_apiticket',$data['ticket'],118);
                    return $data['ticket'];
                }else{
                    file_put_contents(storage_path('logs/wechat_js_apiticket_error_'.date('Y-m-d').'log'),date('Y-m-d H:i:s')."=> code:【".$data['errcode'].'】-errmsg：【'.$data['errmsg'].'】'.PHP_EOL,FILE_APPEND);
                    return false;
                }
            }
        } else {
            return Cache::get('wechat_js_apiticket');
        }

    }

    /*
     *生成随机字符串
     */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /*
     *CURL发送请求
     */
    private function wx_request($url,$data=null){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        if($data != null){
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $info = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno:'.curl_getinfo($curl);//捕抓异常
            dump(curl_getinfo($curl));
        }
        return $info;
    }

}