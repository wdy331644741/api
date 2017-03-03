<?php

namespace App\Http\Controllers;

use App\Http\JsonRpcs\AdvancedJsonRpc;
use App\Http\JsonRpcs\OpenJsonRpc;
use App\Models\AppUpdateConfig;
use App\Models\Cms\Opinion;
use App\Service\NvshenyueService;
use Illuminate\Http\Request;

use App\Http\Requests;
use Lib\JsonRpcServer;

use App\Http\JsonRpcs\BannerJsonRpc;
use App\Http\JsonRpcs\ContentJsonRpc;
use App\Http\JsonRpcs\RedeemCodeJsonRpc;
use App\Http\JsonRpcs\ActivityJsonRpc;
use App\Http\JsonRpcs\OpinionJsonRpc;
use App\Http\JsonRpcs\LoanBookJsonRpc;
use App\Http\JsonRpcs\CouponCountJsonRpc;
use App\Http\JsonRpcs\InsideJsonrpc;
use App\Http\JsonRpcs\AppUpdateConfigJsonRpc;
use App\Http\JsonRpcs\IdiomJsonrpc;
use App\Http\JsonRpcs\InvestmentJsonrpc;
use App\Http\JsonRpcs\IntegralMallJsonRpc;
use App\Http\JsonRpcs\OneYuanJsonRpc;
use App\Http\JsonRpcs\XjdbJsonRpc;
use App\Http\JsonRpcs\MoneyShareJsonRpc;
use App\Http\JsonRpcs\YaoyiyaoJsonRpc;
use App\Http\JsonRpcs\NvshenyueJsonRpc;

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
        $jsonRpcServer->addService(new IdiomJsonrpc());
        $jsonRpcServer->addService(new CouponCountJsonRpc());
        $jsonRpcServer->addService(new InvestmentJsonrpc());
        $jsonRpcServer->addService(new IntegralMallJsonRpc());
        $jsonRpcServer->addService(new OneYuanJsonRpc());
        $jsonRpcServer->addService(new XjdbJsonRpc());
        $jsonRpcServer->addService(new MoneyShareJsonRpc());
        $jsonRpcServer->addService(new YaoyiyaoJsonRpc());
        $jsonRpcServer->addService(new NvshenyueJsonRpc());
        $jsonRpcServer->addService(new AdvancedJsonRpc());
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
     * 内部接口
     */
    public function postInside() {
        $jsonRpcServer = new JsonRpcServer();
        $jsonRpcServer->addService(new InsideJsonrpc());
        $jsonRpcServer->processingRequests();
        return response('')->header('Content-Type', 'application/json');
    }

}
