<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcClient;
use Lib\JsonRpcServer;
use App\Http\JsonRpcs\TestJsonRpc;
use App\Http\JsonRpcs\BannerJsonRpc;
use App\Http\JsonRpcs\ContentJsonRpc;


class RpcController extends Controller
{
    
    public function getClient() {
        $client = new JsonRpcClient('http://api-omg.wanglibao.com/rpc/cms');
        $result = $client->noticeList();
        echo json_encode($result);
    }  

    public function postTest() {
        $jsonRpcServer = new JsonRpcServer();         
        $jsonRpcServer->addService(new TestJsonRpc());
        $jsonRpcServer->processingRequests();
    }

    /**
     * banner列表
     */
    public function postCms() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new ContentJsonRpc());
        $jsonRpcServer->addService(new BannerJsonRpc());
        $jsonRpcServer->processingRequests();
    }
    
}
