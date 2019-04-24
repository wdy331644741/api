<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Func;
use Validator;
use Lib\JsonRpcClient;
use Lib\Session;
use Lib\McQueue;
use Lib\Weixin;
use Config;
use  \GuzzleHttp\Client;


class OpenJsonRpc extends JsonRpc {

    private $_weixin = 'wechat';

    /**
     * 微信绑定
     *
     * @JsonRpcMethod
     */
    public function wechatBind() {
        global $userId;
        $session = new Session();
        $weixin = $session->get('weixin');
        file_put_contents(storage_path('logs/test-error.log'),$weixin['openid'].'--'.$userId.'--'.$this->_weixin.PHP_EOL,FILE_APPEND);
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->wechatBind(array('open_id'=>$weixin['openid']));

        if(!isset($res['error'])){
            $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
            $userBase = $client->userBasicInfo(array('userId'=>$userId));
            if(isset($userBase['result'])){
                $data = array(
                    'first'=>array(
                        'value'=>'绑定通知',
                        'color'=>'#173177'
                    ),
                    'name1'=>array(
                        'value'=>'微信',
                        'color'=>'#000000'
                    ),
                    'name2'=>array(
                        'value'=>$userBase['result']['data']['username'],
                        'color'=>'#173177'
                    ),
                    'time'=>array(
                        'value'=>date('Y年m月d日'),
                        'color'=>'#173177'
                    ),
                    'remark'=>array(
                        'value'=>'您可以使用下方的微信菜单进行更多体验。',
                        'color'=>'#000000'
                    )
                );
                $wxObj = new Weixin();
                $status = $wxObj->send_template_msg($weixin['openid'],Config::get('open.weixin.msg_template.wechat_bind'),$data);
                if($status['errcode'] == 40001){
                    $status = $wxObj->send_template_msg($weixin['openid'],Config::get('open.weixin.msg_template.wechat_bind'),$data,null,true);
                }
            }
            return $res['result'];
        }elseif ($res['error']['code'] == 1443){
            throw new OmgException(OmgException::IS_DINED_TO_WECHAT);
        }
        return $res['error'];
    }

    /**
     * 微信小程序绑定
     *
     * @JsonRpcMethod
     */
    public function wechatXcxBind() {
        global $userId;
        $session = new Session();
        $weixin = $session->get('weixin');
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountBind(array('channel'=>'wechat_xcx','openId'=>$weixin['openid'],'userId'=>$userId));
        if(!isset($res['error'])){
            return $res['result'];
        }elseif ($res['error']['code'] == 1443){
            throw new OmgException(OmgException::IS_DINED_TO_WECHAT);
        }
        return $res['error'];
    }
    
