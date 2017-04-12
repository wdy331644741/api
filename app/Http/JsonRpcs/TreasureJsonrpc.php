<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Treasure;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Validator, Config, Request, Cache, DB, Session;

class TreasureJsonRpc extends JsonRpc
{
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function treasureStatus() {
        global $userId;

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $result = [
            'copper' => [],
            'silver' => [],
            'gold' => []
        ];

        foreach($result as $key => &$item){
            $status = $this->treasureIsOpen($userId,$key);
            $item = $status;
        }
        //用户待收本息
        $result['collect_money'] = self::getCollectMoney($userId);
        $result['collect_money'] = number_format($result['collect_money'],2,".","");
        //用户开宝箱次数
        $number = UserAttribute::where(['user_id'=>$userId,'key'=>'treasure_num'])->select('number')->first();
        $result['num'] = isset($number['number']) ? $number['number'] : 0;
        //用户获取宝箱总金额
        $money = Treasure::where(['user_id'=>$userId])->sum('amount');
        $result['has_money'] = !empty($money) ? $money : "0.00";
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function treasureDraw($params) {
        global $userId;

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $type = $params->type;

        $config = Config::get('treasure');

        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $item = isset($config['config'][$type]) ? $config['config'][$type] : array();
        if(empty($item)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //宝箱是否开启
        $isOpen = $this->treasureIsOpen($userId,$type);
        if(isset($isOpen['is_open']) && $isOpen['is_open'] == 0){
            throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
        }

        //宝箱开启次数验证
        if(!$this->isInvested($userId)) {
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

//        // 是否触发间隔限制
//        if($this->isTooOften($userId, $config)) {
//            throw new OmgException(OmgException::API_BUSY);
//        }

        $result = [
            'awardName' => '',
            'amount' => 0
        ];
        $remark = [];

        // 获取奖品
        $award = $this->getAward($item,$type);

        // 发送现金
        $uuid = SendAward::create_guid();

        // 创建记录
        $result['awardName'] = $award['size'] . '元';
        $result['amount'] = strval($award['size']);
        //宝箱类型
        $treasureType = 0;
        if($type == 'copper'){
            $treasureType = 1;
        }elseif($type == 'silver'){
            $treasureType = 2;
        }elseif($type == 'gold'){
            $treasureType = 3;
        }
        $res = Treasure::create([
            'user_id' => $userId,
            'amount' => $award['size'],
            'award_name' => $result['awardName'],
            'uuid' => $uuid,
            'ip' => Request::getClientIp(),
            'user_agent' => Request::header('User-Agent'),
            'status' => 0,
            'type' => $treasureType,
            'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
        ]);
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $purchaseRes = $client->incrementAvailable([
            'record_id' => $res->id,
            'uuid' => $uuid,
            'amount' => $award['size'],
            'type' => 'cash_box',
            'sign' => hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft")
        ]);

        $remark['addMoneyRes'] = $result;

        // 成功
        if(isset($purchaseRes['result'])) {
            $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            //次数-1
            Attributes::decrement($userId,'treasure_num',1);
        }

        // 失败
        if(!isset($purchaseRes['result'])) {
            $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            throw new OmgException(OmgException::API_FAILED);
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 查询宝箱获奖列表
     *
     * @JsonRpcMethod
     */
    public function treasureList($params) {
        $num = isset($params->num) && !empty($params->num) ? $params->num : 15;

        //查询
        $list = Treasure::where(['status'=>1])->take($num)->orderByRaw("id desc")->get();
        $result = [];
        foreach($list as $item){
            //获取用户手机号
            if(isset($item->user_id) && !empty($item->user_id)){
                $phone = Func::getUserPhone($item->user_id);
                $phone = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                $typeName = "";
                if($item->type == 1){
                    $typeName = "初";
                }elseif($item->type == 2){
                    $typeName = "中";
                }elseif($item->type == 3){
                    $typeName = "高";
                }
                $result[] = "恭喜用户".$phone."开启".$typeName."级宝箱获得".$item->award_name."现金";
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 获取奖品
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    private function getAward($awards,$type) {
        if(empty($awards)){
            return false;
        }
        $weight = 0;
        foreach($awards as $award) {
            $weight += $award['weight'];
        }
        $target = rand(1, $weight);
        foreach($awards as $award) {
            $target = $target - $award['weight'];
            //有次数限制
            if($target <= 0) {
                $key = $award['alias_name'] . '_' . date('Ymd') . '_'. $type;
                $keyNum = Cache::get($key);
                //发不限奖
                if($award['is_notLimit'] == 1) {
                    Cache::increment($key);
                    return $award;
                }

                if($keyNum < $award['num']) {
                    Cache::increment($key);
                    return $award;
                }
            }
        }
        //发不限奖
        $aliasName = isset($awards[0]['alias_name']) ? $awards[0]['alias_name'] : '';
        $key = $aliasName . '_' . date('Ymd') . '_'. $type;
        Cache::increment($key);
        return $awards[0];
    }

    /**
     * 抽奖间隔验证
     *
     * @param $userId
     * @param $config
     * @return bool
     */
    private function isTooOften($userId, $config) {
        $key = "treasure_interval_{$userId}";
        $value = Cache::pull($key);
        Cache::put($key, time(), 10);
        if($value && time()-$value < $config['interval']) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户抽奖次数
     *
     */
    private function isInvested($userId) {
        $key = "treasure_num";

        $inviteNum = Attributes::getNumber($userId, $key, 0);

        return $inviteNum;
    }
    /**
     * 宝箱是否开启
     *
     */
    private function treasureIsOpen($userId,$type) {
        $return = ['is_open'=>0,'diff_money'=>"0.00"];
        //从刘奇那边获取代收本金
        $money = self::getCollectMoney($userId);
        $section = Config::get("treasure.".$type);
        if(empty($section) || !isset($section['min'])){
            return $return;
        }
        if($money >= $section['min']){
            $return['is_open'] = 1;
        }
        if($money < $section['min']){
            $return['diff_money'] = number_format($section['min']-$money,2,".","");
        }
        return $return;
    }

    /**
     * 用户待收本息
     * @param $userId
     * @return int
     */
    private function getCollectMoney($userId){
        $client = new JsonRpcClient(env('MARK_HTTP_URL'));
        $userId = !empty($userId) ? intval($userId) : 0;
        $info = $client->assetStatistics(["userid" => $userId]);
        if(isset($info['result']['data']['unPaidIncome']) && isset($info['result']['data']['unpayed_principle'])){
            return $info['result']['data']['unPaidIncome']+$info['result']['data']['unpayed_principle'];
        }
        return 0;
    }
}

