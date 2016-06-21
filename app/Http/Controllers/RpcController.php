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
        $client = new JsonRpcClient('http://api-omg.wanglibao.com/rpc/content-list');
        $result = $client->getList(array('type_id' => '1'));
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


    /**
     * 公告列表
     */
    public function postContentList() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new ContentJsonRpc());
        $jsonRpcServer->processingRequests();
    }
}
