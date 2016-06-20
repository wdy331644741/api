<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcClient;
use Lib\JsonRpcServer;
use App\Http\JsonRpcs\TestJsonRpc;
use App\Http\JsonRpcs\BannerJsonRpc;



class RpcController extends Controller
{
    
    public function getClient() {
        $client = new JsonRpcClient('http://staging.api-omg.wanglibao.com/rpc/banner-list');
        $result = $client->getList(array('position' => '1'));
        print_r($result);
    }  

    public function postTest() {
        $jsonRpcServer = new JsonRpcServer();         
        $jsonRpcServer->addService(new TestJsonRpc());
        $jsonRpcServer->processingRequests();
    }

    /**
     * banner列表
     */
    public function postBannerList() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new BannerJsonRpc());
        $jsonRpcServer->processingRequests();
    }
}
