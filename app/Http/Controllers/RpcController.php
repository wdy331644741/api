<?php

namespace App\Http\Controllers;

use App\Http\JsonRpcs\AdvancedJsonRpc;
use App\Http\JsonRpcs\AmountShareJsonRpc;
use App\Http\JsonRpcs\BbsCommentJsonrpc;
use App\Http\JsonRpcs\BbsThreadJsonRpc;
use App\Http\JsonRpcs\BbsThreadSectionJsonRpc;
use App\Http\JsonRpcs\BbsUserCollectZanJsonrpc;
use App\Http\JsonRpcs\BbsUserJsonRpc;
use App\Http\JsonRpcs\JianmianhuiJsonrpc;
use App\Http\JsonRpcs\OpenJsonRpc;
use App\Http\JsonRpcs\SignInSystemJsonRpc;
use App\Models\AppUpdateConfig;
use App\Models\Cms\Opinion;
use App\Models\PoBaiYi;
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
use App\Http\JsonRpcs\TzyxjJsonRpc;
use App\Http\JsonRpcs\TreasureJsonRpc;
use App\Http\JsonRpcs\PoBaiYiJsonRpc;
use App\Http\JsonRpcs\FiveOneEightJsonRpc;
use App\Http\JsonRpcs\DiyIncreasesJsonRpc;


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
        $jsonRpcServer->addService(new TzyxjJsonRpc());
        $jsonRpcServer->addService(new AdvancedJsonRpc());
        $jsonRpcServer->addService(new TreasureJsonRpc());
        $jsonRpcServer->addService(new PoBaiYiJsonRpc());
        $jsonRpcServer->addService(new AmountShareJsonRpc());
        $jsonRpcServer->addService(new FiveOneEightJsonRpc());
        $jsonRpcServer->addService(new DiyIncreasesJsonRpc());
        $jsonRpcServer->addService(new BbsUserJsonRpc());
        $jsonRpcServer->addService(new BbsThreadJsonRpc());
        $jsonRpcServer->addService(new BbsThreadSectionJsonRpc());
        $jsonRpcServer->addService(new BbsCommentJsonRpc());
        $jsonRpcServer->addService(new JianmianhuiJsonRpc());
        $jsonRpcServer->addService(new SignInSystemJsonRpc());
        $jsonRpcServer->addService(new BbsUserCollectZanJsonrpc());
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
