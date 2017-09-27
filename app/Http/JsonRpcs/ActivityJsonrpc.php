<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\AwardCash;
use App\Models\SendRewardLog;
use App\Models\User;
use App\Service\SendAward;
use App\Service\Attributes;
use App\Service\Func;
use App\Service\SignIn;
use App\Models\UserAttribute;
use Lib\JsonRpcClient;
use Lib\HouseOwnership;
use Validator, Config;

use App\Models\Award;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Coupon;
use App\Models\DataBlackWord;
use Cache;
use App\Service\ActivityService;
use Lib\McQueue;
use App\Service\GlobalAttributes;
use App\Service\SignInSystemBasic;
class ActivityJsonRpc extends JsonRpc {


    /**
     * 房产证生成
     *
     * @JsonRpcMethod
     */
    public function createImg($params) {
        if(empty($params->city) || empty($params->realname) || empty($params->address) || empty($params->area)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $params = (array)$params;
        $city = I('data.city/s', '北京', 'trim', $params);
        $realname = I('data.realname/s', '小明', 'trim', $params);
        $address = I('data.address/s', '朝阳区三元桥海南航空大厦A座7层', 'trim', $params);
        $area = I('data.area/f', '180.00', 'trim', $params);

        if (mb_strlen($city) > 5)
            throw new OmgException(OmgException::CITY_IS_TOO_LONG);

        if (mb_strlen($realname) > 20)
            throw new OmgException(OmgException::REALNAME_IS_TOO_LONG);

        if (mb_strlen($address) > 30)
            throw new OmgException(OmgException::ADDRESS_IS_TOO_LONG);

        if ($area < 0 || $area > 10000)
            throw new OmgException(OmgException::AREA_IS_TOO_BIG);

        $nums = DataBlackWord::whereRaw("locate(word,\"{$params['realname']}\")")->count();
        if ($nums)
            throw new OmgException(OmgException::NAME_IS_ALIVE);
        $nums = DataBlackWord::whereRaw("locate(word,\"{$params['address']}\")")->count();
        if ($nums)
            throw new OmgException(OmgException::ADDRESS_IS_ALIVE);

        $houseOwnTool = new HouseOwnership();
        $cityTextPos = $houseOwnTool::CITY_TEXT_POS;
        $cityTextPos['x'] = $cityTextPos['x'] - (mb_strlen($city) - 5) * 20;
        $houseOwnTool->writeText($city, $cityTextPos, 16);
        $houseOwnTool->writeText($realname, $houseOwnTool::NAME_TEXT_POS);
        $houseOwnTool->writeText($address, $houseOwnTool::ADDRESS_TEXT_POS);
        $houseOwnTool->writeText(date("Y年m月d日"), $houseOwnTool::RECORDTIME_TEXT_POS);
        $houseOwnTool->writeText($area, $houseOwnTool::AREA_TEXT_POS);
        $houseOwnTool->writeText(round(($area * 0.81), 2), $houseOwnTool::TRUEAREA_TEXT_POS);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $houseOwnTool->getBase64Png()
        ];
    }
    /**
     * 领取分享奖励
     *
     * @JsonRpcMethod
     */
    public function signinShare() {
        global $userId;
        $signInName = 'signin';
        $sharedName = 'signinShared';
        $isShared = false;
        $awardName = '';
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        // 未签到
        $signIn = Attributes::getItem($userId, $signInName);
        if(!$signIn) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }
        $lastUpdate = $signIn['updated_at'] ? $signIn['updated_at'] : $signIn['created_at'];
        $lastUpdateDate = date('Y-m-d', strtotime($lastUpdate));
        if($lastUpdateDate !== date('Y-m-d', time())) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        // 已分享
        $shared = Attributes::getItem($userId, $sharedName);
        if($shared){
            $sharedUpdate = $shared['updated_at'] ? $shared['updated_at'] : $shared['created_at'];
            $sharedUpdateDate = date('Y-m-d', strtotime($sharedUpdate));

            if($sharedUpdateDate == date('Y-m-d', time())) {
                $isShared = true;
                $awardName = $shared['string'];
            }
        }

