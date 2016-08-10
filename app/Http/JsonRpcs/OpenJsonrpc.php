<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Validator;
use Lib\JsonRpcClient;
use Lib\Session;
use Lib\McQueue;
use Lib\Weixin;
use Config;


class OpenJsonRpc extends JsonRpc {

    private $_weixin = 'wechat';
    
    /**
     * 微信绑定
     *
     * @JsonRpcMethod
     */
    public function wechatBind() {
        global $userId;
        $weixin = Session::get('weixin');
        /*$userId = 5000032;
        $weixin['openid'] = 'ovewut6VpqDz6ux4nJg2cKx0srh0';*/
        file_put_contents(storage_path('logs/test-error.log'),$weixin['openid'].'--'.$userId.'--'.$this->_weixin.PHP_EOL,FILE_APPEND);
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountBind(array('channel'=>$this->_weixin,'openId'=>$weixin['openid'],'userId'=>$userId));

        if(!isset($res['error'])){
            $mcQueue = new McQueue();
            $data =  ['user_id' => $userId ,'datetime' => date('Y-m-d H:i:s')];
            $putStatus = $mcQueue->put('binding',$data);
            if(!$putStatus)
            {
                $error = $mcQueue->getErr();//  ['err_code' => $mcQueue->errCode ,'err_msg' => $mcQueue->errMsg];
                file_put_contents(storage_path('logs/McQueue-Error-'.date('Y-m-d')).'.log','【userId:'.$userId.'-err_code:'.$error['err_code'].'-err_msg:'.$error['err_msg'].'】-Send Msg Fails-'.date('Y-m-d').PHP_EOL,FILE_APPEND);
            }
            $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
            $userBase = $client->userBasicInfo(array('userId'=>$userId));
            if(isset($userBase['result'])){
                $data = array(
                    'first'=>array(
                        'value'=>'绑定通知',
                        'color'=>'#173177'
                    ),
                    'keyword1'=>array(
                        'value'=>$userBase['result']['data']['username'],
                        'color'=>'#173177'
                    ),
                    'keyword2'=>array(
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
            }
            return $res['result'];
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
        $weixin = Session::get('weixin');
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
        }
        return $res['result'];
    }

}