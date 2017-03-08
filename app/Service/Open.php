<?php
namespace App\Service;

use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Open189Log;
use GuzzleHttp\Client;

class Open
{
    private $_appid = "";
    private $_appsecret = "";
    private $_taskId = "";
    private $_client = null;
    private $_clientIp = null;
    private $_version = "v1.0";
    private $_clientType = "10010";

    function __construct() {
        $this->_appid = env('OPEN189_APPID');
        $this->_appsecret = env('OPEN189_APP_SECRET');
        $this->_taskId = env('OPEN189_TASKID');
        $this->_clientIp = env('OPEN189_CLIENTIP');
        $this->_client = new Client([
            'base_uri'=>"https://open.e.189.cn/",
            'timeout'=>9999.0
        ]);
    }

    public function sendNb($data){
        $phone = Func::getUserPhone($data['user_id']);
        $nb = null;
        if((($data['scatter_type'] == 1 && $data['period'] >= 30) || ($data['scatter_type'] == 2))
            && $data['is_first'] == 1 && $data['Investment_amount'] >= 1000) {
            if ($data['Investment_amount'] >= 1000 && $data['Investment_amount'] < 5000) {
                $nb = 330;
            } elseif ($data['Investment_amount'] >= 5000 && $data['Investment_amount'] < 10000) {
                $nb = 800;
            } else {
                $nb = 1300;
            }
            $log = new Open189Log();
            $log->user_id = $data['user_id'];
            $log->project_id = $data['project_id'];
            $log->investment_amount = $data['Investment_amount'];
            $log->phone = $phone;
            $log->is_first = $data['is_first'];
            $log->period = $data['period'];
            $log->buy_time = $data['buy_time'];
            $log->type = $data['type'];
            $log->scatter_type = $data['scatter_type'];
            $log->register_time = $data['register_time'];
            $log->nb = $nb;
            $msectime = msectime();
            $uuid = $msectime . mt_rand(10000, 99999);
            $formData = [
                'clientId' => $this->_appid,
                'timeStamp' => $msectime,
                'clientIp' => $this->_clientIp,
                'version' => $this->_version,
                'clientType' => $this->_clientType,
                'taskId' => $this->_taskId,
                'mobile' => $phone,
                'coin' => $nb,
                'uuid' => $uuid
            ];
            $sign = $this->makeSign($formData);
            $params_str = "clientId=".$this->_appid."&timeStamp=".$msectime."&clientIp=182.18.19.162&version=v1.0&clientType=10010&taskId=100004915&mobile=".$phone."&coin=".$nb."&uuid=".$uuid."&sign=".$sign;
            file_put_contents(storage_path('logs/open189_sign_'.date('Y-m-d').'.log'),date('Y-m-d H:i:s')."=> ".$params_str."===".$sign.PHP_EOL,FILE_APPEND);
            $formData['sign'] = $sign;
            $res = $this->_client->post('/api/oauth2/llb/grantCoin.do', ['form_params' => $formData]);
            $response = json_decode($res->getBody(), true);
            $log->uuid = $uuid;
            $log->status = $response['result'];
            $log->remark = $response['msg'];
            $log->save();
        }else {
            $log = new Open189Log();
            $log->user_id = $data['user_id'];
            $log->project_id = $data['project_id'];
            $log->phone = $phone;
            $log->investment_amount = $data['Investment_amount'];
            $log->is_first = $data['is_first'];
            $log->period = $data['period'];
            $log->buy_time = $data['buy_time'];
            $log->type = $data['type'];
            $log->scatter_type = $data['scatter_type'];
            $log->register_time = $data['register_time'];
            $log->status = 0;
            $log->remark = '投资标期或金额不符合条件或不是首投';
            $log->save();
        }
    }

    //生成签名
    private function makeSign($data){
        ksort($data);
        $str = '';
        foreach ($data as $val){
            $str.=$val;
        }
        return strtoupper(hash_hmac('sha1',$str,$this->_appsecret));
    }
}