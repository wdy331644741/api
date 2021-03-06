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

class RichLotteryJsonRpc extends JsonRpc
{
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function lotterySystemInfo() {
        global $userId;
        $config     = Config::get('richlottery');
        $user       = ['login' => false, 'multiple' => 0];
        $game       = ['available' => true, 'nextSeconds' => 0 , 'endSeconds' => 0 , 'awards' => null];
        $looteryBat = null;

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            $game['available'] = false;
        }

        // 下次活动开始时间
        $item = $this->selectList($config['lists']);
        $game['nextSeconds'] = $item['endTimestamps'] - time();
        // 活动开始一段时间后强制结束
        if(time() - $item['startTimestamps'] > $item['times']) {
            $game['available'] = false;
            
        }else{
            $looteryBat = $item['start'];//当前是哪个时间段的 抽奖
            $game['nextSeconds'] = 0;
            $game['endSeconds'] = $this->whenEndDraw($looteryBat,$item);
            //去掉 敏感信息
            // foreach ($item['awards'] as &$value) {
            //     unset($value['pro'] ,$value['award_type']);
            // }
            // $game['awards'] = $item['awards'];
        }
    
        // 用户是否登录
        if(!empty($userId)) {
            $user['login'] = true;
        }
        //查询用户有几次抽奖机会
        if($user['login'] && !empty($looteryBat)){
            $looteryByuser = $this->getLooteryCounts($looteryBat,$userId);
            $user['multiple'] = substr($looteryByuser,-1) - substr($looteryByuser, 0,1);
        }

        // 获取用户 抽奖次数

        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'activite' => $game,
                'user'     => $user,
            ],
        ];
    }


    /**
     * 分享加次数  每个时间段仅加一次
     *
     * @JsonRpcMethod
     */
    public function shareLotteryAdd(){
        global $userId;
        //是否登录
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //是否在三个时间段内
        $isAvail = false;
        $reason = '不在活动时间段内';
        $looteryBat = null;
        $nowDate = strtotime(date('H:i:s') );
        $config     = Config::get('richlottery.lists');
        foreach ($config as $value) {
            if($nowDate >= strtotime($value['start'].":00") && $nowDate <= strtotime($value['start'].":00")+$value['times']){
                $isAvail = true;
                $looteryBat = $value['start'];
                break;

            }else{
                continue;
            }
        }

        if($isAvail){//
            $effect = $this->shareAddLooteryCounts($looteryBat,$userId);
            $reason = $effect?'增加一次抽奖次数':'超出最大限制';
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'effect' => $isAvail && $effect,
                'reason' => $reason,
            ]
        ];

    }


    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function lotterySystemDraw() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $config = Config::get('richlottery');


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

        $item = $this->selectList($config['lists']);
        if($item['start'] != intval(date('H'))) {
            return [
                'code' => -1,
                'message' => 'failed',
                'data' => '不在活动时间段内',
            ];
        }
        $looteryBat = $item['start'];//当前是哪个时间段的 抽奖
        //查询是否 剩余抽奖次数
        $userLottery = $this->getLooteryCounts($looteryBat,$userId);
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
        //如果是谢谢参与
        if($aliasName == 'thanks'){
            $ret = $this->thanksLottery($userId,$looteryBat);
            //递增 用户属性
            Attributes::incrementByDay($userId, $config['drew_daily_key']);
            Attributes::increment($userId, $config['drew_total_key']);
            DB::commit();
            return $ret;
        }
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
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => $result['awardType'],
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            //修改 用户剩余抽奖次数
            $this->decLotteryCounts($looteryBat,$userId);
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
     * 还有多长时间 结束本次抽奖
     *
     */
    private function whenEndDraw($bat,$conf){
        $nowS = time();
        //本次抽奖开始 时间戳
        $dateStr = date('Y-m-d'); 
        //活动开始了多长时间 
        $runingTime = $nowS - strtotime($dateStr." {$bat}:00:00");
        return $conf['times'] - $runingTime;
    }

    /**
     * 谢谢抽奖
     *
     */
    private function thanksLottery($userId,$bat){
        RichLottery::create([
            'user_id' => $userId,
            'amount' => 0,
            'award_name' => 'thanks',
            'uuid' => '',
            'ip' => Request::getClientIp(),
            'user_agent' => Request::header('User-Agent'),
            'status' => 1,
            'type' => 0,
            'remark' => '谢谢参与',
        ]);
        //修改 用户剩余抽奖次数
        $this->decLotteryCounts($bat,$userId);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'awardName' => '谢谢参与',
                'awardType' => 0,
                'amount' => 0,
                'awardSigni' => 'thanks',
            ],
        ]; 
    }


    /**
     * 抽奖完成 减少用户抽奖次数
     *
     */
    private function decLotteryCounts($bat,$userId){
        $key = Config::get('richlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
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
        $key = Config::get('richlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
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
        $key = Config::get('richlottery.alias_name') . '_' . date('Ymd') . '_'. $bat . '_' . $userId;
        if( !Redis::exists($key) ){
            Redis::setex($key,1*3600+60,'0-1');//初始化 一次抽奖机会
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
        foreach($item['awards'] as $award) {
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
        foreach($item['awards'] as $award) {
            $target = $target - $award['pro'];
            if($target <= 0) {
                $globalKey = Config::get('richlottery.alias_name') . '_' . date('Ymd') . '_'. $item['start'];
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

