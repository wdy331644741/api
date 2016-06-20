<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcClient;
use Lib\JsonRpcServer;
use App\Http\JsonRpcs\TestJsonRpc;



class RpcController extends Controller
{
    
    public function getClient() {
        $client = new JsonRpcClient('http://api-omg.wanglibao.com/rpc/test');
        $result = $client->add(array('x' => 1, 'y' => 2));
        var_dump($result);
    }  

    public function postTest() {
        $jsonRpcServer = new JsonRpcServer();         
        $jsonRpcServer->addService(new TestJsonRpc());
        $jsonRpcServer->processingRequests();
    }
}
