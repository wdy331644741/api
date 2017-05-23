<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\SignInSystem;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use Config, Request, Cache;

class SignInSystemJsonRpc extends JsonRpc
{
    /**
     * 获取摇一摇奖品数量
     *
     * @JsonRpcMethod
     */
    public function signInSystemAwardNum() {
        $number = Cache::remember('sign_in_system_temp_used_num', 0.05, function(){
            $config = Config::get('signinsystem');
            if(!ActivityService::isExistByAlias($config['alias_name'])) {
                return 0;
            }
            $item = $this->selectList($config['lists']);
            return $this->getLastGlobalNum($item);
        });
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'number' => $number,
            ],
        ];
    }

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function signInSystemInfo() {
        global $userId;

        $config = Config::get('signinsystem');
        $user = ['invested' => false, 'login' => false, 'multiple' => 1, 'multiple_card' => 0];
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
            $multipleCard = SignInSystemBasic::signInEveryDayMultiple($userId);
            if($multipleCard > 0){
                $result['multiple_card'] = $multipleCard;
            }
        }


        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $game['available'] = true;
        }


        // 下次活动开始时间
        if($game['available']) {
            $item = $this->selectList($config['lists']);
            $game['awardNum'] = $this->getLastGlobalNum($item);
            $game['nextSeconds'] = $item['endTimestamps'] - time() + rand(0,3);
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

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $config = Config::get('signinsystem');

        if(!$this->isInvested($userId, $config)) {
            throw new OmgException(OmgException::CONDITION_NOT_ENOUGH);
        }

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
        $multipleCard = SignInSystemBasic::signInEveryDayMultiple($userId);
        if($multipleCard > 0){
            $result['multiple_card'] = $multipleCard;
        }

        // 发送现金
        if($award['is_rmb']) {
            $uuid = SendAward::create_guid();

            // 创建记录
            $result['awardName'] = $award['size'] . '元';
            $result['amount'] = strval($award['size']);
            $result['awardType'] = 7;
            $res = SignInSystem::create([
                'user_id' => $userId,
                'award_name' => $result['awardName'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'amount' => $award['size'],
                'multiple' => $result['multiple'],
                'multiple_card' => $multipleCard,
                'user_agent' => Request::header('User-Agent'),
                'status' => 0,
                'type' => 7,
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);

            $amount = bcmul($award['size'], $result['multiple'] + $multipleCard, 2);
            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $amount, 'shake');

            $remark['addMoneyRes'] = $result;
            // 成功
            if(isset($purchaseRes['result'])) {
                $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            }

            // 失败
            if(!isset($purchaseRes['result'])) {
                $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
                throw new OmgException(OmgException::API_FAILED);
            }
        }

        // 根据别名发活动奖品
        if(!$award['is_rmb']) {
            $aliasName = $award['alias_name'];
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
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
        }

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
}

