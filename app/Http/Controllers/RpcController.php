<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcClient;
use Lib\JsonRpcServer;
use App\Http\JsonRpcs\TestJsonRpc;
use App\Http\JsonRpcs\BannerJsonRpc;
use App\Http\JsonRpcs\ContentJsonRpc;
use App\Http\JsonRpcs\RedeemCodeJsonRpc;

class RpcController extends Controller
{
    
    public function getClient() {
        $client = new JsonRpcClient('http://api-omg.wanglibao.com/rpc/redeem-code');
        $result = $client->sendCodeAward(array('code'=>'5006-6476-8435','userID'=>1716596));
        print_r($result);exit;
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
        return response('')->header('Content-Type', 'application/json');
    }
    /**
     * 兑换码发奖
     */
    public function postRedeemCode() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new RedeemCodeJsonRpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');
    }
}
