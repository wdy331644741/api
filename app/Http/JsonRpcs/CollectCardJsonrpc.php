<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\ActivityJoin;
use App\Models\HdCollectCard;
use App\Models\HdCollectCardAward;
use App\Models\SendRewardLog;
use App\Models\User;
use App\Models\UserAttribute;
use App\Models\Award2;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\CollectCardAwardService;
use App\Service\CollectCardService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Service\SendMessage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\CollectCard;
use Illuminate\Support\Facades\Redis;
use Lib\JsonRpcClient;

use Config, Request, Cache,DB;

class CollectCardJsonrpc extends JsonRpc
{
    use DispatchesJobs;

    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function collectInfo() {
        global $userId;
        $result = [
                'login' => false,
                'available' => 0,
                'channel' => 0, //是否在渠道白名单
                'has' => 0, //是否已获取刘备卡片
                'timeing'=>0,//倒计时
                'num'=>0, //剩余次数
                ];
        $config = Config::get('collectcard');
        $cards = self::getInitCard();
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
            $user_info = Func::getUserBasicInfo($userId);
            if( $user_info['from_channel'] && in_array($user_info['from_channel'], $config['channel']) ) {
                $result['channel'] = 1;
            }
        }
        // 活动是否存在
        $activity = ActivityService::GetActivityedInfoByAlias($config['alias_name']);
        if(isset($activity->id) && $activity->id > 0) {
            $startTime = !empty($activity->start_at) ? strtotime($activity->start_at) : 0;
            $endTime = !empty($activity->end_at) ? strtotime($activity->end_at) : 0;
            //活动正在进行
            if(empty($startTime) && empty($endTime)){
                $result['available'] = 1;
            } else if(empty($startTime) && !empty($endTime)){
                if(time() > $endTime){
                    //活动结束
                    $result['available'] = 2;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            } else if(!empty($startTime) && empty($endTime)){
                //活动未开始
                if(time() < $startTime){
                    $result['available'] = 0;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            } else if(!empty($startTime) && !empty($endTime)){
                if(time() > $startTime){
                    //活动正在进行
                    $result['available'] = 1;
                }
                if(time() > $endTime){
                    $result['available'] = 2;
                }
            }
            $result['timeing'] = strtotime($activity->end_at);
            //老用户不能参加
            if ($result['login'] && strtotime($user_info['create_time']) < $startTime) {
                $result['channel'] = 0;
            }
        }
        if($result['available'] && $result['login'] && $result['channel']) {
            //获取卡牌
            $user_attr_text = Attributes::getJsonText($userId, $config['alias_name']);
            //1.注册就送刘备卡
            //已有刘备卡表示已送过一次了
            if (isset($user_attr_text['liubei']) && $user_attr_text['liubei'] ) {
                $result['has'] = 1;
                $cards = $user_attr_text;
                //没有刘备卡，就送一张
            }else {
                $cards['liubei'] += 1;
                if(!Attributes::setText($userId, $config['alias_name'], json_encode($cards))) {
                    $cards['liubei'] = 0;
                }
                //刘备卡代表新手红包,注册触发
                //这里只记录发送卡牌记录
//                $this->dispatch(new CollectCard($userId,$config,$config['register_award']));
            }
            //最后一张牌开启时间
            $last_card_time = strtotime("+5 day", strtotime($activity->start_at));
            $result['last_card'] = strtotime('now') > $last_card_time ? 1 : 0;
            $result['last_card_time'] = date('m-d', $last_card_time);
            //每日登陆就送1次抽卡机会
            self::addDrawCardNum($userId, $config['day_login_key']);
            //抽卡次数
            $result['num'] = Attributes::getNumber($userId, $config['drew_user_key'], 0);
        }
        $result['card'] = $cards;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 抽卡片
     *
     * @JsonRpcMethod
     */
    public function collectDrawCard($params) {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('collectcard');
        $user_info = Func::getUserBasicInfo($userId);
        if( !$user_info['from_channel'] || !in_array($user_info['from_channel'], $config['channel']) ) {
            throw new OmgException(OmgException::API_ILLEGAL);
        }
        //获取抽奖次数
        $num = isset($params->num) ? $params->num : 0;
        if($num != 1){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        // 活动是否存在
        if(!$activity = ActivityService::GetActivityInfoByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //老用户不能参加
        if (strtotime($user_info['create_time']) < strtotime($activity->start_at)) {
            throw new OmgException(OmgException::API_ILLEGAL);
        }
        try {
            DB::beginTransaction();
            $user_attr_text = Attributes::getJsonText($userId, $config['alias_name']);
            Attributes::getItemLock($userId, $config['drew_total_key']);
            $user_num = $this->getUserNum($userId,$config['drew_user_key']);
            if ( $num > $user_num) {
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
            //抽奖
            $award = $this->getAward($config['awards']);
            //减少用户抽奖次数
            $this->reduceUserNum($userId,$config,$num);
            //放入队列
            $this->dispatch(new CollectCard($userId,$config,$award));
            //格式化后返回
            if ($award['type'] != 'empty') {
                $user_attr_text[$award['card_name']] += 1;
                Attributes::setText($userId, $config['alias_name'], json_encode($user_attr_text));
                $award['card_aliasname'] = $config['card_name'][$award['card_name']];
                $award['effective_end'] = date('Y-m-d H:i:s', strtotime("+7 days"));
            }
            unset($award['type']);
            unset($award['weight']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $award,
        ];
    }
    /**
     * 滚动数据
     *
     * @JsonRpcMethod
     */
    public function collectlist() {
        $list = Cache::remember('collect_card_list', 30, function() {
            $sql = "SELECT cc.id, user_id, award_name FROM hd_collect_card cc 
                      JOIN  (SELECT MAX(id) id FROM `hd_collect_card` WHERE status = 1 AND `type` != 'empty' GROUP BY user_id) t 
                        WHERE cc.id=t.id ORDER BY t.id DESC limit 20";
            $data = DB::select($sql);
            if (!empty($data)) {
                foreach ($data as &$item) {
                    $item = get_object_vars($item);
                    if (isset($item['user_id']) && $item['user_id']) {
                        $phone = Func::getUserPhone($item['user_id']);
                        $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    }
                    unset($item['id']);
                }
            }
            return $data;
        });
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    /**
     * 我的集卡记录
     *
     * @JsonRpcMethod
     */
    public function collectMyList() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $data = HdCollectCard::select('award_name', 'card_name', 'uuid','effective_end', 'effective_start','created_at', 'alias_name')
            ->where('type', '!=', 'empty')
            ->where('user_id',$userId)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')->get()->toArray();
        $key = 'collect_card__award_list_' . $userId;
        $list = Cache::remember($key, 1, function() use(&$userId){
            
            $activity_time = ActivityService::GetActivityedInfoByAlias(Config::get('collectcard.alias_name'));
            //实名奖励
            $activity = ActivityService::GetActivityInfoByAlias('advanced_real_name');
            $where['user_id'] = $userId;
            $where['activity_id'] = $activity->id;
            $where['status'] = 1;
            $award_log = SendRewardLog::select('user_id', 'uuid', 'remark', 'award_id', 'award_type', 'created_at')->where($where)->where('created_at', '>=', $activity_time->start_at)->get()->toArray();
            $list = array();
            if ($award_log) {
                foreach ($award_log as $val) {
                    $award_info = array();
                    $remark = json_decode( $val['remark'], 1);
                    $award_info['award_name'] = $remark['award_name'];
                    $award_info['card_name'] = 'liubei';
                    $award_info['uuid'] = $val['uuid'];
                    if (isset($remark['effective_start'])) {
                        $award_info['effective_start'] = $remark['effective_start'];
                        $award_info['effective_end'] = $remark['effective_end'];
                    } else {
                        $award_info['effective_start'] = $val['created_at'];
                        $award_detail = Award2::select('effective_time_day')->where('id', $remark['award_id'])->first();
                        if (!$award_detail) {
                            continue;
                        }
                        $award_info['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $award_detail->effective_time_day . " days", strtotime($val['created_at'])));
                    }
                    $award_info['created_at'] = $val['created_at'];
                    $award_info['alias_name'] = 'advanced_real_name';
                    $award_info['type'] = 'virtual';
                    $list[] = $award_info;
                }
            }
            return $list;
        });
        if (!empty($list)) {
            $data = self::arrSort(array_merge($data, $list), 'created_at');
        }
        $valid_award = array();//未使用
        $invalid_award = array();//奖励过期或已使用
        $config = Config::get('collectcard');
        foreach ($data as &$item){
            $item['card_aliasname'] = $config['card_name'][$item['card_name']];
            $item['status'] = 1;
            unset($item['card_name']);
            $flag = false;
            if ($item['uuid']) {
                if ( false !== stripos($item['alias_name'], 'jiaxi') ) {
                    $flag = $this->couponUseStatus($item['uuid']);
                } else {
                    $flag = $this->couponUseStatus($item['uuid'], 2);
                }
            }
            unset($item['uuid']);
            //加息券是否已使用
            if( $flag ) {
                //已使用
                $item['status'] = 2;
                array_push($invalid_award, $item);
            } else {
                $date = time();
                if ( $date > strtotime($item['effective_end'])) {
                    $item['status'] = 3;
                    array_push($invalid_award, $item);
                } else {
                    array_push($valid_award, $item);
                }
            }
        }
        $ret['valid_award'] = $valid_award;
        $ret['invalid_award'] = $invalid_award;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $ret,
        ];
    }

    /**
     *  每天分享只加一次抽卡机会
     *
     * @JsonRpcMethod
     */
    public function collectCardShare() {
        global $userId;
        $result['code'] = 0;
        $result['message'] = 'success';
        $result['data']['share'] = false;
        if (empty($userId)) {
            return $result;
        }
        $config = Config::get('collectcard');
        $user_info = Func::getUserBasicInfo($userId);
        if( !$user_info['from_channel'] || !in_array($user_info['from_channel'], $config['channel']) ) {
            return $result;
        }
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            return $result;
        }
        $result['data']['share'] = self::addDrawCardNum($userId, $config['day_share_key']);
        return $result;

    }

    /**
     *  翻倍奖励
     *
     * @JsonRpcMethod
     */
    public function collectAwardDouble() {
        global $userId;
        $result = ['login'=>false, 'available'=>0, 'flag'=> false];
        $config = Config::get('collectcard');
        $flag = false;
        if (!empty($userId)) {
            $result['login']= true;
            $user_info = Func::getUserBasicInfo($userId);
            if( $user_info['from_channel'] && in_array($user_info['from_channel'], $config['channel']) ) {
                $flag = true;
            }
        }
        // 活动是否存在
        if($activity = ActivityService::GetActivityInfoByAlias($config['alias_name'])) {
            $result['available']= 1;
        }
        if( $result['login'] && $flag && $result['available']) {
            //老用户不能参加
            if (strtotime($user_info['create_time']) < strtotime($activity->start_at)) {
                return [
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result,
                ];
            }
            $this->awardDouble($userId, $config);
            $result['flag']= true;
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 抽奖页面初始状态
     *
     * @JsonRpcMethod
     */
    public function collectStatus() {
        global $userId;
        $result = [
            'login' => false,
            'available' => 0,
            'channel' => 0, //是否在渠道白名单
            'num'=>0, //剩余次数
            'total_num'=>0, //共获得次数
            'award'=>[],
        ];
        $config = Config::get('collectcard');
        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
            $user_info = Func::getUserBasicInfo($userId);
            if( $user_info['from_channel'] && in_array($user_info['from_channel'], $config['channel']) ) {
                $result['channel'] = 1;
            }
        }
        // 活动是否存在
        if($activity = ActivityService::GetActivityInfoByAlias($config['alias_name_draw'])) {
            $result['available'] = 1; //活动开始
        }
        if($result['available'] && $result['login'] && $result['channel']) {
            $result['num'] = self::getDrawNum($userId, $config);
            $draw_num = Attributes::getNumber($userId, $config['award_total_key'], 0);
            $result['total_num'] = $result['num'] + $draw_num;
            //已获得的奖品
            $where['user_id'] = $userId;
            $where['type'] = 'activity';
            $where['status'] = 1;
            $result['award'] = HdCollectCardAward::select('award_name', 'alias_name')->where($where)->get()->toArray();
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 滚动数据
     *
     * @JsonRpcMethod
     */
    public function collectDrawList() {
        $list = Cache::remember('collect_card__award_list', 30, function() {

            $sql = "SELECT cc.id, user_id, award_name FROM hd_collect_card_award cc 
                      JOIN  (SELECT MAX(id) id FROM `hd_collect_card_award` WHERE status = 1 AND `type` = 'activity' GROUP BY user_id) t 
                        WHERE cc.id=t.id ORDER BY t.id DESC limit 20";
            $data = DB::select($sql);
            if (!empty($data)) {
                foreach ($data as &$item) {
                    $item = get_object_vars($item);
                    if (isset($item['user_id']) && $item['user_id']) {
                        $phone = Func::getUserPhone($item['user_id']);
                        $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    }
                    unset($item['id']);
                    unset($item['user_id']);
                }

                //随机奖品插入
                $award = self::getBossAwardList();
                $prize['award_name'] = $award['iphone']['name'];
                $rand = rand(0, 2);
                $rand_data = [];
                for ($i=0; $i<$rand;$i++) {
                    $phone = self::getRandMobile();
                    $prize['phone'] = $phone;
                    $rand_data[] = $prize;
                }
                $data = array_merge($data, $rand_data);
            }
            return $data;
        });
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    /**
     * 最终抽奖
     *
     * @JsonRpcMethod
     */
    public function collectDraw($params) {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('collectcard');
        $user_info = Func::getUserBasicInfo($userId);
        if( !$user_info['from_channel'] || !in_array($user_info['from_channel'], $config['channel']) ) {
            throw new OmgException(OmgException::API_ILLEGAL);
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name_draw'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $num = isset($params->num) ? $params->num : 0;
        if($num != 1){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $card_num = self::getCardKindNum($userId, $config['alias_name']);
        DB::beginTransaction();
        Attributes::getItemLock($userId, $config['award_total_key']);
        $user_num = $this->getUserNum($userId,$config['award_user_key']);
        if ( $num > $user_num) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $award = $this->getBossAward($card_num, $user_num, $config);
        Attributes::increment($userId,$config['award_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config['award_user_key'],$num);
        if ($award['type'] == 'activity') {
            $award['remark'] = CollectCardAwardService::sendMessage($userId, ['awardname'=>$award['name']]);
        }
        CollectCardAwardService::sendAward($userId, $award);
        unset($award['remark']);
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $award,
        ];
    }

    //初始化卡牌数据
    private static function getInitCard() {
        $config = Config::get('collectcard');
        $cards = array();
        foreach ($config['card_name'] as $key=>$val) {
            $cards[$key] = 0;
        }
        return $cards;
    }

    //登陆或分享根据别名加次数， 一天一次
    private  function addDrawCardNum($userId, $aliasName) {
        $activity = ActivityService::GetActivityInfoByAlias($aliasName);
        $where['user_id'] = $userId;
        $where['activity_id'] = $activity['id'];
        $where['status'] = 3;
        $date = date('Y-m-d');
        $count = ActivityJoin::where($where)->whereRaw("date(created_at) = '{$date}'")->first();
        if ($count) {
            return false;
        }
        $user_lock = $userId . ":" . $aliasName;
        $is_lock = Redis::set($user_lock, 1, "nx", "ex", 5);
        if ($is_lock) {
            $config = Config::get('collectcard');
            DB::beginTransaction();
            Attributes::getItemLock($userId, $config['drew_user_key']);
            SendAward::addJoins($userId, $activity, 3);
            Attributes::increment($userId, $config['drew_user_key']);
            DB::commit();
            Redis::del($user_lock);
            return true;
        }
        return false;
//        return !SendAward::ActiveSendAward($userId, $aliasName);
    }

    //用户抽卡次数(抽卡次数不是抽奖次数)
    private function getUserNum($userId, $key) {
        $user_attr = Attributes::getItemLock($userId, $key);
        if ($user_attr && $user_attr->number > 0) {
            return $user_attr->number;
        }
        return 0;
    }

    //根据概率抽奖算法
    private function getAward($awards) {
        if (!$awards || !is_array($awards)) {
            return [];
        }
        $total_weight = array_sum(array_column($awards, 'weight'));
        $rand = rand(1, $total_weight);
        $target = 0;
        foreach ($awards as $award) {
            $target += $award['weight'];
            if ( $rand <= $target) {
                return $award;
            }
        }
    }

    //减少抽卡次数,
    // 增加总共抽奖次数
    private function reduceUserNum($userId,$config,$num){
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config['drew_user_key'],$num);
        return true;
    }

    //优惠券或红包使用状态
    private function couponUseStatus($uuid, $type=1) {
        if(!$uuid) {
            return false;
        }
        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        $params['uuid'] = $uuid;
        $params['type'] = $type;
        $result = $client->couponUseStatus($params);
        if ( !empty($result['result']) && $result['result']['status'] == 1) {//成功
            return true;
        }
        return false;
    }

    //翻倍奖励
    private  function awardDouble($userId, $config) {
        if($this->isFanbei($userId, $config)) {
//            return false;
            throw new OmgException(OmgException::MALL_IS_HAS);
        }
        $virtual_award = $this->virtualAwardDouble($userId, $config);
        $activity_award = $this->avtivityAwardDouble($userId, $config);
        return $virtual_award && $activity_award;
    }

    //是否已经翻过倍
    //卡牌翻倍
    private function isFanbei($userId, $config) {
        $where['user_id'] = $userId;
        $where['status'] = 1;
        $where['alias_name'] = $config['end_award']['alias_name'];
        $where['type'] = $config['end_award']['type'];
        $fanbei_award = HdCollectCard::select('id')->where($where)->first();
        if ( !$fanbei_award ) {
            //卡牌翻倍
            $user_attr_text = Attributes::getJsonText($userId, $config['alias_name']);
            foreach ($user_attr_text as &$val) {
                $val *= 2;
            }
            $user_attr_text[$config['end_award']['card_name']] = 1;
            Attributes::setText($userId, $config['alias_name'], json_encode($user_attr_text));
            //
           CollectCardService::sendAward($userId, $config['end_award']);
        }
        return !!$fanbei_award;
    }
    private  function avtivityAwardDouble($userId, $config) {
        $where['user_id'] = $userId;
        $where['status'] = 1;
        $where['type'] = 'activity';
        $activity_award = HdCollectCard::select('card_name', 'award_name', 'alias_name', 'type')->where($where)->get()->toArray();
        if ($activity_award) {
            foreach ($activity_award as &$val) {
                $val['name'] = $val['award_name'];
                $this->dispatch(new CollectCard($userId,$config,$val));
            }
            return true;
        }
        return false;
    }

    //实名红包翻倍
    //由于正常发奖实名限制一次，所以跳过限制发送

    private function virtualAwardDouble($userId, $config) {
//        $where['user_id'] = $userId;
//        $where['status'] = 1;
//        $where['type'] = $config['register_award']['type'];
//        $where['alias_name'] = $config['register_award']['alias_name'];
//        $virtual_award = HdCollectCard::select('id')->where($where)->first();
        //
        $activity_time = ActivityService::GetActivityInfoByAlias($config['alias_name']);
        $activity = ActivityService::GetActivityInfoByAlias('advanced_real_name');
        $where['user_id'] = $userId;
        $where['activity_id'] = $activity->id;
        $where['status'] = 1;
        $award_log = SendRewardLog::select('id')->where($where)->where('created_at', '>=', $activity_time->start_at)->first();
        //如果奖励记录表有实名奖品的记录，说明已经实名了，奖品翻倍
        if ($award_log) {
            $activity = ActivityService::GetActivityInfoByAlias($config['register_award']['alias_name']);
            //根据活动id发奖品
            SendAward::addAwardByActivity($userId, $activity->id);
//            CollectCardService::addRedByRealName($userId);
            return true;
        }
        return false;
    }

    //获取最终奖励
    //卡牌数=5， 奖品“三国礼盒”
    //卡牌数>=1，奖品“钞票枪”
    //卡牌数=0， 奖品“谢谢参与”
    private static function getBossAwardList() {
        return [
            'iphone'=>['name' => 'iPhoneX', 'alias_name' => 'iphone', 'type' => 'activity'],
            'chaopiaoqiang'=>['name' => '钞票枪', 'alias_name' => 'chaopiaoqiang', 'type' => 'activity'],
            'sanguolihe'=>['name' => '三国礼盒', 'alias_name' => 'sanguolihe', 'type' => 'activity'],
            'empty'=>['name' => '谢谢参与', 'alias_name' => 'empty', 'type' => 'empty'],
        ];
    }

    private function getBossAward($cardNum, $drawNum, $config) {
        $awards = self::getBossAwardList();
        //卡牌数>=1，奖品“钞票枪”; 随机设定20名用户。
        //先抽到钞票枪
        if ($cardNum >= 1 && $drawNum == 3) {
            $global_num = 0;
            $global_attr = GlobalAttributes::getItem($config['chaopiaoqiang_alisname']);
            if ($global_attr) {
                $global_num = intval($global_attr->number);
            }
            //超过20个人，中奖 empty
            if ($global_num >=20) {
                return $awards['empty'];
            }
            //
            $join_num = UserAttribute::where('key', '=', $config['alias_name'])->count();
            $total_num = $config['chaopiaoqiang'];
            $max = round($join_num / $total_num);
            $rand = rand(1, $max);
            if ($rand == 1) {
                GlobalAttributes::increment($config['chaopiaoqiang_alisname']);
                return $awards['chaopiaoqiang'];
            } else {
                return $awards['empty'];
            }
            //卡牌数=5， 奖品“三国礼盒”;机率为100%。
            //再抽到三国礼盒   这样 滚动记录会显示大奖
        } else if ( $cardNum == 5 && $drawNum == 2) {
            return $awards['sanguolihe'];
            //卡牌数=0， 奖品“谢谢参与”
        } else if ($cardNum >= 1 && $drawNum == 2) {
            $global_num = 0;
            $global_attr = GlobalAttributes::getItem($config['chaopiaoqiang_alisname']);
            if ($global_attr) {
                $global_num = intval($global_attr->number);
            }
            //超过20个人，中奖 empty
            if ($global_num >=20) {
                return $awards['empty'];
            }
            //
            $join_num = UserAttribute::where('key', '=', $config['alias_name'])->count();
            $total_num = $config['chaopiaoqiang'];
            $max = round($join_num / $total_num);
            $rand = rand(1, $max);
            if ($rand == 1) {
                GlobalAttributes::increment($config['chaopiaoqiang_alisname']);
                return $awards['chaopiaoqiang'];
            } else {
                return $awards['empty'];
            }
        } else {
            return $awards['empty'];
        }
    }

    //获取抽奖次数
    private static function getDrawNum($userId, $config) {
        if ($user_attr = Attributes::getItem($userId, $config['award_user_key'])) {
            return intval($user_attr->number);
        }
        //判断用户的卡牌数量
        $card_num = self::getCardKindNum($userId, $config['alias_name']);
        //剩余抽奖次数; 默认“一次”
        $number = Attributes::getNumber($userId, $config['award_user_key'], 1);
        //卡牌数 > 0 , 次数 +1
        if ($card_num > 0) {
            //抽奖次数 +1
            $number = Attributes::increment($userId, $config['award_user_key']);
        }
        //卡牌数 = 5 , 次数 +1
        if ($card_num == 5) {
            //抽奖次数 +1
            $number = Attributes::increment($userId, $config['award_user_key']);
        }
        return $number;
    }

    //获取用户拥有卡牌种类数
    private static function getCardKindNum($userId, $aliasName) {
        $card_num = 0;
        $user_attr_text = Attributes::getJsonText($userId, $aliasName);
        if (empty($user_attr_text)) {
            return $card_num;
        }
        foreach ($user_attr_text as $val) {
            if ($val > 0) {
                $card_num += 1;
            }
        }
        return $card_num;
    }

    private static $mobileSegment = [
        '134', '135', '136', '137', '138', '139', '150', '151', '152', '157', '130', '131', '132', '155', '186', '133', '153', '189',
    ];

    private static function getRandMobile()
    {
        $mobileSegment = [
            '134', '135', '136', '137', '138', '139', '150', '151', '152', '157', '130', '131', '132', '155', '186', '133', '153', '189',
        ];
        $prefix = self::$mobileSegment[array_rand(self::$mobileSegment)];
        $middle = mt_rand(2000, 9000);
        $suffix = mt_rand(2000, 9000);

        return $prefix . $middle . $suffix;
    }

    /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
    public static function arrSort($array, $field, $sort = 'SORT_DESC') {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
}

