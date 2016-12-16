<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Xjdb;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Validator, Config, Request;

class XjdbJsonRpc extends JsonRpc
{

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function xjdbInfo($params) {
        global $userId;
        
        $validator = Validator::make((array)$params, [
            'position' => 'required|string',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }

        $position = $params->position;
        $game = ['available' => false, 'endSeconds' => 0];
        $user = ['available' => true, 'startSeconds' => 0, 'login' => 0];
        $xjdbConfig = Config::get('activity.xjdb');
        $key = "xjdb_${position}";
        
        if(isset($xjdbConfig[$position])) {
            $config = $xjdbConfig[$position];
            
            if (strtotime($config['start_at']) < time() && strtotime($config['end_at']) > time()) {
                $game['available'] = true;
                $game['endSeconds'] = strtotime($config['end_at']) - time();
            }
        }

        if(!empty($userId)) {
            $user['login'] = 1;
            $item = Attributes::getItem($userId, $key);
            if($item && date('Y-m-d H', $item['number']) === date('Y-m-d H')) {
                $user['available'] = false;
                $user['startSeconds'] = (strtotime(date('Y-m-d H:00:00')) + 3600) - time();
            }
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'game' => $game,
                'user' => $user
            ],
        ];

    }

     /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function xjdbDraw($params) {
        global $userId;
        
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        
        $validator = Validator::make((array)$params, [
            'position' => 'required|string',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }       
        
        $position = $params->position;
        $xjdbConfig = Config::get('activity.xjdb');
        $key = "xjdb_${position}";
        $awardStatus = true;
        $remark = [];

        $result = [
            'isAward' => true,
            'awardName' => '',
            'nextSeconds' => (strtotime(date('Y-m-d H:00:00')) + 3600) - time(),
            'msg' => '',
        ];
        
        // 活动不存在
        if(!isset($xjdbConfig[$position])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        // 活动已结束
        $config = $xjdbConfig[$position];
        
        if (strtotime($config['start_at']) > time() || strtotime($config['end_at']) < time()) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        
        // 已领取
        $item = Attributes::getItem($userId, $key);
        if($item && date('Y-m-d H', $item['number']) === date('Y-m-d H')) {
            $result['isAward'] = false;
            $cooldownArr = Config::get('activity.xjdb_global.cooldown_msg');
            $result['msg'] = $cooldownArr[array_rand($cooldownArr, 1)];
            $result['awardName'] = $item['string'];
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result,
            ];
        }

        // 获取奖品
        $award = $this->getAward($config);
        $remark['rmbAward'] = $award;

        // 奖品获取失败
        if(!$award) {
            $awardStatus = false;
            $remark['failReason'] = '奖品不存在';
        }
        

        // 判断奖品数量是否足够
        if($awardStatus) {
            $uniqueKey = "xjdb_{$position}_{$award['start']}_{$award['end']}_{$award['money']}";
            $num = GlobalAttributes::getNumberByDay($uniqueKey);
            if($num >= $award['num']) {
                $awardStatus = false;
                $remark['failReason'] = '奖品剩余数量不足';
            }
        }
        
        // 判断投资是否满足资格
        if($awardStatus) {
            $maxInvest = $this->getMaxInvest($userId);
            if( $maxInvest < $award['require'] ) {
                $awardStatus= false;
                $remark['failReason'] = "用户投资额度{$maxInvest}不满足获奖资格{$award['require']}";
            }
        }

        // 判断用户是否有交易密码
        if($awardStatus) {
            $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
            $userBase = $client->userBasicInfo(array('userId' => $userId));
            if(!$userBase || !isset($userBase['result']['data']['trade_pwd']) || empty($userBase['result']['data']['trade_pwd']) ) {
                $awardStatus= false;
                $remark['failReason'] = '用户未设置交易密码';
            }
        }
         

        // 判断今天是否到达领取上限
        if($awardStatus) {
            $num = $this->getSmallAwardNum($userId);
            if($num >= Config::get('activity.xjdb_global.small_num')) {
                $awardStatus = false;
                $remark['failReason'] = "用户已达到领取上限:{$num}";
            }
        }

        // 发送现金
        if($awardStatus) {
            $uuid = SendAward::create_guid();

            // 创建记录
            $result['awardName'] = $award['money'] . '元现金';
            $result['msg'] = str_replace('{awardName}', $result['awardName'],  Config::get('activity.xjdb_global.award_msg.rmb'));
            $res = Xjdb::create([
                'user_id' => $userId,
                'award_name' => $result['awardName'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'status' => 0,
                'type' => 7,
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);

            $client = new JsonRpcClient(env('TRADE_HTTP_URL'));
            $purchaseRes = $client->purchaseGoldCoin([
                'userId' => $userId,
                'id' => $res->id,
                'uuid' => $uuid,
                'amount' => $award['money'],
                'sign' => hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft")
            ]);
            
            $remark['addMoneyRes'] = $result;
            // 成功
            if(isset($purchaseRes['result'])) {
                $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]); 
                
                if($award['isSmall']){
                    $this->addSmallAwardNum($userId, 1);
                }
                $uniqueKey = "xjdb_{$position}_{$award['start']}_{$award['end']}_{$award['money']}";
                GlobalAttributes::incrementByDay($uniqueKey, 1);
            }

            // 失败
            if(!isset($purchaseRes['result'])) {
                $awardStatus = false;
                $remark['failReason'] = "现金发送失败:";
                $remark['purchaseRes'] = $purchaseRes;
                $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]); 
            }
        }

