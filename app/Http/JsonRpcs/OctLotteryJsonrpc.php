<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\RichLottery;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use App\Service\SendMessage;
use Illuminate\Support\Facades\Redis;
use Config, Request, DB, Cache;

class OctLotteryJsonRpc extends JsonRpc
{

    const iphoneMail = "10月新存管活动抽奖-无iphone x";//注释 nothing
    /**
     * 抽奖info
     *
     * @JsonRpcMethod
     */
    public function octLotteryInfo(){
        global $userId;
        $config = Config::get('octlottery');
        // 用户是否登录
        $islogin  = $userId?true:false;

        $activiStatus = true;
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            $activiStatus = false;
        }

        // 获取用户 抽奖次数 是否可以抽奖
        if($islogin){
            $num = $this->getLooteryCounts($userId);
            $getNum = $this->getGiveCounts($userId);
        }else{
            $num = 0;
            $getNum = 0;
        }

        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'islogin' => $islogin,
                'num'   => $num,
                'getNum'   => intval($getNum),
            ],
        ];
    }


    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function octLotteryDraw(){
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $_userInfo = call_user_func(array("App\Service\Func","getUserBasicInfo"),$userId);
        $config = Config::get('octlottery');


        // 是否触发间隔限制
        if($this->isTooOften($userId, $config)) {
            throw new OmgException(OmgException::API_BUSY);
        }

        $result = [
            'awardName' => '',
            'awardType' => 0,
            'amount' => 0,
            'awardSigni' => '',
        ];
        $remark = [];

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //符合用户会员等级的奖品列表
        $item = $config['lists'][max($_userInfo['level'],0)];
        //查询是否 剩余抽奖次数
        $userLottery = $this->getLooteryCounts($userId);
        if($userLottery  <= 0){
            return [
                'code' => -1,
                'message' => 'failed',
                'data' => '抽奖次数不足',
            ];
        }

        //事务开始
        DB::beginTransaction();
        //forupdate
        Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        // 获取奖品
        $award = $this->getAward($item);

        // 根据别名发活动奖品
        $aliasName = $award['alias_name'];
        
        $awards = SendAward::ActiveSendAward($userId, $aliasName);
        if(isset($awards[0]['award_name']) && $awards[0]['status']) {
            $result['awardName'] = $awards[0]['award_name'];
            $result['awardType'] = $awards[0]['award_type'];
            $result['amount'] = strval(intval($result['awardName']));
            $result['awardSigni'] = $aliasName;//奖品标示 需要返回给前端
            $remark['awards'] = $awards;
            RichLottery::create([
                'user_id' => $userId,
                'amount' => $award['size'],
                'award_name' => $result['awardName'],
                'uuid' => $config['alias_name'],//区分活动
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => $result['awardType'],
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            //修改 用户剩余抽奖次数
            Attributes::decrement($userId,$config['drew_daily_key']);
            //递增 用户属性
            Attributes::increment($userId, $config['drew_total_key']);
            DB::commit();

        }else{
            DB::rollBack();
            throw new OmgException(OmgException::API_FAILED);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];

    }


    /**
     * 获奖列表
     *
     * @JsonRpcMethod
     */
    public function octLotteryBingo(){
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('octlottery');

        $bingoList = RichLottery::select('award_name', 'created_at')->where(['user_id' => $userId, 'uuid' => $config['alias_name'] ])->latest()->get();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $bingoList,
        ];
    }

    /**
     * 获奖列表(全部) 缓存5分钟
     *
     * @JsonRpcMethod
     */
    public function octLotteryList(){
        $key = "octLotteryList";
        $config = Config::get('octlottery');
        return Cache::remember($key,5, function() use($config){
            $list = RichLottery::select('user_id' ,'award_name')->where(['uuid' => $config['alias_name'] ])->latest()->limit(30)->get();
            foreach ($list as $key => & $value) {
                $value['user_id'] = Func::getUserPhone($value['user_id']);
            }
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $list,
            ];
        });
    }

    /**
     * 用户剩余抽奖次数(当天)
     *
     */
    private function getLooteryCounts($userId){
        $config = Config::get('octlottery');
        $userAtt = UserAttribute::where(array('user_id' => $userId, 'key' => $config['drew_daily_key']))->where("updated_at",">=",date("Y-m-d"))->first();
        //如果存在 返回，如果不存在  init用户抽奖次数
        $counts = isset($userAtt['number'])?$userAtt['number']:$this->initLotteryCounts($userId);
        if($counts >3){
            $userAtt->number = 3;
            $userAtt->save();
        }
        return $counts>3?3:$counts;
    }

    /**
     * 获取用户赠送抽奖次数(当天)
     *
     */
    private function getGiveCounts($userId){
        $config = Config::get('octlottery');
        $userAtt = UserAttribute::where(array('user_id' => $userId, 'key' => $config['drew_daily_key']))->where("updated_at",">=",date("Y-m-d"))->first();
        //如果存在 返回，如果不存在  init用户抽奖次数
        $getString = isset($userAtt['string'])?$userAtt['string']:$this->initLotteryCounts($userId);

        return $getString>3?3:$getString;
    }

    /**
     * 初始化用户抽奖次数(当天)
     *
     */
    private function initLotteryCounts($userId){
        $config = Config::get('octlottery');
        return Attributes::incrementItemByDay($userId , $config['drew_daily_key'] ,$initNum=1 ,$initStr=1);
    }

    /**
     * 获取奖品总数
     *
     * @param $item
     * @return int
     */
    private function getTotalNum($item) {
        $number = 0;
        foreach($item as $award) {
            $number += $award['pro'];
        }
        return $number;
    }

    /**
     * 获取奖品
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    private function getAward($item) {
        $number = $this->getTotalNum($item);

        $target = rand(1, $number);
        foreach($item as $award) {
            $target = $target - $award['pro'];
            if($target <= 0) {
                $globalKey = Config::get('octlottery.alias_name') . '_' . date('Ymd');
                Cache::increment($globalKey, 1);
                return $award;
            }
        }

        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }



    /**
     * 抽奖间隔验证
     *
     * @param $userId
     * @param $config
     * @return bool
     */
    private function isTooOften($userId, $config) {
        $key = "rich_lottery_system_{$userId}";
        $value = Cache::pull($key);
        Cache::put($key, time(), 3);
        if($value && time()-$value < $config['interval']) {
            return true;
        }
        return false;
    }



}