        // 未分享
        if(!$isShared) {
            $awards = json_decode($signIn['text'], true);
            $sharedActivity = Activity::where('alias_name', $sharedName)->first();
            // 发奖
            $award = SendAward::sendDataRole($userId, $awards[0]['award_type'], $awards[0]['award_id'], $sharedActivity['id']);
            $awardName = $award['award_name'];
            Attributes::setItem($userId, $sharedName, time(), $awardName, json_encode($awards));
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isShared' => $isShared,
                'award' => [$awardName],
            ),
        );

    }

    /**
     * 圣诞节活动获取闯关状态
     *
     * @JsonRpcMethod
     */
    public function getCgStatus(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where('user_id',$userId)->whereIn('key',['sd_tree_status','invite_investment_first','invite_investment','year_investment_50000','signin','year_investment_100000'])->get();
        $data = [
            'sd_tree_status'=>0,
            'invite_investment_first'=>0,
            'invite_investment'=>0,
            'year_investment_50000'=>0,
            'signin'=>0,
            'year_investment_100000'=>0
        ];
        foreach ($res as $val){
            switch ($val->key) {
                case 'sd_tree_status':
                    $data['sd_tree_status'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'invite_investment_first':
                    $data['invite_investment_first'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'invite_investment':
                    $data['invite_investment'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'year_investment_50000':
                    $data['year_investment_50000'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'signin':
                    $start_at = Activity::where('alias_name','continue_signin_three')->value('start_at');
                    $data['signin'] = SignIn::getSignInNum($userId,date('Y-m-d',strtotime($start_at)));
                    break;
                case 'year_investment_100000':
                    $data['year_investment_100000'] = isset($val->number) ? $val->number : 0;
                    break;
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' =>$data
        );
    }

    /**
     * 圣诞节活动点我种树
     *
     * @JsonRpcMethod
     */
    public function setSdStatus(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $treeStatus = UserAttribute::where(['user_id'=>$userId,'key'=>'sd_tree_status'])->value('number');
        if(!$treeStatus){
            $res = Attributes::increment($userId,'sd_tree_status');
            if(!$res){
                throw new OmgException(OmgException::API_FAILED);
            }
        }
        $status = UserAttribute::where('user_id',$userId)->whereIn('key',['invite_investment_first','invite_investment','year_investment_50000','signin','year_investment_100000'])->get();
        foreach ($status as $val){
            switch ($val->key) {
                case 'invite_investment_first':
                    if(isset($val->number) && $val->number >=3){
                        SendAward::ActiveSendAward($userId,'invite_investment_first_send');
                    }
                    break;
                case 'invite_investment':
                    if(isset($val->number) && $val->number >=2){
                        SendAward::ActiveSendAward($userId,'invite_investment_send');
                    }
                    break;
                case 'year_investment_50000':
                    if(isset($val->number) && $val->number >=50000){
                        SendAward::ActiveSendAward($userId,'year_investment_50000_send');
                    }
                case 'signin':
                    $start_at = Activity::where('alias_name','continue_signin_three')->value('start_at');
                    $signDay = SignIn::getSignInNum($userId,date('Y-m-d',strtotime($start_at)));
                    if($signDay >= 3){
                        SendAward::ActiveSendAward($userId,'continue_signin_three');
                    }
                    break;
                case 'year_investment_100000':
                    if(isset($val->number) && $val->number >=100000){
                        SendAward::ActiveSendAward($userId,'year_investment_100000_send');
                    }
                    break;
            }

        }

    }
    /**
     * 领取连续签到奖励
     *
     * @JsonRpcMethod
     */
    public function signinDay($params) {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make((array)$params, [
            'day' => 'required|integer',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $day = $params->day;
        $aliasName = "signinDay_{$day}";
        $signInName = 'signin';
        $isAward = false;
        $awardName = '';

        $signIn = Attributes::getItem($userId, $signInName);
        if(!$signIn) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }
        $lastUpdate = $signIn['updated_at'] ? $signIn['updated_at'] : $signIn['created_at'];
        $lastUpdateDate = date('Y-m-d', strtotime($lastUpdate));
        if($lastUpdateDate !== date('Y-m-d', time())) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        $signInNum = $signIn['number'];
        $signInNum = $signInNum%28 == 0  ? 28 : $signInNum%28;

        if($signInNum !== $day) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $extra = Attributes::getItem($userId, $aliasName);
        if($extra) {
            $extraLastUpdate = $extra['updated_at'] ? $extra['updated_at'] : $extra['created_at'];
            $extraLastUpdateDate = date('Y-m-d', strtotime($extraLastUpdate));
            if($extraLastUpdateDate == date('Y-m-d', time())) {
                $isAward = true;
                $awardName = $extra['string'];
                $awardType = 0;
            }
        }

        if(!$isAward) {
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
            $awardName = $awards[0]['award_name'];
            $awardType = $awards[0]['award_type'];
            Attributes::setItem($userId, $aliasName, time(), $awardName, json_encode($awards));
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isAward' => $isAward,
                'awards' => [$awardName],
                'type' => $awardType,
            ),
        );

    }

    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin() {
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        return $this->innerSignin($userId);
    }

    // 内部签到
    public function innerSignin($userId) {
        $aliasName = 'signin';
        $days = array(7, 14, 21, 28); //额外奖励
        $last = $days[count($days)-1]; //最后的天数
        $isSignIn = false; //是否签到过
        $continue = 1; //连续签到天数
        $award = [
            'name' => '',
            'type' => 0,
        ];

        //事务开始
        DB::beginTransaction();
        $activity = Activity::where('alias_name', $aliasName)->with('rules')->with('awards')->lockForUpdate()->first();
        if(!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $signIn = Attributes::getItem($userId, $aliasName);

        //签到过
        if($signIn) {
            $lastUpdate = $signIn['updated_at'] ? $signIn['updated_at'] : $signIn['created_at'];
            $lastUpdateDate = date('Y-m-d', strtotime($lastUpdate));

            // 今天已签到
            if($lastUpdateDate == date('Y-m-d', time())) {
                $isSignIn = true;
                $continue = $signIn['number'] ?  $signIn['number'] : 0;
                $award['name'] = $signIn['string'];
            }

            // 昨天已签到
            if($lastUpdateDate == date('Y-m-d', time() - 3600*24)) {
                $award = $this->signSendAward($userId);
                $continue = Attributes::increment($userId, $aliasName, 1, $award['name'], json_encode($award));
            }
        }

        //未签到或非连续签到
        if(empty($award['name'])) {
            $continue = 1;
            $award = $this->signSendAward($userId);
            Attributes::setItem($userId, $aliasName, $continue, $award['name'], json_encode($award));
        }

        // 送积分 & 发消息
        if(!$isSignIn) {
            if($continue >=7 )  {
                SendAward::ActiveSendAward($userId, 'signin_point7'); // 连续签到7天送2积分
            } else {
                SendAward::ActiveSendAward($userId, 'signin_point'); // 签到送1积分
            }
            $mcQueue = new McQueue();
            $mcQueue->put('daylySignin', array('user_id' => $userId, 'days' => $continue));
        }

        // 额外奖励进度
        $current = $continue%$last == 0 ? $last : $continue%$last;
        foreach($days as $key => $day) {
            if($current <= $day){
                if($key == 0) {
                    $start = 1;
                }else{
                    $start = $days[$key-1] + 1;
                }
                $end = $day;
                break;
            }
        }

        // 是否领取额外奖励
        $extra = $this->isExtraAwards($userId, $end);
        // 是否分享
        $shared = $this->isShared($userId);
        DB::commit();

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isSignin' => $isSignIn,
                'current' => $current,
                'start' => $start,
                'end' => $end,
                'extra' => $extra,
                'shared' => $shared,
                'award' => [$award['name']],
                'type' => $award['type'],
                'last' => $last,
            ),
        );
    }

    //签到发奖
    private function signSendAward($userId) {
        $aliasName = 'signin';
        $interval = strtotime(date('Y-m-d 20:00:00')) - time();
        $rand = rand(1, 2);

        $award = [
            'name' => '谢谢参与',
            'type' => 0,
        ];
        if($interval < 0 || $rand === 1) {
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
            if(!isset($awards[0]['award_name'])) {
                throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
            }
            $award['name'] = $awards[0]['award_name'];
            $award['type'] = $awards[0]['award_type'];
            return $award;
        }

        $multiple = rand(1,2)/10;
        SignInSystemBasic::signInEveryDayMultiple($userId, $multiple);
        $award['name'] = "${multiple}倍摇一摇翻倍特权";
        $award['type'] = 8;
        return $award;
    }

    // 获取额外奖励领取记录
    private function isExtraAwards($userId, $end) {
        $aliasName = "signinDay_{$end}";
        $extra = Attributes::getItem($userId, $aliasName);

        $lastUpdate = $extra['updated_at'] ? $extra['updated_at'] : $extra['created_at'];
        $lastUpdateDate = date('Y-m-d', strtotime($lastUpdate));

        // 今天领奖了
        if($lastUpdateDate == date('Y-m-d', time())) {
            return true;
        }

        return false;
    }

    // 今天是否分享
    private function isShared($userId) {
        $sharedName = 'signinShared';

        $shared = Attributes::getItem($userId, $sharedName);
        // 未分享记录
        if(!$shared) {
            return false;
        }

        //已分享
        $sharedUpdate = $shared['updated_at'] ? $shared['updated_at'] : $shared['created_at'];
        $sharedUpdateDate = date('Y-m-d', strtotime($sharedUpdate));
        if($sharedUpdateDate == date('Y-m-d', time())) {
            return true;
        }

        return false;
    }


    /**
     * 获取闯关状态
     *
     * @JsonRpcMethod
     */
    public function getStatus($params) {
        global $userId;
        if (empty($params->key)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $json = UserAttribute::where(['user_id'=>$userId,'key'=>$params->key])->value('text');
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => json_decode($json)
        );
    }

    /**
     * 根据活动别名获取奖品信息
     *
     * @JsonRpcMethod
     */
    function aliasNameToAwardInfo($params){
        $where['alias_name'] = trim($params->aliasName);
        if(empty($where['alias_name'])){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $where['enable'] = 1;
        //获取活动信息
        $activity = Activity::where($where)->first();
        if(empty($activity)){
            throw new OmgException(OmgException::NO_DATA);
        }
        //获取奖品id
        $activityID = isset($activity['id']) ? $activity['id'] : 0;
        if(empty($activityID)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $awardsList = Award::where('activity_id',$activityID)->get()->toArray();
        if(empty($awardsList)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $awardList = array();
        foreach($awardsList as $item){
            //获取奖品信息
            $table = $this->_getAwardTable($item['award_type']);
            $awardInfo = $table::where('id',$item['award_id'])->get()->toArray();
            if(!empty($awardInfo)){
                foreach($awardInfo as $value){
                    $awardList[] = $value;
                }
            }

        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $awardList
        );
    }
    /**
     * 根据活动别名获取中奖纪录
     *
     * @JsonRpcMethod
     */
    function aliasNameToRewardList(){
        $where['enable'] = 1;
        //获取活动信息
        $aliasNameKey = array(Config::get("activity.double_eleven.key1"),Config::get("activity.double_eleven.key2"),Config::get("activity.double_eleven.key3"));
        $activity = Activity::where($where)->whereIn('alias_name',$aliasNameKey)->select('id')->get()->toArray();
        if(empty($activity)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $activityIDs = array();
        foreach($activity as $id){
            $activityIDs[] = $id['id'];
        }
        if(empty($activityIDs)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $list = SendRewardLog::whereIn('activity_id',$activityIDs)->where('status','>=',1)->select('user_id','award_type','award_id')->orderBy('id', 'desc')->take(3)->get()->toArray();
        if(empty($list)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $awardList = array();
        $i = 0;
        foreach($list as $item){
            //根据用户ID获取手机号
            $url = env('INSIDE_HTTP_URL');
            $client = new JsonRpcClient($url);
            $userBase = $client->userBasicInfo(array('userId'=>$item['user_id']));
            $phone = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : '';
            if(empty($phone)){
                throw new OmgException(OmgException::API_FAILED);
            }
            $phone = substr_replace($phone, '*****', 3, 5);
            //获取奖品信息
            $table = $this->_getAwardTable($item['award_type']);
            $awardInfo = $table::where('id',$item['award_id'])->select('name')->first();
            if(empty($awardInfo)){
                throw new OmgException(OmgException::NO_DATA);
            }
            $awardList[$i]['phone'] = $phone;
            $awardList[$i]['name'] = $awardInfo['name'];
            $i++;
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $awardList
        );
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Coupon|bool
     */
    function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 7) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award3;
            } elseif ($awardType == 4) {
                return new Award4;
            } elseif ($awardType == 5) {
                return new Award5;
            } elseif ($awardType == 6){
                return new Coupon;
            } elseif ($awardType == 7){
                return new AwardCash;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


    //---------------------------新春嘉年华-------------------------//

    /**
     * 获取活动金牌投手top排行
     *
     * @JsonRpcMethod
     */
    public function getNyToushouTop(){
        $res = UserAttribute::where('key','new_year_bidding')->orderBy('number','desc')->orderBy('updated_at','ASC')->paginate(5);
        $response = array();
        if(isset($res)){
            $i = 1;
            foreach ($res as $key=>$val){
                if(empty($val->user_id)){
                    continue;
                }
                $item['top'] = $i;
                $item['number'] = $val->number;
                $phone = Func::getUserPhone($val->user_id);
                $item['display_name'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                $item['user_id'] = $val->user_id;
                $response[] = $item;
                $i++;
            }
        }else{
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array()
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $response
        );
    }


    /**
     * 获取活动当前用number排行
     *
     * @JsonRpcMethod
     */
    public function getNyUserNumber($params){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NOT_NULL);

        }
        switch ($params->key){
            case 'new_year_bidding':
                $number = UserAttribute::where(['key'=>$params->key,'user_id'=>$userId])->value('number');
                break;
            case 'new_year_invite_investment':
                $number = UserAttribute::where(['key'=>$params->key,'user_id'=>$userId])->value('text');
                break;
            case 'new_year_hammer_num':
                $number = UserAttribute::where(['key'=>$params->key,'user_id'=>$userId])->value('number');
                break;
            case 'new_year_year_investment':
                $number = UserAttribute::where(['key'=>$params->key,'user_id'=>$userId])->value('number');
                break;
        }
        if(!$number){
            $number = 0;
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $number
        );
    }


    /**
     * 新春活动砸金蛋
     *
     * @JsonRpcMethod
     */
    public function nyZaJinDan($params){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if(empty($params->isget)){
            throw new OmgException(OmgException::PARAMS_NOT_NULL);
        }
        $number = UserAttribute::where(['user_id'=>$userId,'key'=>'new_year_hammer_num'])->value('number');
        if(!$number){
            $number = 0;
        }
        if($params->isget == 1){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $number
            );
        }
        if(isset($number) && $number > 0){
            $res = SendAward::ActiveSendAward($userId,'new_year_hammer_eggs');
            if(isset($res[0]['status']) && $res[0]['status'] === true){
                $awardName = $res[0]['award_name'];
                $deNum = Attributes::decrement($userId,'new_year_hammer_num');
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => array('num'=>$deNum,'award_name'=>$awardName)
                );
            }
        }
        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }


    /**
     * 新春活动砸金蛋最新获取列表
     *
     * @JsonRpcMethod
     */
    public function nyZaJinDanList($params){
        $per_page = intval($params->per_page);
        if(empty($per_page)){
            throw new OmgException(OmgException::PARAMS_NOT_NULL);
        }
        //判断缓存中是否有数据
        if(Cache::has('NewYearHammerList')){
            $json = Cache::get("NewYearHammerList");
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($json,1)
            );
        }
        $data = array();
        //根据别名获取活动id
        $activityInfo = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where('alias_name','new_year_hammer_eggs')->select("id","join_num")->first();
        if(empty($activityInfo)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data
            );
        }
        $data = SendRewardLog::where("activity_id",$activityInfo['id'])->where("status",">=",1)->select("user_id","remark","created_at")->take($per_page)->orderBy("id","desc")->get();
        if(!empty($data)){
            foreach($data as &$item){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                $awardList = json_decode($item['remark'],1);
                $item['award_name'] = isset($awardList['award_name']) ? $awardList['award_name'] : '';
            }
        }
        $return = array();
        $return['total_num'] = isset($activityInfo['join_num']) && !empty($activityInfo['join_num']) ? $activityInfo['join_num'] : 0;
        $return['data'] = $data;
        Cache::put("NewYearHammerList",json_encode($return),5);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $return
        );
    }

    /**
     * 获取推广贡献奖top排行
     *
     * @JsonRpcMethod
     */
    public function getNyExtensionTop(){
        $res = UserAttribute::where('key','new_year_invite_investment')->orderByRaw('text * 1 desc')->orderBy('updated_at','ASC')->paginate(5);
        $response = array();
        if(isset($res) && !empty($res)){
            $i = 1;
            foreach ($res as $key=>$val){
                if(empty($val->user_id)){
                    continue;
                }
                $item['top'] = $i;
                $item['friend_num'] = $val->number;
                $item['year_investment'] = $val->string;
                $item['integral'] = $val->text;
                $phone = Func::getUserPhone($val->user_id);
                $item['display_name'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                $item['user_id'] = $val->user_id;
                $response[] = $item;
                $i++;
            }
        }else{
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array()
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $response
        );
    }


    /**
     * 获取群雄逐鹿top排行
     *
     * @JsonRpcMethod
     */
    public function getNyPackTop($params){
        if($params->min >= 0 && empty($params->max)){
            throw new OmgException(OmgException::PARAMS_NOT_NULL);
        }
        $res = UserAttribute::where('key','new_year_year_investment')
            ->where('number','>=',$params->min)
            ->where('number','<=',$params->max)
            ->orderBy('number','desc')
            ->orderBy('updated_at','ASC')
            ->paginate(5);
        $response = array();
        if(isset($res)){
            $i = 1;
            foreach ($res as $key=>$val){
                if(empty($val->user_id)){
                    continue;
                }
                $item['top'] = $i;
                $item['year_investment'] = $val->number;
                $phone = Func::getUserPhone($val->user_id);
                $item['display_name'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                $item['user_id'] = $val->user_id;
                $response[] = $item;
                $i++;
            }
        }else{
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array()
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $response
        );
    }

    /**
     * 给用户发100红包
     *
     * @JsonRpcMethod
     */
    public function consolationPrize(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $status = SendAward::ActiveSendAward($userId,'consolation_prize');
        if(isset($status['msg'])){
            if($status['msg'] == "频次验证不通过"){
                throw new OmgException(OmgException::MALL_IS_HAS);
            }
            if($status['msg'] == "活动不存在！"){
                throw new OmgException(OmgException::AWARD_NOT_EXIST);
            }
            if($status['msg'] == "发奖失败！"){
                throw new OmgException(OmgException::SEND_ERROR);
            }
        }
        $awardName = isset($status[0]['award_name']) ? $status[0]['award_name'] : '';
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>array("award_name"=>$awardName)
        );
    }
    /**
     * 给用户发100元红包
     *
     * @JsonRpcMethod
     */
    public function shakeShare(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $status = SendAward::ActiveSendAward($userId,'shake_to_shake3_share');
        if(isset($status['msg'])){
            if($status['msg'] == "频次验证不通过"){
                throw new OmgException(OmgException::MALL_IS_HAS);
            }
            if($status['msg'] == "活动不存在！"){
                throw new OmgException(OmgException::AWARD_NOT_EXIST);
            }
            if($status['msg'] == "发奖失败！"){
                throw new OmgException(OmgException::SEND_ERROR);
            }
        }
        $awardName = isset($status[0]['award_name']) ? $status[0]['award_name'] : '';
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>array("award_name"=>$awardName)
        );
    }
    /**
     * 判断是否是中影票务通渠道和是否领奖
     *
     * @JsonRpcMethod
     */
    public function zypwtStatus(){
        global $userId;
        $return = ['isChannel'=>1,'isGet'=>1];
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //判断是否是该渠道
        $userInfo = Func::getUserBasicInfo($userId,true);
        if(isset($userInfo['from_channel']) && $userInfo['from_channel'] != 'zypwt'){
            $return['isChannel'] = 0;
        }
        //判断是否获得
        $isGet = ActivityService::isExistByAliasUserID('zypwt_channel',$userId);
        if(empty($isGet)){
            $return['isGet'] = 0;
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>$return
        );
    }

    /**
     * 网贷天眼积分值是否超过预算
     *
     * @JsonRpcMethod
     */
    static function wdtyExceedLimit(){
        $wdtyConfig = config::get("wdty");
        if(empty($wdtyConfig)){
            return array(
                'code' => 0,
                'message' => 'success',
                'data'=> false
            );
        }
        //判断是否超过
        $globalKey = $wdtyConfig['alias_name']."_".date("Ymd");
        $totalIntegral = GlobalAttributes::getItem($globalKey);
        if(isset($totalIntegral['number']) && $totalIntegral['number']>= $wdtyConfig['max_integral']){
            return array(
                'code' => 0,
                'message' => 'success',
                'data'=> false
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=> true
        );
    }

    /**
     * 总收益账单2.5%加息券
     *
     * @JsonRpcMethod
     */
    static function incomeStatementStatus(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = ActivityService::isExistByAliasUserID('income_statement_2.5',$userId);
        if($res >= 1){
            return array(
                'code' => 0,
                'message' => 'success',
                'data'=> true
            );
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=> false
        );
    }
    /**
     * 总收益账单2.5%加息券
     *
     * @JsonRpcMethod
     */
    static function incomeStatement(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = SendAward::ActiveSendAward($userId,'income_statement_2.5');
        //调用发奖
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=> $res
        );
    }
}
