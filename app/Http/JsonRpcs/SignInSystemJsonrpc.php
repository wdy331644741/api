<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\SignInSystem;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Jobs\SignInSystemJob;
use Config, Request, Cache,DB;
use Illuminate\Foundation\Bus\DispatchesJobs;

class SignInSystemJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function signInSystemInfo() {
        global $userId;

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
            $multipleCard = SignInSystemBasic::signInEveryDayMultiple($userId);
            if($multipleCard > 0){
                $user['multiple_card'] = $multipleCard;
            }
            //获取用户摇一摇结束时间
            $userAtt = UserAttribute::where(['user_id'=> $userId,'key' => $config['trade_alias_name']])->first();
            $times = isset($userAtt->number) ? $userAtt->number - time() : 0;
            $user['user_end_time'] = $times <= 0 ? 0 : $times;
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
        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['trade_alias_name'])->first()->lockForUpdate();

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
        //放到队列
        $this->dispatch(new SignInSystemJob($userId,$award,$result));
        //现金
        if($award['is_rmb']) {
            $result['awardName'] = $award['size'] . '元';
            $result['awardType'] = 7;
            $result['amount'] = strval($award['size']);
        }else{
            //100元摇一摇红包
            $result['awardName'] = '100元摇一摇红包';
            $result['awardType'] = 2;
            $result['amount'] = strval(intval($result['awardName']));
        }
        //事务提交
        DB::commit();
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
        if(time() - $item['startTimestamps'] > $item['times']) {
            return 0;
        }

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

