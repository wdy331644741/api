<?php
namespace Lib;

use  \GuzzleHttp\Client;

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
     *通过access_token获取用户信息
     * @params：string access_token
     * @params：string open_id
     */

    static public function get_user_info($access_token,$open_id){
        $get_user_url = "/sns/userinfo?access_token={$access_token}&openid={asdasda}&lang=zh_CN";
        $res = $this->_client->get($get_user_url);
        if($res->getStatusCode() == 200){
            return json_decode($res->getBody());
        }
        return false;
    }

    /*
     *通过appid,secret获取access_token
     * @params：string access_token
     * @params：int expires_in
     */

    public function get_access_token(){
        $access_token_url = "/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            return $data;
        }
        return false;
    }


    /*
     *通过openid,template_id发送模板消息
     * @params：string access_token
     * @params：string openid
     * @params：string template_id
     * @params：array data
     */

    public function send_template_msg($access_token,$openid,$template_id,$data){
        $access_token_url = "/cgi-bin/template/del_private_template?access_token={$access_token}";
        $postData = array(
            'touser'=>$openid,
            'template_id'=>$template_id,
            'url'=>'',
            'data'=>$data
        );
        $res = $this->_client->post($access_token_url,json_encode($postData));
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            if($data['errcode'] == 0){
                return true;
            }
            file_put_contents(storage_path('logs/send_template_msg-error'.date('Y-m-d').'.log','code:【'.$data['errcode'].'】-errormsg:【'.$data['errmsg'].'】',FILE_APPEND));
            return false;
        }
        return false;
    }


}