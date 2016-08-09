<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use Validator;
use Lib\JsonRpcClient;
use Lib\Session;
use Lib\McQueue;


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
        file_put_contents(storage_path('logs/test-error.log'),$weixin['openid'].'--'.$userId.'--'.$this->_weixin,FILE_APPEND);
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountBind(array('channel'=>$this->_weixin,'openId'=>$weixin['openid'],'userId'=>$userId));
        if(!isset($res['error'])){
            $mcQueue = new McQueue();
            $data =  ['user_id' => $userId ,'datetime' => date('Y-m-d H:i:s')];
            $putStatus = $mcQueue->put('binding',$data);
            if(!$putStatus)
            {
                $error = $mcQueue->getErr();//  ['err_code' => $mcQueue->errCode ,'err_msg' => $mcQueue->errMsg];
                file_put_contents(storage_path('logs/McQueue-Error-'.date('Y-m-d')).'.log','【userId:'.$userId.'-err_code:'.$error['err_code'].'-err_msg:'.$error['err_msg'].'】-Send Msg Fails-'.date('Y-m-d'),FILE_APPEND);
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
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountUnbind(array('channel'=>$this->_weixin,'userId'=>$userId));
        if(isset($res['error'])){
            return $res['error'];
        }
        return $res['result'];
    }

}