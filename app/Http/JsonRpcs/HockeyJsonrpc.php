<?php

namespace App\Http\JsonRpcs;


use App\Models\GlobalAttribute;
use App\Models\HdHockeyCard;
use App\Models\HdHockeyCardAward;
use App\Models\HdHockeyCardMsg;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\Attributes;
use Config,DB,Cache;
class HockeyJsonRpc extends JsonRpc {

    /**
     * 卡片信息接口
     *
     * @JsonRpcMethod
     */
    public function HockeyCardInfo() {
        global $userId;
        $config = Config::get("hockey");
        $cardKey = isset($config['card_key']) ? $config['card_key'] : '';
        $cards = isset($config['user_attr']) ? $config['user_attr'] : [];

        //活动倒计时
        $next_time = date("Y-m-d H:i:s") > date("Y-m-d 10:00:00") ? date("Y-m-d 10:00:00",strtotime("+1 day")) : date("Y-m-d 10:00:00");

        $res = ['is_login'=>false,'available'=>false,'is_synthesis'=>true,'num'=>0,'cards'=>$cards,'awards'=>[],'time'=>date("Y-m-d H:i:s"),'next_time'=>$next_time];
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
        }
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $game['available'] = true;
        }
        //获取卡片信息
        if($res['is_login'] == true){
            $attr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$userId])->first();
            if(isset($attr['string'])){
                $res['cards'] = json_decode($attr['string'],1);
                $res['num'] = isset($attr['number']) ? intval($attr['number']) : 0;
            }
        }
        //判读是否可以合成关键卡
        foreach($res['cards'] as $item){
            if($item <=0){
                $res['is_synthesis'] = false;
                break;
            }
        }
        //获取实物奖励信息
        $res['awards'] = HdHockeyCard::where(['status'=>1])->get()->toArray();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        ];
    }
    /**
     * 集卡活动卡片获奖记录
     *
     * @JsonRpcMethod
     */
    public function HockeyCardAwardList($params) {
        $page = $params->page == 0 ? 5 : $params->page;
        $res = ['award_list'=>[],'msg_list'=>[]];
        $awardList = HdHockeyCard::where('status',1)->orderBy('updated_at', 'desc')->take($page);
        $msgList = HdHockeyCardMsg::where('type',1)->orderBy('created_at', 'desc')->take($page);
        $res['award_list'] = $awardList;
        $res['msg_list'] = $msgList;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        ];
    }
    /**
     * 冠军卡合成接口
     *
     * @JsonRpcMethod
     */
    public function HockeyCardSynthesis() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $config = Config::get("hockey");
        $cardKey = isset($config['card_key']) ? $config['card_key'] : '';
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        DB::beginTransaction();
        $attr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$userId])->lockForUpdate()->first();
        $cards = isset($attr['string']) ? $attr['string'] : [];
        if(empty($cards)){
            DB::rollBack();//回滚
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }
        foreach($cards as $k =>$v){
            if($v <= 0){
                //卡片有不够的
                DB::rollBack();//回滚
                throw new OmgException(OmgException::NUMBER_IS_NULL);
                break;
            }
        }
        //减少卡片数量
        $newCards = [];
        foreach($cards as $key =>$val){
            $newCards[$key] = $cards[$key] - 1;
        }
        //添加到冠军卡表中
        $insertData['user_id'] = $userId;
        $insertData['before'] = json_encode($cards);
        $insertData['after'] = json_encode($newCards);
        $insertData['type'] = 1;
        $insertData['status'] = 0;
        $insertData['created_at'] = date("Y-m-d H:i:s");
        HdHockeyCard::insertGetId($insertData);
        //修改用户属性
        $attr->string = json_encode($newCards);
        $attr->decrement('number',6);
        $attr->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>'冠军卡合成成功'
        ];
    }
    /**
     * 冠军卡兑换奖品接口
     *
     * @JsonRpcMethod
     */
    public function HockeyCardExchange($params) {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get("hockey");
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //兑换实物奖品id
        $awardId = isset($params->award_id) && $params->award_id > 0 ? intval($params->award_id) : 0 ;
        if($awardId <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        DB::beginTransaction();
        $goldCard = HdHockeyCard::where(['user_id'=>$userId,'type'=>2])->lockForUpdate()->first();
        if(!isset($goldCard['type'])){
            DB::rollBack();
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }
        //兑换实物
        $goldCard->award_id = $awardId;
        $goldCard->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>'兑换奖品成功'
        ];
    }
    /**
     * 冠军卡抢实物接口每天10点第一个可以获得
     *
     * @JsonRpcMethod
     */
    public function HockeyCardAward() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $key = "hockey_card_award_key_".date("Ymd");
        if(date("Y-m-d H:i:s") >= date("Y-m-d 10:00:00")){
            DB::beginTransaction();
            $luckAwardKey = "hockey_card_luck_award_key";
            $lockAwardCount = GlobalAttribute::where('key',$luckAwardKey)->count();
            if($lockAwardCount < 1){
                GlobalAttribute::create(['key'=>$luckAwardKey,'number'=>0]);
            }
            GlobalAttribute::where('key',$luckAwardKey)->lockForUpdate()->first();
            $isExist = Cache::get($key);
            if($isExist > 0){
                DB::rollBack();
                throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
            }else{
                Cache::rememberForever($key,function($userId) {
                    return $userId;
                });
                $luckUser = Cache::get($key);
                //修改为已获得实物抽奖卡状态
                HdHockeyCard::where(['user_id'=>$luckUser,'type'=>1])->update(['type'=>2]);
                //修改锁住的key加1
                GlobalAttribute::where('key',$luckAwardKey)->increment('number',1);
                DB::commit();
            }
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'成功活动兑换奖品卡'
            ];
        }
    }
    /**
     * 卡片送卡接口
     *
     * @JsonRpcMethod
     */
    public function HockeyCardInvite($params) {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $inviteId = $params->invite_id;
        $cardName = $params->card_name;
        if($inviteId <= 0 || empty($cardName)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $config = Config::get("hockey");
        $cardKey = isset($config['card_key']) ? $config['card_key'] : '';
        $userAttr = isset($config['user_attr']) ? $config['user_attr'] : '';
        DB::beginTransaction();
        $inviteAttr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$inviteId])->lockForUpdate()->first();
        //判断该卡片分享用户是否有
        $inviteCard = isset($inviteAttr['string']) ? json_decode($inviteAttr['string']) : [];
        if(empty($inviteCard) || $inviteCard[$cardName] < 1){
            DB::rollBack();
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

        //给领取人添加卡片
        $attr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$userId])->first();
        $cards = isset($attr['string']) ? json_decode($attr['string']) : [];
        if(!empty($cards)){
            $cards[$cardName] += 1;
            $attr->number += 1;
            $attr->string = json_encode($cards);
            $attr->save();
        }else{
            $userAttr[$cardName] = 1;
            UserAttribute::create(['user_id' => $userId, 'key' => $cardKey,  'number' => 1, 'string'=>json_encode($userAttr)]);
        }
        //邀请人减去相应次数
        //减少分享人次数
        $inviteCard[$cardName] -= 1;
        $inviteAttr->string = json_encode($inviteCard);
        $inviteAttr->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>'领取成功'
        ];

    }
    /**
     * 竞猜信息接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessInfo($params) {

    }
    /**
     * 竞猜接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessDrew($params) {

    }
    /**
     * 竞猜榜单接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessTop($params) {

    }
}
