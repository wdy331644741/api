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
use App\Service\SendMessage;
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
        $next_time = date("Y-m-d H:i:s") > $config['today_start'] ? $config['next_day_start'] : $config['today_start'];
        //判断抽奖时间是否在有效时间内
        if(date("Y-m-d H:i:s") >= $config['today_start'] && date("Y-m-d H:i:s") <= $config['today_end']){
            $next_time = 0;//1分钟内可以抽奖
        }else{
            $next_time = strtotime($next_time) - time();//倒计时
        }
        $res = [
            'is_login'=>false,//是否登录
            'available'=>false,//活动是否有效
            'is_synthesis'=>true,//是否可以合成冠军卡
            'is_cash_exchange'=>false,//是否可以兑换现金
            'is_object_exchange'=>false,//是否可以兑换实物
            'cash_exchange_num' => 0,//已兑换的现金奖品数量
            'num'=>0,//冠军卡数量
            'gold_card_num'=>0,//实物卡数量
            'cards'=>$cards,//卡片信息
            'awards'=>[],//实物奖信息
            'next_time'=>$next_time];//下次开抢实物卡时间
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
            //获取冠军卡剩余数量
            $res['gold_card_num'] = HdHockeyCard::where(["user_id"=>$userId,"type"=>1,"status"=>0])->count();
        }
        //判读是否可以合成冠军卡
        foreach($res['cards'] as $item){
            if($item <=0){
                $res['is_synthesis'] = false;
                break;
            }
        }
        //获取实物奖励信息
        $res['awards']['object'] = HdHockeyCardAward::where(['status'=>1])->get()->toArray();
        //现金奖品列表
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
        global $userId;
        $page = $params->page == 0 ? 5 : $params->page;
        $res = ['award_list'=>[],'msg_list'=>[],'my_list'=>[]];
        //获取中奖金额倒序排行
        $awardList = HdHockeyCard::where('status',1)->select("user_id","award_name","updated_at")->orderBy('updated_at', 'desc')->take($page)->get()->toArray();
        //获取投资获取卡片消息列表记录
        $msgList = HdHockeyCardMsg::where('type',1)->select('id','user_id','msg','created_at')->orderBy('created_at', 'desc')->take($page)->get()->toArray();
        if(!empty($awardList)){
            foreach ($awardList as &$item){
                if($item['user_id'] > 0){
                    $userInfo = Func::getUserBasicInfo($item['user_id']);
                    $item['display_name'] = isset($userInfo['username']) ? substr_replace(trim($userInfo['username']), '******', 3, 6) : '';
                }
                $item['updated_at'] = date("Y-m-d",strtotime($item['updated_at']));
            }
        }
        $res['award_list'] = $awardList;
        $res['msg_list'] = $msgList;
        if($userId > 0){//获取自己的获奖记录
            $userInfo = Func::getUserBasicInfo($userId);
            $display_name = isset($userInfo['username']) ? substr_replace(trim($userInfo['username']), '******', 3, 6) : '';
            $res['my_list'] = HdHockeyCard::where('user_id',$userId)->where('status',1)->select("user_id","award_name","updated_at")->orderBy('updated_at', 'desc')->take($page)->get()->toArray();
            if(!empty($res['my_list'])){
                foreach ($res['my_list'] as &$v){
                    $v['display_name'] = $display_name;
                    $v['updated_at'] = date("Y-m-d",strtotime($v['updated_at']));
                }
            }
        }
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
        //锁住该用户属性
        UserAttribute::where(['user_id'=>$userId,'key'=>$config['card_key']])->lockForUpdate()->first();
        if($type == 1){//兑换现金
            //锁住用户未使用的冠军卡
            $cashCard = HdHockeyCard::where(['user_id'=>$userId,'type'=>1,'status'=>0])->lockForUpdate()->first();
            if(!isset($cashCard['type'])){
                DB::rollBack();
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
            //获取应得奖励（应该获取的现金奖励）
            $cashName = Hockey::getHockeyCardExchangeAward($userId);
            if($cashName > 0){
                //发送现金奖励
                $uuid = Func::create_guid();
                $res = Func::incrementAvailable($userId,$cashCard['id'],$uuid,intval($cashName),'hockey_card');
                if (!isset($res['result']['code'])) {
                    DB::rollBack();
                    throw new OmgException(OmgException::API_FAILED);
                }
                $cashCard->status = 1;//修改冠军卡为已使用
                $cashCard->award_id = intval($cashName);
                $cashCard->award_name = $cashName;
                $cashCard->save();
                DB::commit();
                $msg = "亲爱的用户，恭喜您在助力女曲-集卡场活动中成功兑换".$cashName."，现金奖励已发放至您的网利宝账户，敬请查收。客服电话：400-858-8066。";
                SendMessage::Mail($userId,$msg);//发送站内信
                SendMessage::Message($userId,$msg);//发送短信
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
        //获取实物卡信息（锁住）
        $goldCard = HdHockeyCard::where(['user_id'=>$userId,'type'=>2,'status'=>0])->lockForUpdate()->first();
        if(!isset($goldCard['type'])){
            DB::rollBack();
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //兑换实物
        $goldCard->status = 1;//修改实物卡为已使用
        $goldCard->award_id = $awardId;
        $goldCard->award_name = isset($award['award_name']) ? $award['award_name'] : '';
        $goldCard->save();
        DB::commit();
        $msg = "亲爱的用户，恭喜您在助力女曲-集卡场活动中成功兑换".$goldCard->award_name."，实物奖品将在活动结束后15个工作日内发放。温馨提示：请确保您在网利宝平台的收货地址准确无误，请到“网利宝APP—账户—个人信息”完善收货地址，客服电话：400-858-8066。";
        SendMessage::Mail($userId,$msg);//发送站内信
        SendMessage::Message($userId,$msg);//发送短信
        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$award['award_name']
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
        if(date("Y-m-d H:i:s") >= $config['today_start'] && date("Y-m-d H:i:s") <= $config['today_end']){//判断时间是否可以抢实物卡
            DB::beginTransaction();
            $luckAwardKey = "hockey_card_luck_award_key";//redis key
            $lockAwardCount = GlobalAttribute::where('key',$luckAwardKey)->count();
            if($lockAwardCount < 1){//全局属性是否存在
                GlobalAttribute::create(['key'=>$luckAwardKey,'number'=>0]);
            }
            GlobalAttribute::where('key',$luckAwardKey)->lockForUpdate()->first();//锁住属性记录
            $isExist = Cache::get($key,0);//判断rediskey是否存在
            if($isExist > 0){//如果存在说明有人中奖
                DB::rollBack();
                throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
            }else{//不存在就记录第一个用户id
                Cache::forever($key,$userId);
                $luckUser = Cache::get($key);
                //查看有没有冠军卡
                $goldCard = HdHockeyCard::where(['user_id'=>$luckUser,'type'=>1,"status"=>0])->first();
                if(!isset($goldCard['id'])){//没有冠军卡直接返回错误代码
                    DB::rollBack();
                    throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
                }
                //修改为已获得实物抽奖卡状态
                $goldCard->type = 2;//修改为可兑换实物类型（实物卡）
                $goldCard->updated_at = date("Y-m-d H:i:s");
                $goldCard->save();
                //消息内容
                $msg = "亲爱的用户，恭喜您在助力女曲-集卡场活动中抽中实物奖品兑换卡，请到活动页面兑换实物奖品，实物奖品将在活动结束后15个工作日内发放。温馨提示：请确保您在网利宝平台的收货地址准确无误，请到“网利宝APP—账户—个人信息”完善收货地址，客服电话：400-858-8066。";
                SendMessage::Mail($luckUser,$msg);//发送站内信
                SendMessage::Message($luckUser,$msg);//发送短信
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
        throw new OmgException(OmgException::TODAY_ACTIVITY_IS_END);
    }

    /***********************************竞猜活动接口********************************/

    /**
     * 竞猜信息接口
     *
     * @JsonRpcMethod
     */
    public function HockeyGuessInfo($params) {
        global $userId;
        $config = Config::get("hockey");
        $res = [
            'is_login'=>false,//是否登录
            'available'=>false,//获取是否存在
            'stake_status'=>1,//押注状态1竞猜结束、2竞猜中，3竞猜未开始
            'time_end'=>strtotime($config['expire_time']) - time(),//活动总体押注过期时间
            'next_time_end'=>0,//当天押注过期时间
            'next_date'=>17,
            'champion_user_count'=>0,
            'user_count'=>0,
            'team'=>$config['guess_team'],//国家队信息
            'team_list'=>[],//国家队对阵信息及押注情况
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
        $dateData = Hockey::getMatchDate();
        $match = isset($params->match) && !empty($params->match) ? $params->match : $dateData['match_date'];
        $res['next_time_end'] = $dateData['next_time'];
        $res['next_date'] = $dateData['next_date'];
        if(empty($match)){//没传日期默认第一天
            $configList = HdHockeyGuessConfig::orderBy('match_date', 'asc')->first();
        }else{//获取传送的日期
            $configList = HdHockeyGuessConfig::where('match_date', $match)->first();
        }
        $res['team_list'] = Hockey::formatHockeyGuessData($configList);
        if(isset($res['team_list']['id']) && $res['team_list']['id'] > 0){
            //第一场参与次数
            $firstUserCount = HdHockeyGuess::where("type",1)->where("find_name","like",$res['team_list']['id']."_first%")->select(DB::raw("sum(num) as count"))->first();
            $res['team_list']['first_user_count'] = isset($firstUserCount['count']) ? $firstUserCount['count'] : 0;
            //第二场参与次数
            $secondUserCount = HdHockeyGuess::where("type",1)->where("find_name","like",$res['team_list']['id']."_second%")->select(DB::raw("sum(num) as count"))->first();
            $res['team_list']['second_user_count'] = isset($secondUserCount['count']) ? $secondUserCount['count'] : 0;
            //第三场参与次数
            $thirdUserCount = HdHockeyGuess::where("type",1)->where("find_name","like",$res['team_list']['id']."_third%")->select(DB::raw("sum(num) as count"))->first();
            $res['team_list']['third_user_count'] = isset($thirdUserCount['count']) ? $thirdUserCount['count'] : 0;
            //总共参与的人数
            $thirdUserCount = HdHockeyGuess::where("type",1)->where("config_id",$res['team_list']['id'])->select(DB::raw("COUNT(DISTINCT user_id) as count"))->first();
            $res['team_list']['total_user_count'] = isset($thirdUserCount['count']) ? $thirdUserCount['count'] : 0;
            unset($res['team_list']['msg_status']);
            unset($res['team_list']['champion_status']);
            unset($res['team_list']['draw_info']);
            unset($res['team_list']['remark']);
        }
        //登陆获取用户的投注情况
        if($res['is_login']){
            if(isset($configList['id'])){
                //登陆获取用户的投注情况
                $userStake = HdHockeyGuess::where(["user_id"=>$userId,"config_id"=>$configList['id'],"type"=>1])->select(DB::raw("sum(`num`) as nums"),"find_name","config_id")->groupBy("find_name")->get()->toArray();
                if(!empty($userStake)){
                    $stakeArr = ['first'=>[1=>0, 2=>0, 3=>0],'second'=>[1=>0, 2 =>0, 3=>0],'third'=>[1=>0, 2=>0, 3=>0]];
                    //格式化数据
                    foreach($userStake as $value){
                        if(isset($value['find_name']) && !empty($value['find_name'])){
                            $tmp = explode("_",$value['find_name']);
                            if(!empty($tmp)){
                                $stakeArr[$tmp[1]][$tmp[2]] = $value['nums'];
                            }
                        }
                    }
                }
            }
            //获取冠军场押注情况
            $userChampionStake = HdHockeyGuess::where(["user_id"=>$userId,"type"=>2])->select(DB::raw("sum(`num`) as nums"),"config_id")->groupBy("find_name")->get()->toArray();
            $championStake = [];
            foreach($userChampionStake as &$item){
                $championStake[$item['config_id']] = $item;
            }
            //用户抽奖次数
            $res['user_count'] = Attributes::getNumber($userId,$config['guess_key']);
        }
        //普通场押注状态判断是否为竞猜中
        if($configList['open_status'] <= 0 && date("Y-m-d H:i:s") < $configList['match_date']." 14:00:00" && date("d",strtotime($configList['match_date'])) <= $res['next_date']){
            $res['stake_status'] = 2;
        }
        //普通场押注状态判断是否为未开始
        if($configList['open_status'] <= 0 && date("d",strtotime($configList['match_date'])) > $res['next_date']){
            $res['stake_status'] = 3;
        }
        //第一场个人投注
        $res['team_list']['first_stake'] = isset($stakeArr['first']) ? $stakeArr['first'] : [];
        //第二场个人投注
        $res['team_list']['second_stake'] = isset($stakeArr['second']) ? $stakeArr['second'] : [];
        //第三场个人投注
        $res['team_list']['third_stake'] = isset($stakeArr['third']) ? $stakeArr['third'] : [];
        //冠军场押注
        foreach($res['team'] as $key => $value){
            $tmpData['num'] = isset($championStake[$key]['nums']) ? $championStake[$key]['nums'] : 0;
            $tmpData['country'] = $value;
            $res['team'][$key] = $tmpData;
        }
        //冠军场参与人数
        $championUserCount = HdHockeyGuess::where("type",2)->select(DB::raw("COUNT(DISTINCT user_id) as count"))->first();
        $res['champion_user_count'] = isset($championUserCount['count']) ? $championUserCount['count'] : 0;
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
        if($id <= 0 || empty($field) || $stake<= 0 || !in_array($field,['first','second','third','champion'])){//判断必要参数
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $config = Config::get("hockey");
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['guess_alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        DB::beginTransaction();
        //获取用户抽奖次数
        $userAttr = Attributes::getItemLock($userId,$config['guess_key']);//锁住用户抽奖次数
        $num = isset($userAttr['number']) ? $userAttr['number'] : 0;
        if($field == 'champion'){//冠军场下注
            if($num < 3){
                DB::rollBack();
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
            //判断冠军场是否超过下注时间
            if(date("Y-m-d H:i:s") >= $config["expire_time"]){
                DB::rollBack();
                throw new OmgException(OmgException::ACTIVITY_IS_END);
            }
            $find_name = $id."_".$field;
            $guessConfig = HdHockeyGuessConfig::where('champion_status',1)->first();
        }else{//普通对阵下注
            if($num <= 0){
                DB::rollBack();
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
            $find_name = $id."_".$field."_".$stake;
            $guessConfig = HdHockeyGuessConfig::where('id',$id)->first();
            //判断普通场是否超过下注时间
            if(date("Y-m-d H:i:s") >= $guessConfig['match_date']." 14:00:00"){
                DB::rollBack();
                throw new OmgException(OmgException::ACTIVITY_IS_END);
            }
        }
        //根据开奖状态是否可以下注
        if(isset($guessConfig['open_status']) && $guessConfig['open_status'] > 0){
            DB::rollBack();
            throw new OmgException(OmgException::ACTIVITY_IS_END);
        }
        //获取投注记录
        $stakeData = HdHockeyGuess::where(['config_id'=>$id,'user_id'=>$userId,'find_name'=>$find_name])->first();
        //判断是否存在
        if(!isset($stakeData->id)){
            //添加
            $stakeData = new HdHockeyGuess();
            $stakeData->config_id = $id;
            $stakeData->match_date = $field == 'champion' ? "2018-11-25" : $guessConfig['match_date'];
            $stakeData->user_id = $userId;
            $stakeData->num = 1;
            $stakeData->find_name = $find_name;
            $stakeData->type = $field == 'champion' ? 2 : 1;
            $stakeData->created_at = date("Y-m-d H:i:s");
        }else{
            $stakeData->increment('num',1);//注数+1
        }
        $stakeData->save();
        //减少竞猜次数(冠军场竞猜减少3次竞猜机会)
        $userAttr->number -= $field == 'champion' ? 3 : 1;
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
            'total_list' => [['top'=>'--','display_name'=>'--','amount'=>'--']],//竞猜获取现金列表
            "my_list" => []//我的竞猜信息列表
        ];

        if($userId > 0){
            //我的竞猜
            $myList = HdHockeyGuess::where('user_id',$userId)->orderBy("match_date",'asc')->orderBy("updated_at",'asc')->get()->toArray();
            //格式化数据
            foreach($myList as $key => $val){
                $res['my_list'][$val['match_date']]['date'] = $val['match_date'];
                $res['my_list'][$val['match_date']]['total_num'] = isset($res['my_list'][$val['match_date']]['total_num']) ? $res['my_list'][$val['match_date']]['total_num'] + $val['num'] : $val['num'];
                if($val['status'] == 1 && $val['amount'] > 0){
                    if(isset($res['my_list'][$val['match_date']]['num'])){
                        $res['my_list'][$val['match_date']]['num'] += $val['num'];
                    }else{
                        $res['my_list'][$val['match_date']]['num'] = $val['num'];
                    }
                    if(isset($res['my_list'][$val['match_date']]['amount'])) {
                        $res['my_list'][$val['match_date']]['amount'] += $val['amount'];
                    }else{
                        $res['my_list'][$val['match_date']]['amount'] = $val['amount'];
                    }
                }else{
                    $res['my_list'][$val['match_date']]['num'] = '--';
                    $res['my_list'][$val['match_date']]['amount'] = '--';
                }
            }
        }
        $where['status'] = 1;
        $totalList = HdHockeyGuess::where($where)->select("match_date","user_id",DB::raw("sum(amount) as amount"),"updated_at")->groupBy("user_id")->orderBy("amount",'desc')->having('amount', '>', 0)->take(5)->get()->toArray();
        foreach($totalList as $k => $v){
            $res['total_list'][$k]['top'] = $k+1;
            $userInfo = Func::getUserBasicInfo($v['user_id']);
            $display_name = isset($userInfo['username']) ? substr_replace(trim($userInfo['username']), '******', 3, 6) : '';
            $res['total_list'][$k]['display_name'] = $display_name;
            $res['total_list'][$k]['amount'] = isset($res['total_list'][$k]['amount']) ? $res['total_list'][$k]['amount'] + $v['amount'] : $v['amount'];
        }
        return $res;
    }

}
