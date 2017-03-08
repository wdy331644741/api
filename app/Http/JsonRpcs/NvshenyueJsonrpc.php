<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\GlobalAttributes;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use App\Service\Func;
use App\Service\ActivityService;
use App\Models\Nvshenyue;
use App\Models\UserAttribute;
use Validator, Request, Cache, DB, Session;
use App\Service\NvshenyueService;

class NvshenyueJsonRpc extends JsonRpc
{
    /**
     * 获取当前的文字数量
     *
     * @JsonRpcMethod
     */
    public function nvshenyueInfo() {
        global $userId;

        $user = ['login' => 0];
        $game = ['available' => 0];

        $config = config('nvshenyue');

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['key'])) {
            $game['available'] = 1;
        }
        if(!$userId) {
            $user['login'] = 0;
        }

        if($userId) {
            $user['login'] = 1;
            $user['exchange_num'] = NvshenyueService::getExchangeNum($userId);
            $user['words'] = NvshenyueService::getChance($userId);
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
    public function nvshenyueList() {
        $config = config('nvshenyue');
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $this->getExchangeList($config),
                'rank' => $this->getExchangeRank($config)
            ]
        );
    }

    /**
     * 兑换奖品
     *
     * @JsonRpcMethod
     */
    public function nvshenyueExchange() {
        global $userId;

        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if(!NvshenyueService::minusChance($userId, 1)) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $config = config('nvshenyue');

        $award = $this->getAward($config);

        $remark = [];
        // 发送现金
        if($award['is_rmb']) {
            $uuid = SendAward::create_guid();

            // 创建记录
            $result['awardName'] = $award['size'] . '元现金';
            $result['amount'] = strval($award['size']);
            $result['awardType'] = 7;
            $res = Nvshenyue::create([
                'user_id' => $userId,
                'award_name' => $result['awardName'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 0,
                'type' => 7,
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);

            $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
            $purchaseRes = $client->goddessIncrement([
                'userId' => $userId,
                'id' => $res->id,
                'uuid' => $uuid,
                'amount' => $award['size'],
                'sign' => hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft")
            ]);

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
                Nvshenyue::create([
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
     * 购买
     *
     * @JsonRpcMethod
     */
    public function nvshenyueBuy($params) {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if (!isset($params->tradePassword) || !isset($params->word) || !isset($params->number)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }

        $tradePassword = strval($params->tradePassword);
        $number = abs(intval($params->number));
        $word = trim($params->word);
        if($number > $this->getWordBuyNum($word)) {
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

        $uuid = SendAward::create_guid();

        //调用孙峰接口余额购买次数
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $param['userId'] = $userId;
        $param['id'] = 0;
        $param['uuid'] = $uuid;
        $param['amount'] = $number * 2;
        $param['trade_pwd'] = $tradePassword;
        $param['sign'] = hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft");
        $result = $client->wordPurchase($param);

        // 成功
        if(isset($result['result']) && !empty($result['result'])) {
            $words = NvshenyueService::addChanceByBuy($userId, $word, $number);
            $this->decrementNum($word, $number);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'status' => 1,
                    'words' => $words,
                ]
            );
        }

        if(isset($result['error']['code'])) {
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'status' => 0,
                    'result' => $result['error'],
                ]
            );
        }

        throw new OmgException(OmgException::API_FAILED);
    }

    /**
     * 查询剩余数量
     *
     * @JsonRpcMethod
     */
    public function nvshenyueBuyNum() {
        $config = config('nvshenyue');
        $prefix = $config['key'];
        $words = [];
        foreach($config['probability'] as $key => $value) {
            $ident = $prefix . '_' . $key;
            $words[$key] = GlobalAttributes::getNumberByTodaySeconds($ident, $config['buy_number'], $config['fresh_time']);
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $words
        );
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
            $data = Nvshenyue::select('user_id', 'award_name')->orderBy('id', 'desc')->take($number)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $award = [];
                    $phone = Func::getUserPhone($item['user_id']);
                    $award['award_name'] = $item['award_name'];
                    $award['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    $result[] = $award;
                }
            }

            if(count($result) !== $number) {
                return $result;
            }

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
     * @return int
     */
    private function getWordBuyNum($word) {
        $config = config('nvshenyue');
        $prefix = $config['key'];
        if(isset($config['probability'][$word])) {
            $ident = $prefix . '_' . $word;
            $num = GlobalAttributes::getNumberByTodaySeconds($ident, $config['buy_number'], $config['fresh_time']);
            if($num < 0) {
                return 0;
            }else{
                return $num;
            }
        }
        return 0;
    }

    /**
     * @param $word
     * @param $number
     * @return int
     */
    private function decrementNum($word, $number) {
        $config = config('nvshenyue');
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
            if(award['is_rmb'] === 0) {
                $number += $award['num'];
            }
        }
        $target = rand(1, $number);
        foreach($awards as $award) {
            if(award['is_rmb'] === 0) {
                $target = $target - $award['num'];
                if ($target <= 0) {
                    return $award;
                }
            }
        }
    }
}
