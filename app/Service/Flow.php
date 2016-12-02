<?php
namespace App\Service;
use Config;
use Lib\JsonRpcClient;
use \GuzzleHttp\Client;
use Faker\Provider\Uuid;
use App\Models\FlowRechargeLog;

class Flow
{
    //公共购买方法
    public static function buyFlow($data){
        $appId = env('XY_APPID');
        $api_url = env('XY_API_URL');
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $userBase = $client->userBasicInfo(array('userId'=>$data['user_id']));
        if(isset($userBase['error'])){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【用户接口出错】'.json_encode($userBase).PHP_EOL,FILE_APPEND);
            return array('send'=>false,'errmsg'=>$userBase['error']['message']);
        }
        $phone = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : '';
        if(!$phone){
            return array('send'=>false,'errmsg'=>'未获取用户手机号');
        }
        $num = FlowRechargeLog::where('phone',$phone)->where('status',1)->count();
        if($num >= 2){
            file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【用户充值次数过多】'.$num.PHP_EOL,FILE_APPEND);
            return array('send'=>false,'errmsg'=>'当前用户充值次数过多，出现异常');
        }
        $callbackUrl = env('YY_BASE_HOST').'/yunying/open/flow-callback';
        $timeStamp = self::msectime();
        $orderSn = 'WLB'.date('sdimHY').Uuid::numberBetween(1000000000).Uuid::numberBetween(10000,99999);
        $private_key = file_get_contents(config_path('key/rsa_private_key.pem'));
        $sign = self::createSign(array('appId'=>77,'customerOrderId'=>$orderSn,'phoneNo'=>$phone,'spec'=>$data['spec'],'scope'=>'nation','callbackUrl'=>$callbackUrl,'timeStamp'=>$timeStamp),$private_key,'RSA');
        $signStr = strtolower(bin2hex($sign));
        $_client = new Client([
            'base_uri'=>$api_url,
            'timeout'=>9999.0
        ]);
        //echo ('/buyQuota?appId='.$appId.'&customerOrderId='.$orderSn.'&phoneNo='.$phone.'&spec='.$data['spec'].'&scope=nation&callbackUrl='.urlencode($callbackUrl).'&timeStamp='.$timeStamp.'&signature='.$signStr);exit;
        $res = $_client->get('/buyQuota?appId='.$appId.'&customerOrderId='.$orderSn.'&phoneNo='.$phone.'&spec='.$data['spec'].'&scope=nation&callbackUrl='.urlencode($callbackUrl).'&timeStamp='.$timeStamp.'&signature='.$signStr);
        if($res->getStatusCode() == 200){
            $response = (array)json_decode($res->getBody());
            if($response['code'] === 0){
                $flow = new FlowRechargeLog();
                $flow->user_id = $data['user_id'];
                $flow->corder_id = $orderSn;
                $flow->phone = $phone;
                $flow->spec = $data['spec'];
                $flow->scope = 'nation';
                $flowObj = $flow->save();
                if(!$flow->id){
                    file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【订单入库失败】'.PHP_EOL,FILE_APPEND);
                }
                return array('send'=>true,'errmsg'=>'充值成功');
            }
        }
        file_put_contents(storage_path('logs/flow-error-'.date('Y-m-d')).'.log',date('Y-m-d H:i:s').'=>【通信出错】'.$res->getBody().PHP_EOL,FILE_APPEND);
        return array('send'=>false,'errmsg'=>'通讯错误');
    }

    //生成签名
    private static function createSign($data,$key,$type,$str=''){
        if($str == ''){
            if(!is_array($data)){
                return '';
            }
            ksort($data);
            $sign_str='';
            foreach($data as $k=>$val){
                if(isset($val) && !is_null($val) && @$val!=''){
                    $sign_str.='&'.$k.'='.trim($val);
                }
            }
            if ($sign_str!='') {
                $sign_str = substr ( $sign_str, 1 );
            }
            switch($type){
                case 'MD5':
                    return md5($sign_str.$key);
                break;
                case 'RSA':
                    openssl_private_encrypt(md5($sign_str),$csign,$key);
                    return $csign;
                break;
            }
        }else{
            switch($type){
                case 'MD5':
                    return md5($str.$key);
                    break;
                case 'RSA':
                    openssl_private_encrypt($str,$csign,$key);
                    return $csign;
                    break;
            }
        }
    }


    //毫秒时间戳(格式化)
    private static function msectime() {
        list($tmp1, $tmp2) = explode(' ', microtime());
        return date('YmdHis',$tmp2).ceil($tmp1*1000);
    }
}