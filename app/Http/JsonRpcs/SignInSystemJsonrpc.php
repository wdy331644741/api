<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\SignInSystem;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SendMessage;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use Config, Request, Cache;
use Illuminate\Support\Facades\Redis;

class SignInSystemJsonRpc extends JsonRpc
{
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function signInSystemInfo() {
        global $userId;
        $userId = 1716707;
        $config = Config::get('signinsystem');
        $user = ['invested' => false, 'login' => false, 'multiple' => 1, 'multiple_card' => 0,'user_end_time' => 0];
        $game = ['available' => false, 'awardNum' => 0, 'nextSeconds' => 0];
        $awardList = $this->getAwardList();

        // 用户是否登录
        if(!empty($userId)) {
            $user['login'] = true;
        }

        // 用户是否投资过
        if($user['login']) {
            $user['invested'] = $this->isInvested($userId, $config);
        }

        // 获取用户倍数
        if($user['login']) {
            $user['multiple'] = $this->getMultiple($userId, $config);
            //获取加倍卡
            $multipleCard = $this->signInEveryDayMultipleCache($userId);
            if($multipleCard > 0){
                $user['multiple_card'] = $multipleCard;
            }
            //获取用户摇一摇结束时间
            $userAtt = UserAttribute::where(['user_id'=> $userId,'key' => $config['trade_alias_name']])->first();
            $times = isset($userAtt->number) ? $userAtt->number - time() : 0;
            $user['user_end_time'] = $times <= 0 ? 0 : $times;
        }


        // 活动是否存在
        if($this->activityIsEnd($config['alias_name'])) {
            $game['available'] = true;
        }


        // 下次活动开始时间
        if($game['available']) {
            $item = $this->selectList($config['lists']);
            $game['awardNum'] = $this->getLastGlobalNum($item);
            $game['nextSeconds'] = $item['endTimestamps'] - time() + rand(0,5);
        }


        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'game' => $game,
                'user' => $user,
                'awardList' => $awardList
            ],
        ];
    }

    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function signInSystemDraw() {
        global $userId;
        $userId = 1716707;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $config = Config::get('signinsystem');

        if(!$this->isInvested($userId, $config)) {
            throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
        }

        // 是否触发间隔限制
//        if($this->isTooOften($userId, $config)) {
//            throw new OmgException(OmgException::API_BUSY);
//        }

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
        if(!$this->activityIsEnd($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $item = $this->selectList($config['lists']);

        // 奖品是否还有
        $lastGlobalNum = $this->getLastGlobalNum($item);
        if(!$lastGlobalNum) {
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

        $result['lastGlobalNum'] = $lastGlobalNum - 1;
        // 获取奖品
        $award = $this->getAward($item);

        //获取倍数
        $result['multiple'] = $this->getMultiple($userId, $config);
        //获取加倍卡
        $multipleCard = $this->signInEveryDayMultipleCache($userId);
        if($multipleCard > 0){
            $result['multiple_card'] = $multipleCard;
        }

        // 创建记录
        $result['awardName'] = $award['is_rmb'] ? $award['size'] . '元' : "100元摇一摇红包";
        $result['amount'] = $award['is_rmb'] ? strval($award['size']) : 100;
        $result['awardType'] = $award['is_rmb'] ? 7 : 2;
        $uuid = SendAward::create_guid();
        $res = SignInSystem::create([
            'user_id' => $userId,
            'award_name' => $result['awardName'],
            'uuid' => $uuid,
            'ip' => Request::getClientIp(),
            'amount' => $award['size'],
            'multiple' => $result['multiple'],
            'multiple_card' => $multipleCard,
            'user_agent' => Request::header('User-Agent'),
            'status' => 1,//默认是成功
            'type' => $result['awardType']
        ]);
        $amount = bcmul($award['size'], $result['multiple'] + $multipleCard, 2);
        //放到redis
        if($award['is_rmb']){
            $redisData = [
                'send_type'=> 7,//发送类型7是现金2是红包
                'user_id'=> $userId,//用户id
                'record_id'=> $res->id,//记录id
                'uuid'=> $uuid,//唯一ID
                'amount'=> $amount,//现金金额金额
                'type'=> "shake",//现金类型（摇一摇现金奖励）
                'sign' => hash('sha256', $userId.env('INSIDE_SECRET')),
            ];
        }else{
            $redisData = [
                'send_type'=>2,//发送类型7是现金2是红包
                'user_id' => $userId,
                'uuid' => $uuid,
                'source_id' => 0,
                'project_ids' =>'',
                'project_type' => 0,
                'project_duration_type' => 3,
                'project_duration_time' => 6,
                'name' => "100元摇一摇红包",
                'type' => 1,
                'amount' => 100,
                'effective_start' => date("Y-m-d H:i:s"),
                'effective_end' => date("Y-m-d H:i:s", strtotime("+7 days")),
                'investment_threshold' => 10000,
                'source_name' => "签到摇一摇",
                'platform' => 0,
                'limit_desc' => "10000元起投，限6月及以上标",
                'remark' => '',
            ];
            $message = ["sourcename"=>"签到摇一摇","awardname"=>"100元摇一摇红包"];
            SendMessage::Mail($userId,"恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",$message);
        }
        //放到队列
        Redis::LPUSH("shakeSendRewardList",json_encode($redisData));
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 获取剩余奖品数量
     * @param $item
     * @return float|int
     */
    private function getLastGlobalNum($item) {
        // 活动开始一段时间后强制结束
//        if(time() - $item['startTimestamps'] > $item['times']) {
//            return 0;
//        }

        $globalKey = Config::get('signinsystem.alias_name') . '_' . date('Ymd') . '_'. $item['start'];
        $awardNumberMultiple = Config::get('signinsystem.award_number_multiple');
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
            $number += $award['num'];
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
            $target = $target - $award['num'];
            if($target <= 0) {
                $globalKey = Config::get('signinsystem.alias_name') . '_' . date('Ymd') . '_'. $item['start'];
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
     * 选择奖品
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
        Cache::put($key, time(), 10);
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

    /**
     * 活动是否存在加缓存
     */
    private function activityIsEnd($alias_name){
        $cacheKey = "activity_is_end".$alias_name;
        if(!Cache::has($cacheKey)){
            $status = ActivityService::isExistByAlias($alias_name);
            if($status){
                Cache::put($cacheKey,$status,5);
            }
        }
        return Cache::get($cacheKey);
    }
    /**
     * 获取加倍卡加缓存
     */
    private function signInEveryDayMultipleCache($userId){
        $cacheKey = "sign_in_every_day_multiple".$userId;
        if(!Cache::has($cacheKey)){
            $num = SignInSystemBasic::signInEveryDayMultiple($userId);
            Cache::put($cacheKey,$num,5);
        }
        return Cache::get($cacheKey);
    }
}

