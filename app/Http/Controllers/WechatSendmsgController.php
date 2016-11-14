<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Lib\JsonRpcClient;
use Lib\Weixin;
use Config;

class WechatSendmsgController extends Controller
{
    public function postSendmsg(Request $request){
        $tag = $request->tag;
        if($tag !== 'wechat_msg'){
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'通知错误'));
        }
        $type = $request->type;
        $userId = isset($request->user_id) ? intval($request->user_id) : 0;
        if(!$type || !$userId ){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        /*$client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->getOpenId($userId,'wechat');
        if(isset($res['error'])){
            return '获取openid出现错误';
        }*/
        $openId = 'ovewut6VpqDz6ux4nJg2cKx0srh0';//$res['result']['data']['openid'];
        switch ($type){
            case 'recharge_success' :
                $res = $this->_sendRecharge($openId,$request->all());
                return response()->json($res);
                break;
            case 'present_success' :
                $res = $this->_sendPresent($openId,$request->all());
                return response()->json($res);
                break;
            case 'payment_account' :
                $res = $this->_sendPayment($openId,$request->all());
                return response()->json($res);
                break;
            default :
                return array('error_code'=>10001, 'data'=>array('error_msg'=>'未知类型'));
            break;
        }
    }

    //发送充值成功消息
    private function _sendRecharge($openId,$data){
        if(!$data['real_name'] || !$data['money']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['real_name'].'，您已充值成功。',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['money'].'元',
                'color'=>'#173177'
            ),
            'remark'=>array(
                'value'=>'请登录您的网利宝账户进行查看。',
                'color'=>'#000000'
            )
        );
        $template_id = Config::get('open.weixin.msg_template.recharge_success');
        $weixin = new Weixin();
        $res = $weixin->send_template_msg($openId,$template_id,$postData);
        if($res === false) {
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'发送失败'));
        }
        if($res['errcode'] == 0){
            return array('error_code'=>0, 'data'=>array('error_msg'=>'发送成功'));
        }elseif ($res['errcode'] == 40001){
            return array('error_code'=> 40001, 'data'=>array('error_msg'=>'access_token授权失败'));
        }


    }

    //发送提现成功消息
    private function _sendPresent($openId,$data){
        if(!$data['real_name'] || !$data['money']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['real_name'].'，您的提现已成功。',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['money'].'元',
                'color'=>'#173177'
            ),
            'remark'=>array(
                'value'=>'请注意查收。',
                'color'=>'#000000'
            )
        );
        $template_id = Config::get('open.weixin.msg_template.present_success');
        $weixin = new Weixin();
        $res = $weixin->send_template_msg($openId,$template_id,$postData);
        if($res === false) {
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'发送失败'));
        }
        if($res['errcode'] == 0){
            return array('error_code'=>0, 'data'=>array('error_msg'=>'发送成功'));
        }elseif ($res['errcode'] == 40001){
            return array('error_code'=> 40001, 'data'=>array('error_msg'=>'access_token授权失败'));
        }
    }


    //发送提现成功消息
    private function _sendPayment($openId,$data){
        if(!$data['real_name'] || !$data['money'] || !$data['project_name']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['real_name'].'，您投资的项目收到还款，已到账',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['project_name'],
                'color'=>'#173177'
            ),
            'keyword2'=>array(
                'value'=>$data['money'].'元',
                'color'=>'#173177'
            ),
            'remark'=>array(
                'value'=>'请登录您的网利宝账户进行查看。',
                'color'=>'#000000'
            )
        );
        $template_id = Config::get('open.weixin.msg_template.payment_account');
        $weixin = new Weixin();
        $res = $weixin->send_template_msg($openId,$template_id,$postData);
        if($res === false) {
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'发送失败'));
        }
        if($res['errcode'] == 0){
            return array('error_code'=>0, 'data'=>array('error_msg'=>'发送成功'));
        }elseif ($res['errcode'] == 40001){
            return array('error_code'=> 40001, 'data'=>array('error_msg'=>'access_token授权失败'));
        }
    }
}