        // 发体验金
        if(!$awardStatus) {
            $aliasName = 'new_year_xjdb';
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
            if(isset($awards[0]['award_name'])) {
                $result['awardName'] = $awards[0]['award_name'];
                $result['msg'] = str_replace('{awardName}', $result['awardName'],  Config::get('activity.xjdb_global.award_msg.tyj'));
                Xjdb::create([
                    'user_id' => $userId,        
                    'award_name' => $result['awardName'],
                    'uuid' => '',
                    'ip' => Request::getClientIp(),
                    'status' => 1,
                    'type' => 3,
                    'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                ]);
            }else{
                throw new OmgException(OmgException::API_FAILED);
            }
        }

        Attributes::setItem($userId, $key, time(), $result['awardName']);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    private function getAward($config) {
        $items = $config['items']; 
        $h = date('H');
        $awards = [];
        $weight = 0;
        foreach($items as $item) {
            if($item['start'] <= $h && $item['end'] >= $h) {
                $awards = $item['awards'];    
                break;
            }    
        }
        
        if(!$awards) {
            return false;    
        }

        foreach($awards as $award) {
            $weight += $award['weight'];
        }

        $target = rand(1, $weight);
        foreach($awards as $award) {
            $target = $target - $award['weight'];
            if($target <= 0) {
                $award['start'] = $item['start'];
                $award['end'] = $item['end'];
                return $award;
            }
        }

        return false;
    }

    /**
     * 获取活动时间内最大投资金额
     */
    private function getMaxInvest($userId) {
        $key = 'xjdb_max_invest';
        $item = Attributes::getItem($userId, $key);
        if(!$item) {
            return 0;    
        }
        return $item['number'];
    }
    
    /**
     * 获取今天小奖中奖次数 
     */
    private function getSmallAwardNum($userId) {
        $key = 'xjdb_small_award_num';
        $item = Attributes::getItem($userId, $key);
        if(!$item) {
            return 0;    
        }
        
        //不为今天
        if(date('Ymd', strtotime($item['update_at'])) !== date('Ymd')) {
            return 0;            
        }
        
        return $item['number'];
    }
    
    /**
     * 小奖次数+1
     */
     private function addSmallAwardNum($userId, $num =1) {
        $key = 'xjdb_small_award_num';
        $item = Attributes::getItem($userId, $key);
        if(!$item) {
            return Attributes::increment($userId, $key, $num);
        }
        
        // 不为今天
        if(date('Ymd', strtotime($item['update_at'])) !== date('Ymd')) {
            Attributes::setItem($userId, $key, $num);
            return $num;
        }
         
        return Attributes::increment($userId, $key, $num);
        
    }
}

