<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use JsonRPC\Server;
use JsonRPC\Client;
use Lib\JsonRpcClient;



class RpcController extends Controller
{
    public function getClient() {
        //$client = new Client('http://api-omg.wanglibao.com/rpc/server');
        $client = new JsonRpcClient('http://wlpassport.wanglibao.com/wl_passport/app/web/accounts.php?a=login');
        $result = $client->api_login(array('username' => '13811035992', 'password' => 'wangning2012'));
        var_dump($result);
    }
}
