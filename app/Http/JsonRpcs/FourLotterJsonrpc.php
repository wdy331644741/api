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
use Illuminate\Support\Facades\Redis;
use Config, Request, DB, Cache;

class FourLotteryJsonRpc extends JsonRpc
{
    /**
     * 预热场 抽奖活动info
     *
     * @JsonRpcMethod
     */
    public function preFourLotteryInfo(){
        global $userId;
        $config = Config::get('fouryearlottery');
        // 用户是否登录
        $islogin  = $userId?true:false;

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            $game['available'] = false;
        }

        //查询用户积分
        $_userInfo = $islogin?call_user_func(array("App\Service\Func","getUserBasicInfo"),$userId):null;
        // 获取用户 抽奖次数 是否可以抽奖
        if($islogin){
            $isplay = call_user_func(function($str){
                return substr($str,-1) - substr($str, 0,1) > 0?true:false;
            },$this->getLooteryCounts(0,$userId));
        }else{
            $isplay = false;
        }

        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'islogin' => $islogin,
                'score'   => $_userInfo['score'],
                'isplay'  => $isplay,
            ],
        ];
    }


    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function preFourLotteryDraw(){
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $_userInfo = call_user_func(array("App\Service\Func","getUserBasicInfo"),$userId);
        $config = Config::get('fouryearlottery');


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

        // $item = $this->selectList();
        //符合用户会员等级的奖品列表
        $item = $config['lists'][max($_userInfo['level'],0)];
        //查询是否 剩余抽奖次数
        $userLottery = $this->getLooteryCounts(0,$userId);
        if(substr($userLottery,-1) - substr($userLottery, 0,1)  <= 0){
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
            $this->decLotteryCounts(0,$userId);
            //递增 用户属性
            Attributes::incrementByDay($userId, $config['drew_daily_key']);
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
    public function preFourLotteryBingo(){
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('fouryearlottery');

        $bingoList = RichLottery::select('award_name', 'created_at')->where(['user_id' => $userId, 'uuid' => $config['alias_name'] ])->latest()->get();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $bingoList,
        ];
    }


    /**
     * 抽奖完成 减少用户抽奖次数
     *
     */
    private function decLotteryCounts($bat,$userId){
        $key = Config::get('fouryearlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
        //获取用户剩余抽奖信息
        $_remainder = $this->getLooteryCounts($bat,$userId);
        $newSet = substr($_remainder,0,1) + 1;
        Redis::setex($key,1*3600 ,$newSet.'-'.substr($_remainder,-1) );
        return true;
    }

    /**
     * 增加redis 中每个用户抽奖次数
     * 每个用户限抽两次
     *
     */
    private function shareAddLooteryCounts($bat,$userId){
        $key = Config::get('fouryearlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
        //查询用户剩余次数
        $_remainder = $this->getLooteryCounts($bat,$userId);
        if(substr($_remainder, -1) == 2){
            return false;
        }else{
            $newRem = substr($_remainder, 0,-1).'2';
            Redis::setex($key,1*3600 ,$newRem);
            return true;
        }
    }

    /**
     * 获取某个时间段内  用户剩余抽奖次数(info)
     *
     */
    private function getLooteryCounts($bat,$userId){
        $key = Config::get('fouryearlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
        if( !Redis::exists($key) ){
            Redis::setex($key,1*3600+60,'0-2');//初始化 抽奖机会
        }
        //Redis::SET($key,1,Array('nx', 'ex'=>100));
        $value = Redis::GET($key);
        return $value;
        // return substr($value,-1) - substr($value, 0,1);
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
                $globalKey = Config::get('fouryearlottery.alias_name') . '_' . date('Ymd');
                Cache::increment($globalKey, 1);
                return $award;
            }
        }

        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }



    /**
     * 选择 抽奖时间段、奖品
     *
     * @param $lists
     * @return mixed
     * @throws OmgException
     */
    private function selectList($lists) {
        foreach($lists as $item) {
            $startTimestamps = strtotime(date("Y-m-d {$item['start']}:00:00"));
            $endTimestamps = strtotime(date("Y-m-d {$item['end']}:00:00"));
            $now = time();
            if($item['start'] > $item['end']) {
                if($now < $endTimestamps){
                    $startTimestamps -= 3600*24;
                }else {
                    $endTimestamps += 3600*24;
                }
            }

            if($startTimestamps < $now && $now < $endTimestamps) {
                $item['startTimestamps'] = $startTimestamps;
                $item['endTimestamps'] = $endTimestamps;
                return $item;
            }
        }
        throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
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

