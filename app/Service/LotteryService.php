<?php
namespace App\Service;

use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use App\Models\RichLottery;
use App\Service\SendMessage;
use Config, Request, DB, Cache;

class LotteryService
{

    static public function allUserList($alias_name) {
        $key = $alias_name."_allUserAwardList";
        return Cache::remember($key,5, function() use($alias_name) {
            $_act = ActivityService::GetActivityInfoByAlias($alias_name);//获取活动id
            $tmp = RichLottery::select('user_id','award_name')->where('status','>=',1)->where('uuid',$alias_name)->orderBy('created_at','DESC')->take(50)->get()->toArray();
            $newArr = [];
            if(!empty($tmp)){
                foreach ($tmp as $key => $value) {
                    $phone = protectPhone(Func::getUserPhone($value['user_id']) );
                    $newArr[$key]['user'] = $phone;
                    $newArr[$key]['award'] = $value['award_name'];
                }
                
            }
            // //131****6448,iphone xs max 512G
            // $text = GlobalAttributes::getText('open_gift_iphonex');
            // if(!empty($text)){
            //     $textArr = explode(',', $text);
            //     $makeArr['user'] = $textArr[0];
            //     $makeArr['award'] = $textArr[1];
            //     array_unshift($newArr, $makeArr);
            // }
            return $newArr;
        });
    }
    //抽奖发奖
    static public function sendLottAward($userId ,$activity ,$award) {
        $result = [];
        $awards = SendAward::ActiveSendAward($userId, $award['alias_name']);
        if(isset($awards[0]['award_name']) && $awards[0]['status']) {
            $result['awardName'] = $awards[0]['award_name'];
            $result['awardType'] = $awards[0]['award_type'];
            $result['amount'] = $award['size'];
            $result['awardSigni'] = $award['alias_name'];//奖品标示 需要返回给前端
            $remark['awards'] = $awards;
            RichLottery::create([
                'user_id' => $userId,
                'amount' => $award['size'],
                'award_name' => $result['awardName'],
                'uuid' => $activity,//区分活动
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => $result['awardType'],
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            return $result;
        }else{
            return false;
        }

    }

    //特殊奖品
    static public function sendSpaAward($userId ,$activity ,$award) {
        $mailSend = "恭喜您在'翻牌抽奖'活动中获得'一次抽奖机会'奖励。";
        SendMessage::Mail($userId,$mailSend);//站内信是否发送成功
        RichLottery::create([
                'user_id' => $userId,
                'amount' => $award['size'],
                'award_name' => $award['desp'],
                'uuid' => $activity,//区分活动
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 0,
                'remark' => json_encode($award, JSON_UNESCAPED_UNICODE),
            ]);
        return [
            "awardName" => $award['desp'],
            "awardType" => 0,
            "amount" => $award['size'],
            "awardSigni" => $award['alias_name'],
        ];
    }

    static public function getUserProfile() {
        $RpcConfig = Config::get('jsonrpc.server');
        $RPC = new JsonRpcClient($RpcConfig['account']['url'] ,$RpcConfig['account']['config']);
        return $RPC->profile();
    }
    /**
     * 初始化用户抽奖次数(当天)
     *
     */
    static public function _initLotteryCounts($userId ,$key ,$initNum = 0 ,$initStr = null){
        return Attributes::incrementItemByDay($userId , $key ,$initNum ,$initStr);
    }
    /**
     * 用户剩余抽奖次数(当天)
     *
     */
    static public function _getLooteryCounts($userId ,$key ,$limit = null){
        $userAtt = UserAttribute::where(array('user_id' => $userId, 'key' => $key))->where("updated_at",">=",date("Y-m-d"))->first();
        //如果存在 返回，如果不存在  init用户抽奖次数
        $counts = isset($userAtt['number'])?$userAtt['number']:self::_initLotteryCounts($userId,$key);
        //超过限制
        if(isset($limit) && $counts > $limit){
            $userAtt->number = $limit;
            $userAtt->save();
            return $limit;
        }
        return $counts;
    }

    static public function getAttrNumber($uid, $key, $default = null) {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = UserAttribute::where(array('user_id' => $uid, 'key' => $key))->lockForUpdate()->first();
        if(!$res) {
            if(!is_null($default)) {
                $attribute = new UserAttribute();
                $attribute->user_id  = $uid;
                $attribute->key = $key;
                $attribute->number = intval($default);
                $attribute->save();
            }
            return $default;
        }

        return $res['number'];
    }
        /**
     * 获取奖品
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function getAward($item) {
        $number = self::getTotalNum($item);

        $target = rand(1, $number);
        foreach($item as $award) {
            $target = $target - $award['pro'];
            if($target <= 0) {
                // $globalKey = Config::get('withdrawlott.alias_name') . '_' . date('Ymd');
                // Cache::increment($globalKey, 1);
                return $award;
            }
        }

        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }


        /**
     * 获取奖品总数
     *
     * @param $item
     * @return int
     */
    static function getTotalNum($item) {
        $number = 0;
        foreach($item as $award) {
            $number += $award['pro'];
        }
        return $number;
    }

    /**
     * 抽奖间隔验证
     *
     * @param $userId
     * @param $config
     * @return bool
     */
    static function isTooOften($userId, $config) {
        $key = "{$config['alias_name']}_system_{$userId}";
        $value = Cache::pull($key);
        Cache::put($key, time(), $config['interval']);
        if($value && time()-$value < $config['interval']) {
            return true;
        }
        return false;
    }


}
