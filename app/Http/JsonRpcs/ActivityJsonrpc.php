<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\SendRewardLog;
use App\Models\User;
use App\Service\SendAward;
use App\Service\Attributes;
use App\Models\UserAttribute;
use Lib\JsonRpcClient;
use Validator, Config;

use App\Models\Award;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Coupon;

class ActivityJsonRpc extends JsonRpc {
    
    /**
     * 领取分享奖励
     *      
     * @JsonRpcMethod
     */
    public function signinShare() {
        global $userId;
        
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $signinName = 'signin';
        $signinActivity = Activity::where('alias_name', $signinName)->first();
        
        $sharedName = 'signinShared';
        $sharedActivity = Activity::where('alias_name', $sharedName)->first();
        $today = date('Y-m-d');
        
        
        //是否签到
        $signinRes = SendRewardLog::where(['user_id' => $userId, 'activity_id' => $signinActivity['id']])->whereRaw("date(created_at) = '{$today}'")->first();
        if(!$signinRes) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        $shared = $this->isShared($userId);
        //是否分享
        if($shared) {
            $award = SendAward::getAward($signinRes['award_type'], $signinRes['award_id']);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isShared' => true,
                    'award' => [$award['name']],
                ),
            );
        }

        //给用户发奖
        $res = SendAward::sendDataRole($userId, $signinRes['award_type'], $signinRes['award_id'], $sharedActivity['id']);
        $awardName = $res['award_name'];
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isShared' => false,
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
        $userId = 21;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $res = UserAttribute::where('user_id',$userId)->whereIn('key',['invite_investment_first','invite_investment','year_investment_50000','signin','year_investment_100000'])->get();
        $data = [
            'invite_investment_first'=>0,
            'invite_investment'=>0,
            'year_investment_50000'=>0,
            'signin'=>0,
            'year_investment_100000'=>0
        ];
        foreach ($res as $val){
            switch ($val->key) {
                case 'invite_investment_first':
                    $data['invite_investment_first'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'invite_investment':
                    $data['invite_investment'] = isset($val->number) ? $val->number : 0;
                    break;
                case 'year_investment_50000':
                    $data['year_investment_50000'] = isset($res->number) ? $val->number : 0;
                    break;
                case 'signin':
                    $data['signin'] = isset($res->number) ? $val->number : 0;
                    break;
                case 'year_investment_100000':
                    $data['year_investment_100000'] = isset($res->number) ? $val->number : 0;
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
     * 领取连续签到奖励
     * 
     * @JsonRpcMethod
     */
    public function signinDay($params) {
        global $userId;
        $today = date('Y-m-d', time());
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
        $activity = Activity::where('alias_name', $aliasName)->first();
        
        if(!$activity) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        
        $signin = Activity::where('alias_name', 'signin')->first();
        
        $where = array(
            'user_id' => $userId,
            'activity_id' => $signin['id'],
        );

        $signinRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();

        if(!$signinRes) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        $remark = json_decode($signinRes['remark'], true);
        //获取连续签到天数
        if(isset($remark['continue'])) {
            $continue = intval($remark['continue']);
        }else {
            $continue = 0; 
        }

        if($day > $continue) {
            throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
        }
        $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));

        $awardRes = SendRewardLog::where(array(
            'user_id'  => $userId,
            'activity_id' => $activity['id'],
        ))->whereRaw("created_at >= '{$before}'")->first();

        if($awardRes) {
            $award = SendAward::getAward($awardRes['award_type'], $awardRes['award_id']);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isAward' => true,
                    'awards' => [$award['name']],
                ),
            ); 
        }

        $res = SendAward::addAwardByActivity($userId, $activity['id']);
        $awardName = $res[0]['award_name'];
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isAward' => false,
                'awards' => [$awardName],
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
        return $this->innerSignin($userId);
    }

    public function innerSignin($userId) {
        $aliasName = 'signin';
        $days = array(7, 14, 21, 28);
        $daysLength = count($days);
        $last = $days[$daysLength-1];

        //是否登录
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $today = date('Y-m-d', time());
        $yesterday = date('Y-m-d', time() - 3600*24);
        $continue = 1;

        $activity = Activity::where('alias_name', $aliasName)->with('rules')->with('awards')->first();
        if(!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        // 今日是否签到
        $where = array(
            'user_id' => $userId,
            'activity_id' => $activity['id'],
        );
        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        if($todayRes) {
            $remark = json_decode($todayRes['remark'], true);
            $award = SendAward::getAward($todayRes['award_type'], $todayRes['award_id']);
            if(isset($remark['continue'])) {
                $continue = intval($remark['continue']);
            }else {
                $continue = 0;
            }

            // 是否分享
            $shared = $this->isShared($userId);

            //获取额外奖励记录
            $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));

            foreach($days as $key => $day) {
                if($continue <= $day){
                    if($key == 0) {
                        $start = 1;
                    }else{
                        $start = $days[$key-1] + 1;
                    }
                    $end = $day;
                    break;
                }
            }
            $extra = $this->getExtraAwards($userId, $before, $end);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isSignin' => true,
                    'current' => $continue,
                    'start' => $start,
                    'end' => $end,
                    'extra' => $extra,
                    'shared' => $shared,
                    'last' => $last,
                    'award' => [$award['name']],
                ),
            );
         }
        
        // 发奖
        $res = SendAward::addAwardByActivity($userId, $activity['id']);
        $awardName = $res[0]['award_name'];

        // 连续登陆
        $yesterdayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$yesterday}'")->first();
        if(!empty($yesterdayRes)){
            $remark = json_decode($yesterdayRes['remark'], true);
            if(isset($remark['continue'])) {
                $continue = intval($remark['continue']);
            }else {
                $continue = 0;
            }
            $continue += 1;
            if($continue > 28) {
                $continue = 1;
            }
        }

        // 更新连续登陆天数
        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        $remark = json_decode($todayRes['remark'], true) ;
        $remark['continue'] = $continue;
        $todayRes->remark = json_encode($remark);
        $todayRes->save();
        
        
        foreach($days as $key => $day) {
            if($continue <= $day){
                if($key == 0) {
                    $start = 1;
                }else{
                    $start = $days[$key-1] + 1;
                }
                $end = $day;
                break;
            }            
        }

        // 获取额外奖励领取记录
        $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));
        $extra = $this->getExtraAwards($userId, $before, $end);

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isSignin' => false,
                'current' => $continue,
                'start' => $start,
                'end' => $end,
                'extra' => $extra,
                'shared' => false,
                'award' => [$awardName],
                'last' => $last,
            ),
        );           
    }

    // 获取额外奖励领取记录
    private function getExtraAwards($userId, $before, $day) {
        $activity = Activity::where('alias_name', "signinDay_{$day}")->first();
        if($activity) {
            $awardRes = SendRewardLog::where(array(
                'user_id'  => $userId,
                'activity_id' => $activity['id'],
            ))->whereRaw("created_at >= '{$before}'")->first();
            if($awardRes){
                return true;
            }else{
                return false;
            }
        }
        throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
    }
    
    // 今天是否分享
    private function isShared($userId) {
        $sharedName = 'signinShared';
        $sharedActivity = Activity::where('alias_name', $sharedName)->first();
        $today = date('Y-m-d', time());
        
        $sharedRes = SendRewardLog::where(['user_id' => $userId, 'activity_id' => $sharedActivity['id']])->whereRaw("date(created_at) = '{$today}'")->first();
        if($sharedRes) {
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
     * 获取双十一抽奖次数
     * 
     * @JsonRpcMethod
     */
    public function getDoubleElevenChance() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('activity.double_eleven');
        
        $res1 = Attributes::getNumber($userId, $config['key1'], 1);
        $res2 = Attributes::getNumber($userId, $config['key2'], 0);
        $res3 = Attributes::getNumber($userId, $config['key3'], 0);

        $totalNumber = $res1 + $res2 + $res3;
        if(!is_numeric($totalNumber)){
            $totalNumber = 0;    
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $totalNumber,
        );           
    }

    /**
     * 使用双十一抽奖次数
     *
     * @JsonRpcMethod
     */
    public function useDoubleElevenChance() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('activity.double_eleven'); 
        $awardList = Config::get('activity.double_eleven.award_list'); 
        
        $res1 = Attributes::getNumber($userId, $config['key1'], 1);
        $res2 = Attributes::getNumber($userId, $config['key2'], 0);
        $res3 = Attributes::getNumber($userId, $config['key3'], 0);
        if($res1 > 0) {
            Attributes::decrement($userId, $config['key1']);
            $awards = SendAward::ActiveSendAward($userId, $config['key1']);
        }elseif($res2 > 0) {
            Attributes::decrement($userId, $config['key2']);
            $awards = SendAward::ActiveSendAward($userId, $config['key2']);
        }elseif($res3 > 0) {
            Attributes::decrement($userId, $config['key3']);
            $awards = SendAward::ActiveSendAward($userId, $config['key3']);
        }else{
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'status'=> 1 ,   
                    'msg' => '抽奖次数已用完', 
                ],
            );
        }
        
        if(!isset($awards[0])) {
            $awardName = '服务器错误,请重试';     
            $awardId = -1;
        }else{
            $award = $awards[0];
            if(!isset($awardList[$award['award_name']])) {
                $awardId = -2;
                $awardName = '服务器错误,请重试';
            }elseif(!$award['status']){
                $awardId = -3;
                $awardName = '服务器错误,请重试';
            }else{
                $awardId = $awardList[$award['award_name']];
                $awardName = $award['award_name'];
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'status' => 0,
                'award' => $award['award_name'],
                'awardId' => $awardId,
            ],
        );
    }
    /**
     * 获取双11抱团投资人数
     *
     * @JsonRpcMethod
     */
    function getDoubleElevenBaotuan() {
        $joinNum = 0;
        $awardNum = 0;
        $awardList = [];
        $aliasName = Config::get('activity.double_eleven.baotuan');
        $baotuanLevels = Config::get('activity.double_eleven.baotuan_level');
        $where = array();
        $where['alias_name'] = $aliasName;
        $where['enable'] = 1;
        $activity = Activity::where($where)->first();
        if($activity && $activity['join_num']) {
            $joinNum = $activity['join_num'];
        }

        $awardUsers = UserAttribute::where(array('key' => $aliasName))->orderBy('id', 'asc')->limit(100)->get();

        foreach($baotuanLevels as $item) {
            if($joinNum >= $item['min']) {
                $curAwardList = [];
                for($i = $awardNum; $i < $awardNum+$item['number'] && $i< count($awardUsers); $i++) {
                    $curAwardList[] = array('userId' => $awardUsers[$i]['user_id'], 'phone'=> protectPhone($awardUsers[$i]['text']), 'award' => $item['award']);
                }                           
                $awardNum += $item['number'];
                if(count($curAwardList) !== 0) {
                    $awardList[] = $curAwardList;
                }
            }
        }
        if(count($awardList) > 0) {
            $curAwardList = $awardList[count($awardList)-1];
        }else{
            $curAwardList = [];
        }
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => [
                'curAwardList' => $curAwardList,
                'awardList' => $awardList,
                'joinNum' => $joinNum, 
            ], 
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
        if($awardType >= 1 && $awardType <= 6) {
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
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
}