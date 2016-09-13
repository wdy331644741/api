<?php

namespace App\Http\Controllers;

use App\Http\JsonRpcs\OpenJsonRpc;
use App\Models\AppUpdateConfig;
use App\Models\Cms\Opinion;
use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcServer;

use App\Http\JsonRpcs\BannerJsonRpc;
use App\Http\JsonRpcs\ContentJsonRpc;
use App\Http\JsonRpcs\RedeemCodeJsonRpc;
use App\Http\JsonRpcs\ActivityJsonRpc;
use App\Http\JsonRpcs\OpinionJsonRpc;
use App\Http\JsonRpcs\LoanBookJsonRpc;
use App\Http\JsonRpcs\InsideJsonrpc;
use App\Http\JsonRpcs\AppUpdateConfigJsonRpc;

class RpcController extends Controller
{

    /**
     * cms列表
     */
    public function postCms() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new ContentJsonRpc());
        $jsonRpcServer->addService(new BannerJsonRpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');
    }

    /**
     * rpc接口
     */
    public function postIndex() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new ContentJsonRpc());
        $jsonRpcServer->addService(new BannerJsonRpc());
        $jsonRpcServer->addService(new RedeemCodeJsonRpc());
        $jsonRpcServer->addService(new ActivityJsonRpc());
        $jsonRpcServer->addService(new OpinionJsonRpc());
        $jsonRpcServer->addService(new AppUpdateConfigJsonRpc());
        $jsonRpcServer->addService(new OpenJsonRpc());
        $jsonRpcServer->addService(new LoanBookJsonRpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');       
    }
    

    /**
     * api列表
     */
    public function postApi() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new RedeemCodeJsonRpc());
        $jsonRpcServer->addService(new ActivityJsonRpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');
    }

    /**
     * 发送奖品
     */
    public function postInside() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new InsideJsonrpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');
    }

}
