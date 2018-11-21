<?php

namespace App\Http\Controllers;

use App\Http\JsonRpcs\AdvancedJsonRpc;
use App\Http\JsonRpcs\AmountShareJsonRpc;
use App\Http\JsonRpcs\BbsCommentJsonrpc;
use App\Http\JsonRpcs\BbsThreadJsonRpc;
use App\Http\JsonRpcs\BbsThreadSectionJsonRpc;
use App\Http\JsonRpcs\BbsUserJsonRpc;
use App\Http\JsonRpcs\CollectCardJsonrpc;
use App\Http\JsonRpcs\DaZhuanPanJsonRpc;
use App\Http\JsonRpcs\DoubleElevenJsonrpc;
use App\Http\JsonRpcs\DoubleTwelveJsonrpc;
use App\Http\JsonRpcs\EndYearInvestJsonrpc;
use App\Http\JsonRpcs\HockeyJsonRpc;
use App\Http\JsonRpcs\JianmianhuiJsonrpc;
use App\Http\JsonRpcs\JumpJsonRpc;
use App\Http\JsonRpcs\NetworkDramaDzpJsonRpc;
use App\Http\JsonRpcs\OpenJsonRpc;
use App\Http\JsonRpcs\PerBaiJsonrpc;
use App\Http\JsonRpcs\QuestionJsonrpc;
use App\Http\JsonRpcs\RedEnvelopesJsonRpc;
use App\Http\JsonRpcs\RobRateCouponJsonRpc;
use App\Http\JsonRpcs\ScratchJsonRpc;
use App\Http\JsonRpcs\SignInSystemJsonRpc;
use App\Models\AppUpdateConfig;
use App\Models\Cms\Opinion;
use App\Models\PoBaiYi;
use App\Service\DoubleElevenService;
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
use App\Http\JsonRpcs\BbsUserCollectZanJsonrpc;
use App\Http\JsonRpcs\GanenJsonrpc;
use App\Http\JsonRpcs\YearEndJsonRpc;
use App\Http\JsonRpcs\ChannelJsonrpc;
use App\Http\JsonRpcs\CarnivalJsonRpc;
use App\Http\JsonRpcs\QuickVoteJsonRpc;
use App\Http\JsonRpcs\WorldCupJsonrpc;
use App\Http\JsonRpcs\RichLotteryJsonRpc;
use App\Http\JsonRpcs\FourLotteryJsonRpc;
use App\Http\JsonRpcs\FourYearZhengshiJsonrpc;
use App\Http\JsonRpcs\OctLotteryJsonRpc;
use App\Http\JsonRpcs\CatchDollJsonRpc;
use App\Http\JsonRpcs\OpenGiftJsonRpc;

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
        $jsonRpcServer->addService(new DaZhuanPanJsonRpc());
        $jsonRpcServer->addService(new ScratchJsonRpc());
        $jsonRpcServer->addService(new BbsUserCollectZanJsonrpc());
        $jsonRpcServer->addService(new NetworkDramaDzpJsonRpc());
        $jsonRpcServer->addService(new EndYearInvestJsonrpc());
        $jsonRpcServer->addService(new GanenJsonrpc());
        $jsonRpcServer->addService(new RobRateCouponJsonRpc());
        $jsonRpcServer->addService(new YearEndJsonRpc());//年终 直接送2%加息 活动结束后 可以直接删除。
        $jsonRpcServer->addService(new ChannelJsonrpc());
        $jsonRpcServer->addService(new CarnivalJsonRpc());//嘉年华战队 活动
        $jsonRpcServer->addService(new QuickVoteJsonRpc());//加急投票
        $jsonRpcServer->addService(new CollectCardJsonrpc());
        $jsonRpcServer->addService(new WorldCupJsonrpc());//世界杯活动
        $jsonRpcServer->addService(new QuestionJsonrpc());//我的客服
        $jsonRpcServer->addService(new PerBaiJsonrpc());//逢百抽大奖
        $jsonRpcServer->addService(new RichLotteryJsonRpc());//8月发财 抽奖
//        $jsonRpcServer->addService(new FourLotteryJsonRpc());//4周年 抽奖
        $jsonRpcServer->addService(new FourYearZhengshiJsonrpc());//四周年活动
        $jsonRpcServer->addService(new JumpJsonRpc());//跳一跳
        $jsonRpcServer->addService(new OctLotteryJsonRpc());//10月份抽奖
        $jsonRpcServer->addService(new RedEnvelopesJsonRpc());//领取红包活动
        $jsonRpcServer->addService(new HockeyJsonRpc());//曲棍球正式场
        $jsonRpcServer->addService(new DoubleElevenJsonrpc());//双11 -- 集卡
        $jsonRpcServer->addService(new CatchDollJsonRpc());//抓娃娃机
        $jsonRpcServer->addService(new OpenGiftJsonRpc());//抓娃娃机
        $jsonRpcServer->addService(new DoubleTwelveJsonrpc());

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
