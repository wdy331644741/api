<?php

namespace App\Http\JsonRpcs;


use App\Models\GlobalAttribute;
use App\Models\HdHockeyCard;
use App\Models\HdHockeyCardAward;
use App\Models\HdHockeyCardMsg;
use App\Models\HdHockeyGuess;
use App\Models\HdHockeyGuessConfig;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\Func;
use App\Service\Hockey;
use Config, DB, Cache;
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
        $next_time = strtotime($next_time) - time();
        $res = [
            'is_login'=>false,
            'available'=>false,
            'is_synthesis'=>true,
            'is_cash_exchange'=>false,
            'is_object_exchange'=>false,
            'cash_exchange_num' => 0,
            'num'=>0,
            'cards'=>$cards,
            'awards'=>[],
            'next_time'=>$next_time];
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
        }
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['card_alias_name'])) {
            $res['available'] = true;
        }
        //登陆状态
        if($res['is_login'] == true){
            //获取卡片信息
            $attr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$userId])->first();
            if(isset($attr['string'])){
                $res['cards'] = json_decode($attr['string'],1);
                $res['num'] = isset($attr['number']) ? intval($attr['number']) : 0;
            }
            //判断是否可以兑换现金和实物奖励
            $cardList = HdHockeyCard::where(["user_id"=>$userId,"status"=>0])->get()->toArray();
            foreach($cardList as $item){
                if($item['type'] == 1){
                    $res['is_cash_exchange'] = true;
                }
                if($item['type'] == 2){
                    $res['is_object_exchange'] = true;
                }
            }
            //获取已兑换多少现金奖品
            $res['cash_exchange_num'] = HdHockeyCard::where(["user_id"=>$userId,"type"=>1,"status"=>1])->count();
        }
        //判读是否可以合成关键卡
        foreach($res['cards'] as $item){
            if($item <=0){
                $res['is_synthesis'] = false;
                break;
            }
        }
        //获取实物奖励信息
        $res['awards']['object'] = HdHockeyCardAward::where(['status'=>1])->get()->toArray();
        $res['awards']['cash'] = $config['cash_list'];
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
        $awardList = HdHockeyCard::where('status',1)->orderBy('updated_at', 'desc')->take($page)->get()->toArray();
        $msgList = HdHockeyCardMsg::where('type',1)->select('id','user_id','msg','created_at')->orderBy('created_at', 'desc')->take($page)->get()->toArray();
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
        if(!ActivityService::isExistByAlias($config['card_alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        DB::beginTransaction();
        $attr = UserAttribute::where(['key'=>$cardKey,'user_id'=>$userId])->lockForUpdate()->first();
        $cards = isset($attr['string']) ? json_decode($attr['string'],1) : [];
        if(empty($cards)){
            DB::rollBack();//回滚
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        foreach($cards as $k =>$v){
            if($v <= 0){
                //卡片有不够的
                DB::rollBack();//回滚
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
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
     * 卡兑换奖品接口
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
        if(!ActivityService::isExistByAlias($config['card_alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //兑换类型1是现金2是实物
        $type = isset($params->type) && $params->type > 0 ? intval($params->type) : 0;
        if($type <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        DB::beginTransaction();
        UserAttribute::where(['user_id'=>$userId,'key'=>$config['card_key']])->lockForUpdate()->first();
        if($type == 1){//兑换现金
            $cashCard = HdHockeyCard::where(['user_id'=>$userId,'type'=>1,'status'=>0])->lockForUpdate()->first();
            if(!isset($cashCard['type'])){
                DB::rollBack();
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
            //获取应得奖励
            $cashName = Hockey::getHockeyCardExchangeAward($userId);
            if($cashName > 0){
                //发送现金奖励
                $uuid = Func::create_guid();
                $res = Func::incrementAvailable($userId,$cashCard['id'],$uuid,intval($cashName),'cash_type');
                if (!isset($res['result']['code'])) {
                    DB::rollBack();
                    throw new OmgException(OmgException::API_FAILED);
                }
                $cashCard->status = 1;
                $cashCard->award_id = intval($cashName);
                $cashCard->award_name = $cashName;
                $cashCard->save();
                DB::commit();
                return [
                    'code' => 0,
                    'message' => 'success',
                    'data' =>'兑换现金奖励成功'
                ];
            }
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //兑换实物奖品id
        $awardId = isset($params->award_id) ? intval($params->award_id) : 0;
        if($awardId <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        //判断实物奖励是否存在
        $award = HdHockeyCardAward::where(["id"=>$awardId,"status"=>1])->first();
        if(!isset($award['id'])){
            throw new OmgException(OmgException::AWARD_NOT_EXIST);
        }
        //兑换实物
        $goldCard = HdHockeyCard::where(['user_id'=>$userId,'type'=>2,'status'=>0])->lockForUpdate()->first();
        if(!isset($goldCard['type'])){
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //兑换实物
        $goldCard->status = 1;
        $goldCard->award_id = $awardId;
        $goldCard->award_name = isset($award['award_name']) ? $award['award_name'] : '';
        $goldCard->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>'兑换奖品成功'
        ];
    }
    /**
     * 冠军卡抢实物卡接口每天10点第一个可以获得
     *
     * @JsonRpcMethod
     */
    public function HockeyCardAward() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('hockey');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['card_alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
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
            $isExist = Cache::get($key,0);
            if($isExist > 0){
                DB::rollBack();
                throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
            }else{
                Cache::forever($key,$userId);
                $luckUser = Cache::get($key);
                //查看有没有兑换实物的数据
                $goldCard = HdHockeyCard::where(['user_id'=>$luckUser,'type'=>1,"status"=>0])->first();
                if(!isset($goldCard['id'])){
                    Cache::forget($key);
                    throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
                }
                //修改为已获得实物抽奖卡状态
                $goldCard->type = 2;
                $goldCard->updated_at = date("Y-m-d H:i:s");
                $goldCard->save();
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
        throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
    }

    /***********************************竞猜活动接口********************************/

    /**
     * 竞猜信息接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessInfo($params) {
        global $userId;
        $res = [
            'is_login'=>false,
            'available'=>false,
            'stake_status'=>false,
            'team_list'=>[]
            ];
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
        }
        $config = Config::get("hockey");
        // 活动是否存在
        if(ActivityService::isExistByAlias($config['guess_alias_name'])) {
            $res['available'] = true;
        }
        //比赛日期
        $match = isset($params->match) ? $params->match : '';
        if(empty($match)){//没传日期默认第一天
            $configList = HdHockeyGuessConfig::orderBy('match_date', 'asc')->first();
        }else{//获取传送的日期
            $configList = HdHockeyGuessConfig::where('match_date', $match)->first();
        }
        $res['team_list'] = Hockey::formatHockeyGuessData($configList);
        //登陆获取用户的投注情况
        if($res['is_login'] && !empty($res['config_list']) && isset($configList['id'])){
            //登陆获取用户的投注情况
            $userStake = HdHockeyGuess::where(["user_id"=>$userId,"config_id"=>$configList['id']])->select(DB::raw("sum(`num`) as nums"),"field","config_id")->groupBy("field")->get()->toArray();
            if(!empty($userStake)){
                $stakeArr = [];
                //格式化数据
                foreach($userStake as $value){
                    $stakeArr[$value['config_id']][$value['field']] = $value['nums'];
                }
            }
        }
        //第一场个人投注
        $res['team_list']['first_stake'] = isset($stakeArr[$configList['id']]['first']) ? $stakeArr[$configList['id']]['first'] : 0;
        //第二场个人投注
        $res['team_list']['second_stake'] = isset($stakeArr[$configList['id']]['second']) ? $stakeArr[$configList['id']]['second'] : 0;
        //第三场个人投注
        $res['team_list']['third_stake'] = isset($stakeArr[$configList['id']]['third']) ? $stakeArr[$configList['id']]['third'] : 0;
        //押注状态
        if($configList['open_status'] <= 0 || date("Y-m-d H:i:s") < date("Y-m-d 21:00:00")){
            $res['stake_status'] = true;
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        ];
    }
    /**
     * 竞猜接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessDrew($params) {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //后台配置的赛程id
        $id = isset($params->id) ? $params->id : 0;
        //后台配置的场次
        $field = isset($params->field) ? $params->field : '';
        //押注
        $stake = isset($params->stake) && $params->stake > 0 ? $params->stake : 0;
        if($id <= 0 || empty($field) || $stake<= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $config = Config::get("hockey");
        DB::beginTransaction();
        //获取用户抽奖次数
        $userAttr = Attributes::getItemLock($userId,$config['guess_key']);//锁住用户抽奖次数
        $num = isset($userAttr['number']) ? $userAttr['number'] : 0;
        if($num <= 0){
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //判断是否可以下注
        $guessConfig = HdHockeyGuessConfig::where('id',$id)->first();
        if(isset($guessConfig['open_status']) && $guessConfig['open_status'] > 0 || date("Y-m-d H:i:s") >= $guessConfig['match_date']." 21:00:00"){
            DB::rollBack();
            throw new OmgException(OmgException::ACTIVITY_IS_END);
        }
        $find_name = $id."_".$field."_".$stake;
        //获取投注记录
        $stakeData = HdHockeyGuess::where(['config_id'=>$id,'user_id'=>$userId,'find_name'=>$find_name])->first();
        //判断是否存在
        if(!isset($stakeData->id)){
            //添加
            $stakeData = new HdHockeyGuess();
            $stakeData->config_id = $id;
            $stakeData->match_date = $guessConfig['match_date'];
            $stakeData->user_id = $userId;
            $stakeData->num = 1;
            $stakeData->find_name = $id."_".$field."_".$stake;
            $stakeData->type = isset($userAttr['champion_status']) && $userAttr['champion_status'] == 1 ? 2 : 1;
            $stakeData->created_at = date("Y-m-d H:i:s");
        }else{
            $stakeData->increment('num',1);
        }
        $stakeData->save();
        //减少竞猜次数
        $userAttr->number -= 1;
        $userAttr->save();
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>'下注成功'
        ];
    }
    /**
     * 竞猜榜单接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessTop()
    {
        global $userId;
        $res = [
            'total_list' => [['top'=>'--','display_name'=>'--','amount'=>'--']],
            "my_list" => []
        ];

        if($userId > 0){
            //我的竞猜
            $myList = HdHockeyGuess::where('user_id',$userId)->orderBy("match_date",'asc')->orderBy("updated_at",'asc')->get()->toArray();
            //格式化数据
            foreach($myList as $key => $val){
                $res['my_list'][$val['match_date']]['date'] = $val['match_date'];
                $res['my_list'][$val['match_date']]['total_num'] = isset($res['my_list'][$val['match_date']]['total_num']) ? $res['my_list'][$val['match_date']]['total_num'] + $val['num'] : $val['num'];
                if($val['status'] == 1 && $val['amount'] > 0){
                    $res['my_list'][$val['match_date']]['num'] += $val['num'];
                    $res['my_list'][$val['match_date']]['amount'] += $val['amount'];
                }else{
                    $res['my_list'][$val['match_date']]['num'] = '--';
                    $res['my_list'][$val['match_date']]['amount'] = '--';
                }
            }
        }
        $where['status'] = 1;
        $totalList = HdHockeyGuess::where($where)->select("match_date","user_id",DB::raw("sum(amount) as amount"),"updated_at")->groupBy("user_id")->orderBy("updated_at",'desc')->having('amount', '>', 0)->take(5)->get()->toArray();
        foreach($totalList as $k => $v){
            $res['total_list'][$k]['top'] = $k+1;
            $userInfo = Func::getUserBasicInfo($v['user_id']);
            $display_name = isset($userInfo['username']) ? substr_replace(trim($phone), '******', 3, 6) : '';
            $res['total_list'][$k]['display_name'] = $display_name;
            $res['total_list'][$k]['amount'] = isset($res['total_list'][$k]['amount']) ? $res['total_list'][$k]['amount'] + $v['amount'] : $v['amount'];
        }
        return $res;
    }

}
