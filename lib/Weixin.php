<?php
namespace Lib;

use  \GuzzleHttp\Client;

class Weixin
{
    private $_appid = "wx169c6925918fe915";
    private $_appsecret = "777cd7abf2a7b63427a660fcec01383f";
    private $_client = null;
    private $_oauth_base_uri = "https://open.weixin.qq.com";
    private $_redirect_uri = "http://ccabcce5.ngrok.io/open/openid";
    private $_state = "snsapi_base";

    function __construct(){
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

    public function get_access_token($code){
        $access_token_url = "/sns/oauth2/access_token?appid=".$this->_appid."&secret=".$this->_appsecret."&code={$code}&grant_type=authorization_code";
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
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
}