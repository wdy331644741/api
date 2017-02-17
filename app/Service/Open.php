<?php
namespace App\Service;

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
                
            }
            $phone = Func::getUserPhone($data['user_id']);


        }
    }
}