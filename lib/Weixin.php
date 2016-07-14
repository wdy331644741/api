<?php
namespace Lib;

use  \GuzzleHttp\Client;

class Weixin
{
    private $_appid = "wx169c6925918fe915";
    private $_appsecret = "777cd7abf2a7b63427a660fcec01383f";
    private $_client = null;
    private $_oauth_base_uri = "https://open.weixin.qq.com";
    private $_redirect_uri = "http://ec79d23c.ngrok.io/open/openid";
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
       /* $res = $this->http($access_token_url,'GET',true);
        if($res[0] == 200){
            return json_decode($res[1]);
        }
        return false;*/
        $res = $this->_client->get($access_token_url);
        if($res->getStatusCode() == 200){
            $data = json_decode($res->getBody());
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
     * http请求
     * @params:string $url
     * @paramsLstring $method
     * @params:array  $postfields
     * @params:array $header
     * @params:bool $debug
     */

    public function http($url,$method="GET",$https=false,$postfields=array(),$header=array(),$debug=false){

        $ch = curl_init();
        //curl Setting
        $option = array(
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 60, //设置cURL允许执行的最长秒数。
            CURLOPT_RETURNTRANSFER => true, //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
            CURLOPT_URL => $url,//设置连接url
            CURLOPT_HTTPHEADER => $header, //设置http头
            CURLINFO_HEADER_OUT => true,
        );
        switch($method){
            case "POST":
                $option[CURLOPT_POST] = true;
                if(!empty($postfields)){
                    $option[CURLOPT_POSTFIELDS] = $postfields;
                }
                break;
        }
        if($https){
            $option[CURLOPT_SSL_VERIFYPEER] = false; // 跳过证书检查
            $option[CURLOPT_SSL_VERIFYHOST] = 2;  // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt_array($ch,$option);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);

            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($ch));

            echo '=====$response=====' . "\r\n";
            print_r($response);
        }
        curl_close($ch);
        return array($http_code, $response);
    }
}