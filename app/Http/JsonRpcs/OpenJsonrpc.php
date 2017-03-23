<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Validator;
use Lib\JsonRpcClient;
use Lib\Session;
use Lib\McQueue;
use Lib\Weixin;
use Config;
use App\Models\WechatUser;


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

}