    /**
     * 微信解除绑定
     *      
     * @JsonRpcMethod
     */
    public function wechatUnbind() {
        global $userId;
        $session = new Session();
        $weixin = $session->get('weixin');
        /*$userId = 5000032;
        $weixin['openid'] = 'ovewut6VpqDz6ux4nJg2cKx0srh0';*/
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountUnbind(array('channel'=>$this->_weixin,'userId'=>$userId));
        if(isset($res['error'])){
            return $res['error'];
        }
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $userBase = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($userBase['result'])){
            $data = array(
                'first'=>array(
                    'value'=>'尊敬的客户您好，您的账户已经解绑！',
                    'color'=>'#173177'
                ),
                'keyword1'=>array(
                    'value'=>$userBase['result']['data']['username'],
                    'color'=>'#173177'
                ),
                'keyword2'=>array(
                    'value'=>date('Y年m月d日 H:i'),
                    'color'=>'#173177'
                ),
                'remark'=>array(
                    'value'=>'',
                    'color'=>''
                )
            );
            $wxObj = new Weixin();
            $status = $wxObj->send_template_msg($weixin['openid'],Config::get('open.weixin.msg_template.wechat_unbind'),$data);
            if($status['errcode'] == 40001){
                $status = $wxObj->send_template_msg($weixin['openid'],Config::get('open.weixin.msg_template.wechat_bind'),$data,null,true);
            }
            $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
            $res = $client->signout();
        }
        return $res['result'];
    }

    /**
     * 微信PC解除绑定
     *
     * @JsonRpcMethod
     */
    public function wechatPcUnbind() {
        global $userId;
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $data = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>$userId));
        $openid = $data['result']['openid'];
        $res = $client->accountUnbind(array('channel'=>$this->_weixin,'userId'=>$userId));
        if(isset($res['error'])){
            return $res['error'];
        }
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $userBase = $client->userBasicInfo(array('userId'=>$userId));
        if(isset($userBase['result'])){
            $data = array(
                'first'=>array(
                    'value'=>'尊敬的客户您好，您的账户已经解绑！',
                    'color'=>'#173177'
                ),
                'keyword1'=>array(
                    'value'=>$userBase['result']['data']['username'],
                    'color'=>'#173177'
                ),
                'keyword2'=>array(
                    'value'=>date('Y年m月d日 H:i'),
                    'color'=>'#173177'
                ),
                'remark'=>array(
                    'value'=>'',
                    'color'=>''
                )
            );
            $wxObj = new Weixin();
            $status = $wxObj->send_template_msg($openid,Config::get('open.weixin.msg_template.wechat_unbind'),$data);
            if($status['errcode'] == 40001){
                $status = $wxObj->send_template_msg($openid,Config::get('open.weixin.msg_template.wechat_bind'),$data,null,true);
            }
        }
        return $res['result'];
    }

    /**
     * 微信PC是否绑定
     *
     * @JsonRpcMethod
     */
    public function wechatIsBind() {
        global $userId;
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $data = $client->accountIsBind(array('channel'=>$this->_weixin,'key'=>$userId));
        if(isset($data['result'])){
            if (!$data['result']['data']){
                return $data['result'];
            }
        }
        return $data['result'];
    }

    /**
     * 获取微信签名认证
     *
     * @JsonRpcMethod
     */
    public function getSignPackage(){
        //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = $_SERVER['HTTP_REFERER'];
        $wxObj = new Weixin();
        $data = $wxObj->get_sign_package($url);
        return array(
            'code' => 0,
            'message' => 'success',
            'sign_package' =>$data,
        );
    }

    //--------------------- 兑吧 积分商城 JsonRPC-------------------------//

    /**
     * 免登录接口
     *
     * @JsonRpcMethod
     */
    public function getAutoLoginUrl($params){
        global $userId;
        $dbredirect = isset($params->dbredirect) ? $params->dbredirect : null;
        $userInfo = Func::getUserBasicInfo($userId,false);
        if(empty($userId)){
            $userId = "not_login";
            $score = 0;
        }else{
            $score = isset($userInfo['score']) ? $userInfo['score'] : 0;
        }
        $timestamp=msectime();
        $DbCnf = config('open.duiba');
        $array=array("uid"=>$userId,"credits"=>$score,"appSecret"=>$DbCnf['AppSecret'],"appKey"=>$DbCnf['AppKey'],"timestamp"=>$timestamp);
        if($dbredirect != null){
            $array['redirect']=$dbredirect;
        }
        $sign=Func::DbSign($array);
        $array['sign']=$sign;
        $url=$this->duibaAssembleUrl($DbCnf['Base_AutoLogin_Url'],$array);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' =>$url,
        );
    }

    /**
     * 前置商品查询接口
     *
     * @JsonRpcMethod
     */
    public function getProductList($params){
        $pageNum = isset($params->pageNum) ? $params->pageNum : 5;
        $httpClient = new Client([
            'base_uri'=>"https://activity.m.duiba.com.cn",
            'timeout'=>9999.0
        ]);

        $DbCnf = config('open.duiba');
        $timestamp = msectime();
        $arr = ['appKey'=>$DbCnf['AppKey'],'appSecret'=>$DbCnf['AppSecret'],'timestamp'=>$timestamp,'count'=>$pageNum];
        $sign = Func::DbSign($arr);

        $productUrl = "/queryForFrontItem/query?appKey=".$DbCnf['AppKey']."&timestamp=$timestamp&count=$pageNum&sign=$sign";
        $res = $httpClient->request('GET', $productUrl, ['verify' => false]);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            if(isset($data['success']) && $data['success'] == 'true'){
                return array(
                    'code' => 0,
                    'message' => "success",
                    'data' =>$data['data'],
                );
            }
        }
        return array(
            'code' => -1,
            'message' => 'fail',
            'data' =>null,
        );

    }

    /**
     * 前置秒杀商品查询接口
     *
     * @JsonRpcMethod
     */
    public function getSeckillProductList($params){
        $pageNum = isset($params->pageNum) ? $params->pageNum : 5;
        $httpClient = new Client([
            'base_uri'=>"https://activity.m.duiba.com.cn",

            'timeout'=>9999.0
        ]);

        $DbCnf = config('open.duiba');
        $timestamp = msectime();
        $arr = ['appKey'=>$DbCnf['AppKey'],'appSecret'=>$DbCnf['AppSecret'],'timestamp'=>$timestamp,'count'=>$pageNum];
        $sign = Func::DbSign($arr);

        $productUrl = "/gaw/querySeckillItem/querySeckillGoods?appKey=".$DbCnf['AppKey']."&timestamp=$timestamp&count=$pageNum&sign=$sign";
        $res = $httpClient->request('GET', $productUrl, ['verify' => false]);
        if($res->getStatusCode() == 200){
            $data = (array)json_decode($res->getBody());
            if(isset($data['success']) && $data['success'] == 'true'){
                return array(
                    'code' => 0,
                    'message' => $data['desc'],
                    'data' =>$data['data'],
                );
            }
        }
        return array(
            'code' => -1,
            'message' => 'fail',
            'data' =>null,
        );
    }



    //--------------------- 兑吧 积分商城 JsonRPC end -------------------------//
    /*
	*构建参数请求的URL
	*/
    private function duibaAssembleUrl($url, $array)
    {
        unset($array['appSecret']);
        foreach ($array as $key=>$value) {
            $url=$url.$key."=".urlencode($value)."&";
        }
        return $url;
    }

}