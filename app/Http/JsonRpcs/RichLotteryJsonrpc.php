<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\SignInSystem;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use Illuminate\Support\Facades\Redis;
use Config, Request, Cache;

class RichLotteryJsonRpc extends JsonRpc
{
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function lotterySystemInfo() {
        global $userId;
// $userId = 5101340;
        $config     = Config::get('richlottery');
        $user       = ['login' => false, 'multiple' => 0];
        $game       = ['available' => true, 'nextSeconds' => 0];
        $looteryBat = null;
        //$awardList = $this->getAwardList();

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            
        }

        // 下次活动开始时间
        $item = $this->selectList($config['lists']);
        $game['nextSeconds'] = $item['endTimestamps'] - time() + rand(0,3);
        // 活动开始一段时间后强制结束
        if(time() - $item['startTimestamps'] > $item['times']) {
            $game['available'] = false;
            
        }else{
            $looteryBat = $item['start'];//当前是哪个时间段的 抽奖
            $game['nextSeconds'] = 0;
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
    public function shareLooteryAdd(){
        global $userId;
$userId = 5101340;
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
    public function looterySystemDraw() {
        global $userId;
$userId = 5101340;
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
            'multiple' => 1,
            'multiple_card' => 0,
            'lastGlobalNum' => 0
        ];
        $remark = [];

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $item = $this->selectList($config['lists']);
        if($item['start'] != intval(date('H'))) {
            return '不在抽奖时间段内';
        }

        // 获取奖品
        $award = $this->getAward($item);

        // 根据别名发活动奖品
        $aliasName = $award['alias_name'];
        $awards = SendAward::ActiveSendAward($userId, $aliasName);return $awards;
        if(isset($awards[0]['award_name']) && $awards[0]['status']) {
            $result['awardName'] = $awards[0]['award_name'];
            $result['awardType'] = $awards[0]['award_type'];
            $result['amount'] = strval(intval($result['awardName']));
            $remark['awards'] = $awards;
            SignInSystem::create([
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
        }else{
            throw new OmgException(OmgException::API_FAILED);
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 增加redis 中每个用户抽奖次数
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
     * 获取剩余奖品数量
     * @param $item
     * @return float|int
     */
    private function getLastGlobalNum($item) {
        // 活动开始一段时间后强制结束
        if(time() - $item['startTimestamps'] > $item['times']) {
            return 0;
        }

        $globalKey = Config::get('richlottery.alias_name') . '_' . date('Ymd') . '_'. $item['start'];
        $awardNumberMultiple = Config::get('richlottery.award_number_multiple');
        $usedGlobalNumber = Cache::get($globalKey, 0);
        $globalNumber = floor($this->getTotalNum($item) * $awardNumberMultiple);
        $lastGlobalNumber = $globalNumber - $usedGlobalNumber < 0  ? 0 :$globalNumber - $usedGlobalNumber;
        return $lastGlobalNumber;
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
     * 获取倍率
     *
     * @param $userId
     * @param $config
     * @return int
     */
    private function getMultiple($userId, $config) {
        return Cache::remember("sign_in_system_multiple_{$userId}", 5, function() use ($userId, $config) {
            $inviteNum = Attributes::getNumber($userId, $config['invite_alias_name'], 0);
            foreach ($config['multipleLists'] as $item) {
                if ($inviteNum >= $item['min'] && $inviteNum <= $item['max']) {
                    return $item['multiple'];
                }
            }
            return 1;
        });
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
     * 获取获奖列表
     *
     * @return array
     */
    private function getAwardList() {
        return Cache::remember('sign_in_system_list', 2, function() {
            $data = SignInSystem::select('user_id', 'award_name')->orderBy('id', 'desc')->take(20)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                }
            }
            return $data;
        });
    }

    /**
     * 抽奖间隔验证
     *
     * @param $userId
     * @param $config
     * @return bool
     */
    private function isTooOften($userId, $config) {
        $key = "sign_in_system_interval_{$userId}";
        $value = Cache::pull($key);
        Cache::put($key, time(), 5);
        if($value && time()-$value < $config['interval']) {
            return true;
        }
        return false;
    }

    /**
     * 用户投资标是否超过48小时
     *
     */
    private function isInvested($userId, $config) {
        $expired = Attributes::getNumber($userId, $config['trade_alias_name'], 0);
        if(time() < $expired) {
            return true;
        }
        return false;
    }
}

