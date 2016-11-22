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
        ignore_user_abort(true);
        $tag = $request->tag;
        if($tag !== 'wechatMSG'){
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'通知错误'));
        }
        $requests = $request->all();
        $type = $requests['mtype'];
        $userId = isset($requests['args']['user_id']) ? intval($requests['args']['user_id']) : 0;
        if(!$type || !$userId ){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        $res = $client->accountOpenId(array('channel'=>'wechat','userId'=>$userId));
        if(isset($res['error'])){
            return array('error_code'=>10000, 'data'=>array('error_msg'=>'获取openid出现错误'));
        }
        $openId = $res['result']['data']['openid'];//'ovewut6VpqDz6ux4nJg2cKx0srh0';
        file_put_contents(storage_path('logs/wsm-data'.date('Y-m-d').'.log'),date('y-m-d H:i:s').'=>'.json_encode($requests).PHP_EOL,FILE_APPEND);
        switch ($type){
            case 'recharge_success' :
                $res = $this->_sendRecharge($openId,$requests);
                return response()->json($res);
                break;
            case 'withdraw_success' :
                $res = $this->_sendWithdraw($openId,$requests);
                return response()->json($res);
                break;
            case 'get_account' :
                $res = $this->_sendGetAccount($openId,$requests);
                return response()->json($res);
                break;
            default :
                return array('error_code'=>10001, 'data'=>array('error_msg'=>'未知类型'));
            break;
        }
    }

    //发送充值成功消息
    private function _sendRecharge($openId,$data){
        if(!$data['args']['username'] || !$data['args']['amount']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['args']['username'].'，您已充值成功。',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['args']['amount'].'元',
                'color'=>'#173177'
            ),
            'keyword2'=>array(
                'value'=>date('Y年m月d日 H时i分'),
                'color'=>'#000000'
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
    private function _sendWithdraw($openId,$data){
        if(!$data['args']['username'] || !$data['args']['amount']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['args']['username'].'，您的提现已成功。',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['args']['amount'].'元',
                'color'=>'#173177'
            ),
            'keyword2'=>array(
                'value'=>date('Y年m月d日 H时i分'),
                'color'=>'#000000'
            ),
            'remark'=>array(
                'value'=>'请注意查收。',
                'color'=>'#000000'
            )
        );
        $template_id = Config::get('open.weixin.msg_template.withdraw_success');
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


    //发送回款成功消息
    private function _sendGetAccount($openId,$data){
        if(!$data['args']['user_name'] || !$data['args']['money'] || !$data['args']['project_name']){
            return array('error_code'=>10001, 'data'=>array('error_msg'=>'参数错误'));
        }
        $postData = array(
            'first'=>array(
                'value'=>'亲爱的'.$data['args']['user_name'].'，您投资的项目收到还款，已到账',
                'color'=>'#000000'
            ),
            'keyword1'=>array(
                'value'=>$data['args']['project_name'],
                'color'=>'#173177'
            ),
            'keyword2'=>array(
                'value'=>$data['args']['money'].'元',
                'color'=>'#173177'
            ),
            'remark'=>array(
                'value'=>'请登录您的网利宝账户进行查看。',
                'color'=>'#000000'
            )
        );
        $template_id = Config::get('open.weixin.msg_template.get_account');
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
