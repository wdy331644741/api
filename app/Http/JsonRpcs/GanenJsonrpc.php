<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\GlobalAttributes;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use App\Service\Func;
use App\Service\ActivityService;
use App\Models\Ganen;
use App\Models\UserAttribute;
use Validator, Request, Cache, DB, Session;
use App\Service\GanenService;

class GanenJsonRpc extends JsonRpc
{

    /**
     * 获取当前的文字数量
     *
     * @JsonRpcMethod
     */
    public function ganenInfo() {
        global $userId;

        $user = ['login' => 0];
        $game = ['available' => 0];

        $config = config('ganen');

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['key'])) {
            $game['available'] = 1;
        }
        if(!$userId) {
            $user['login'] = 0;
        }

        if($userId) {
            $user['login'] = 1;
            $user['exchange_num'] = GanenService::getExchangeNum($userId);
            $user['words'] = GanenService::getChance($userId);
            $user['my_reward'] = GanenService::getMyReward($userId);
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'game' => $game,
                'user' => $user,
            ]
        );
    }

    /**
     * 获取活动排名
     *
     * @JsonRpcMethod
     */
    public function ganenList() {
        $config = config('ganen');
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $this->getExchangeList($config),//最近兑换数据
                'rank' => $this->getExchangeRank($config)//总排名
            ]
        );
    }

    /**
     * 兑换奖品
     *
     * @JsonRpcMethod
     */
    public function ganenExchange() {
        global $userId;

        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if(!GanenService::minusChance($userId, 1)) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $config = config('ganen');

        $award = $this->getAward($config);

        $remark = [];
        // 发送现金
        if($award['is_rmb']) {
            $uuid = SendAward::create_guid();

            // 创建记录
            $result['awardName'] = $award['size'] . '元现金';
            $result['amount'] = strval($award['size']);
            $result['awardType'] = 7;
            $res = ganen::create([
                'user_id' => $userId,
                'award_name' => $result['awardName'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 0,
                'type' => 7,
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            //需要用户中心 定义一个流水 标记
            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $award['size'], 'dragon_tiger');
            $remark['addMoneyRes'] = $purchaseRes;
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
                ganen::create([
                    'user_id' => $userId,
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
     * 获取兑换排行
     */
    private function getExchangeRank($config) {
        $key = $config['key'] . '_rank';
        return Cache::remember($key, 5, function() use($config) {
            $data = UserAttribute::select('user_id', 'number')->where(['key' => $config['key']])->where('number', '!=', 0)->orderBy('number', 'desc')->take(10)->get();
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
     * 获取最近兑换数据
     */
    private function getExchangeList($config) {
        $key = $config['key'] . '_list';
        return Cache::remember($key, 2, function() use($config) {
            $number = 80;
            $result = [];
            $data = ganen::select('user_id', 'award_name')->orderBy('id', 'desc')->take($number)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $award = [];
                    $phone = Func::getUserPhone($item['user_id']);
                    $award['award_name'] = $item['award_name'];
                    $award['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    $result[] = $award;
                }
            }

            // if(count($result) !== $number) {
            //     return $result;
            // }

            foreach($config['fake_user'] as $user) {
                $award = [];
                for($i=0; $i < $user['number']; $i++) {
                    $award['award_name'] = $user['award_name'];
                    $randIndex = array_rand($config['phone_prefix_list']);
                    $award['phone'] = $config['phone_prefix_list'][$randIndex] . '******' . rand(00, 99);
                    $result[] = $award;
                }
            }
            shuffle($result);
            return $result;
        });
    }


    /**
     * @param $word
     * @param $number
     * @return int
     */
    private function decrementNum($word, $number) {
        $config = config('ganen');
        $prefix = $config['key'];
        if(isset($config['probability'][$word])) {
            $ident = $prefix . '_' . $word;
            return GlobalAttributes::decrementByTodaySeconds($ident, $number, $config['fresh_time']);
        }
        return 0;
    }

    private function getAward($config) {
        $awards = $config['awards'];
        $number = $this->getTotalNum($awards);
        $target = rand(1, $number);
        foreach($awards as $award) {
            $target = $target - $award['num'];
            if($target <= 0) {
                $key = $award['alias_name'] . '_' . date('Ymd');
                $usedNumber = GlobalAttributes::increment($key);
                if($usedNumber < $award['num']) {
                    return $award;
                }
            }
        }
        // 强制发一个非rmb礼物
        return $this->getVirtualAward($awards);
    }

    /**
     * 获取奖品总数
     *
     * @param $item
     * @return int
     */
    private function getTotalNum($awards) {
        $number = 0;
        foreach($awards as $award) {
            $number += $award['num'];
        }
        return $number;
    }

    /**
     * 获取无限发放的奖品
     */
    private function getVirtualAward($awards) {
        $number = 0;
        foreach($awards as $award) {
            if($award['is_rmb'] === 0) {
                $number += $award['num'];
            }
        }
        $target = rand(1, $number);
        foreach($awards as $award) {
            if($award['is_rmb'] === 0) {
                $target = $target - $award['num'];
                if ($target <= 0) {
                    return $award;
                }
            }
        }
    }
}
