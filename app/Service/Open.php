<?php
namespace App\Service;

use App\Models\Open189Log;
use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use Cache;
use Illuminate\Support\Facades\DB;

class Open189
{
    private $_appid = "";
    private $_appsecret = "";
    private $_taskId = "";
    private $_client = null;
    private $_version = "v1.0";
    private $_clientType = "10010";

    function __construct() {
        $this->_appid = env('OPEN189_APPID');
        $this->_appsecret = env('OPEN189_APP_SECRET');
        $this->_taskId = env('OPEN189_TASKID');
        $this->_client = new Client([
            'base_uri'=>"https://open.e.189.cn/",
            'timeout'=>9999.0
        ]);
    }

    public function sendNb($data){
        if(($data['scatter_type'] == 1 && $data['period'] >= 30) || ($data['scatter_type'] == 2)){
            if($data['Investment_amount'] >= 1000 && $data['Investment_amount'] < 5000){
                $nb = 330;
            }elseif ($data['Investment_amount'] >= 5000 && $data['Investment_amount'] < 10000){
                $nb = 800;
            }elseif ($data['Investment_amount'] >= 10000){
                $nb = 1300;
            }else{
                $log = new Open189Log();
                $log->user_id = $data['user_id'];
                $log->project_id = $data['project_id'];
                $log->investment_amount = $data['Investment_amount'];
                $log->is_first = $data['is_first'];
                $log->period = $data['period'];
                $log->buy_time = $data['buy_time'];
                $log->type = $data['type'];
                $log->scatter_type = $data['scatter_type'];
                $log->register_time = $data['register_time'];
                $log->status = 0;
                $log->remark = '投资金额不符合条件';

            }
            $log = new Open189Log();
            $log->user_id = $data['user_id'];
            $log->project_id = $data['project_id'];
            $log->investment_amount = $data['Investment_amount'];
            $log->is_first = $data['is_first'];
            $log->period = $data['period'];
            $log->buy_time = $data['buy_time'];
            $log->type = $data['type'];
            $log->scatter_type = $data['scatter_type'];
            $log->register_time = $data['register_time'];
            $log->nb = $nb;
            $msectime = msectime();
            $phone = Func::getUserPhone($data['user_id']);


        }
    }
}