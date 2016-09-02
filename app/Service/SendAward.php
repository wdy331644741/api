<?php
namespace App\Service;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/5
 * Time: 14:35
 */
use App\Http\Requests\Request;
use App\Models\Activity;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\Coupon;
use App\Models\CouponCode;
use Lib\JsonRpcClient;
use App\Models\SendRewardLog;
use App\Service\SendMessage;
use App\Service\RuleCheck;
use App\Models\ActivityJoin;
use Config;
use Validator;
class SendAward
{
    static private $userID;
    static private  $activityID;
    static private  $money;

    /**
     * 验证规则和发送奖品
     * @param $activityInfo
     * @param $userID
     * @param array $triggerData
     * @return mixed
     */
    static function ruleCheckAndSendAward($activityInfo, $userID, $triggerData = array()){
        //验证频次
        if (!self::frequency($userID, $activityInfo)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return self::addJoins($userID, $activityInfo, 1, json_encode(array('err_msg' => 'pass frequency')));
        }

        //验证规则
        $ruleStatus = RuleCheck::check($activityInfo['id'], $userID, $triggerData);
        $activityID = $activityInfo['id'];
        if ($ruleStatus['send'] !== true) {
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return self::addJoins($userID, $activityInfo, 2, json_encode($ruleStatus['errmsg']));
        }

        //*****发奖之前做的附加条件操作*****
        $additional_status = self::beforeSendAward($activityInfo, $triggerData);

        //*****给本人发的奖励*****
        $status = self::addAwardByActivity($userID, $activityID);

        //******给邀请人发奖励*****
        $invite_status = self::InviteSendAward($userID, $activityID);

        //拼接状态
        if(!empty($additional_status)){
            if(!empty($status)) {
                $status[] = $additional_status;
            }
            if(isset($invite_status['awards']) && !empty($invite_status['awards'])) {
                $invite_status['awards'][] = $additional_status;
            }
        }

        //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
        return self::addJoins($userID, $activityInfo, 3, json_encode($status), json_encode($invite_status));
    }

    /**
     * 给邀请人发奖
     * @param $userID
     * @param $activityID
     * @return array
     */
    static function InviteSendAward($userID, $activityID)
    {
        $url = Config::get('award.reward_http_url');
        $client = new JsonRpcClient($url);
        //获取邀请人id
        $res = $client->getInviteUser(array('uid' => $userID));
        $invite_status = array();
        if (isset($res['result']['code']) && $res['result']['code'] === 0 && isset($res['result']['data']) && !empty($res['result']['data'])) {
            $inviteUserID = isset($res['result']['data']['id']) ? $res['result']['data']['id'] : 0;
            if (!empty($inviteUserID)) {
                //调用发奖接口
                $status = self::addAwardToInvite($inviteUserID, $activityID);
                $invite_status['inviteUserID'] = $inviteUserID;
                $invite_status['awards'] = $status;
            }
        }
        return $invite_status;
    }

    /**
     * 按照充值金额送体验金
     * @param $activityInfo
     * @param $triggerData
     * @return mixed
     */
    static function beforeSendAward($activityInfo, $triggerData){
        $return = array();
        switch ($activityInfo['alias_name']) {
            case 'songtiyanjin':
                //如果是充值触发
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'recharge'){
                    $recharge_money = isset($triggerData['money']) && !empty($triggerData['money']) ? intval($triggerData['money'] / 10000) : 0;
                    //金额不大于一万九不发奖
                    if(empty($recharge_money)){
                        return $return;
                    }
                    $money = $recharge_money * 1000;
                    $awards['id'] = 0;
                    $awards['user_id'] = $triggerData['user_id'];
                    $awards['source_id'] = $activityInfo['id'];
                    $awards['name'] = $money.'体验金';
                    $awards['source_name'] = $activityInfo['name'];
                    $awards['experience_amount_money'] = $money;
                    $awards['effective_time_type'] = 1;
                    $awards['effective_time_day'] = 7;
                    $awards['platform_type'] = 0;
                    $awards['limit_desc'] = '';
                    $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
                    $return = self::experience($awards);
                }
        }
        //发奖
        return $return;
    }

    /**
     * 验证频次
     * @param $userID
     * @param $activityInfo
     * @return bool
     */
    static function frequency($userID,$activityInfo){
        if(isset($activityInfo['id']) && !empty($activityInfo['id']) && isset($activityInfo['frequency'])){
            $where = array();
            $where['user_id'] = $userID;
            $where['activity_id'] = $activityInfo['id'];
            $where['status'] = 3;
            //不限
            if($activityInfo['frequency'] == 0){
                $count = 0;
            }
            //一天一次
            $date = date('Y-m-d');
            if($activityInfo['frequency'] == 1){
                $count = ActivityJoin::where($where)->whereRaw("date(created_at) = '{$date}'")->get()->count();
            }
            //仅一次
            if($activityInfo['frequency'] == 2){
                $count = ActivityJoin::where($where)->get()->count();
            }
            if($count == 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 添加到活动参与表
     * @param $userID
     * @param $activityInfo
     * @param $status
     * @param string $remark
     * @param string $invite_remark
     * @return mixed
     */
    static function addJoins($userID,$activityInfo,$status,$remark = '',$invite_remark = ''){
        $data['activity_id'] = $activityInfo['id'];
        $data['user_id'] = $userID;
        $data['alias_name'] = $activityInfo['alias_name'];
        $data['shared'] = 0;
        $data['continue'] = 0;
        $data['isExternal'] = 0;
        $data['status'] = $status;
        $data['trigger_type'] = $activityInfo['trigger_type'];
        $data['remark'] = $remark;
        $data['invite_remark'] = $invite_remark;
        $data['created_at'] = date("Y-m-d H:i:s");
        ActivityJoin::insertGetId($data);
        return $data;
    }
    /**
     * 按活动添加奖品
     *
     * @param $userId
     * @param $activityId
     * @return array
     */
    static function addAwardByActivity($userId, $activityId) {
        $activity = Activity::where('id', $activityId)->with('awards')->first();
        $awards = $activity['awards'];
        $res = [];
        if($activity['award_rule'] == 1) {
            foreach($awards as $award) {
                $res[] = Self::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'] );
            }
        }
        if($activity['award_rule'] == 2) {
            $awards = $activity['awards'];
            $priority = 0;
            foreach($awards as $award) {
                $priority += $award['priority'];
            }
            $target = rand(1, $priority);
            foreach($awards as $award) {
                $target = $target - $award['priority'];
                if($target <= 0) {
                    break;
                }
            }
            $res[] = Self::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'] );
        }
        return $res;


    }
    /**
     * 邀请人发奖
     *
     * @param $userId
     * @param $activityId
     * @return array
     */
    static function addAwardToInvite($userId, $activityId) {
        $activity = Activity::where('id', $activityId)->with('award_invite')->first();
        $awardInvite = $activity['award_invite'];
        $res = [];
        foreach($awardInvite as $award) {
            $res[] = Self::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'] );
        }
        return $res;
    }
    /**
     * @需要提出去
     * @param $userID ，$award_type,$award_id
     *
     */
    static function sendDataRole($userID,$award_type, $award_id, $activityID = 0, $sourceName = '',$batch_id = 0)
    {
        self::$userID = $userID;
        self::$activityID = $activityID;
        //获取数据
        $table = self::_getAwardTable($award_type);
        $info = $table::where('id', $award_id)->select()->get()->toArray();
        if(count($info) >= 1){
            $info = $info[0];
        }
        //来源id
        $info['source_id'] = $activityID;
        //获取出活动的名称
        $activity = Activity::where('id',$activityID)->select('name')->get()->toArray();
        //来源名称
        $info['source_name'] = isset($activity[0]['name']) ? $activity[0]['name'] : $sourceName;
        //用户id
        $info['user_id'] = $userID;
        //批次id
        if(!empty($batch_id)){
            $info['batch_id'] = $batch_id;
        }
        if ($award_type == 1) {
            //加息券
            return self::increases($info);
        } elseif ($award_type == 2) {
            if ($info['red_type'] == 1) {
                //直抵红包
                return self::redMoney($info);
            } elseif ($info['red_type'] == 2){
                //百分比红包
                return self::redMaxMoney($info);
            }
        } elseif ($award_type == 3) {
            //体验金
            return self::experience($info);
        } elseif ($award_type == 6) {
            //优惠券
            return self::coupon($info);
        }
    }
    //加息券
    static function increases($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 1;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'rate_increases' => 'required|numeric|between:0.0001,1',
            'rate_increases_type' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('rate_increases_time', 'required|integer', function($input) {
            return $input->rate_increases_type == 2;
        });
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //加息券
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = $info['rate_increases_type'];//直抵红包
        $data['rate'] = $info['rate_increases'];//加息值
        if ($info['rate_increases_type'] == 2) {
            $data['continuous_days'] = $info['rate_increases_time'];//加息天数
        }
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['source_name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->interestCoupon($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //直抵红包
    static function redMoney($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 2;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //直抵红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = 1;//直抵红包
        $data['amount'] = $info['red_money'];//红包金额
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志&发送消息
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //百分比红包
    static function redMaxMoney($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 2;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|integer|min:1',
            'percentage' => 'required|numeric|between:0.001,1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //百分比红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = 2;//百分比红包
        $data['max_amount'] = $info['red_money'];//红包最高金额
        $data['percentage'] = $info['percentage'];//红包百分比
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志&发送消息
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //体验金
    static function experience($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 3;
        $info['uuid'] = null;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'experience_amount_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //体验金
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['name'] = $info['name'];
        //体验金额
        $data['amount'] = $info['experience_amount_money'];
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['source_name'] = $info['source_name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->experience($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //发送消息&存储日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //优惠券
    static public function coupon($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 6;
        $info['uuid'] = null;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>3,'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //根据id获取出可用的优惠卷
        $where = array();
        $where['coupon_id'] = $info['id'];
        $where['is_use'] = 0;
        $data = CouponCode::where($where)->get()->first();
        if (!empty($data) && isset($data['code']) && !empty($data['code']) && isset($data['id']) && !empty($data['id'])) {
            //发送消息
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>6,'status'=>true);
            $info['code'] = $data['code'];
            $info['remark'] = json_encode($err);
            $info['status'] = 1;
            self::sendMessage($info);
            //修改优惠码状态
            CouponCode::where("id",$data['id'])->update(array('is_use'=>1));
            return $err;
        }else{
            //存储到日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>6,'status'=>false,'err_msg'=>'coupon_empty');
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    static function _getAwardTable($awardType){
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
    /**
     *  根据awardType获取奖品详情 
     * 
     * @param $awardType
     * @param $awardId
     * @return mixed
     */
    
    static function getAward($awardType, $awardId) {
        $table = self::_getAwardTable($awardType);    
        return $table->where('id', $awardId)->first();
    }
    //获取奖品
    static function getSendedAwards($userId, $activityId, $day) {
        return SendRewardLog::where(array(
            'user_id'  => $userId, 
            'activity_id' => $activityId,
        ))->whereRaw("date(created_at) = '{$day}'")->get();
    }
    
    //生成Guid
    static function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * 发送站内信及添加日志
     * @param $info
     * @return array
     */
    static function sendMessage($info){
        $message = array();
        $message['sourcename'] = $info['source_name'];
        $message['awardname'] = $info['name'];
        $message['code'] = isset($info['code']) ? $info['code'] : '';
        $return = array();
        $info['message_status'] = 0;
        $info['mail_status'] = 0;
        if(!empty($info['message'])){
            //发送短信
            $return['message'] = SendMessage::Message($info['user_id'],$info['message'],$message);
            //发送成功
            if($return['message'] == true){
                $info['message_status'] = 2;
            }
            //发送失败
            if($return['message'] == false){
                $info['message_status'] = 1;
            }
        }else{
            //发送模板为空
            $info['message_status'] = 0;
        }
        if(!empty($info['mail'])){
            //发送站内信
            $return['mail'] = SendMessage::Mail($info['user_id'],$info['mail'],$message);
            //发送成功
            if($return['mail'] == true){
                $info['mail_status'] = 2;
            }
            //发送失败
            if($return['mail'] == false){
                $info['mail_status'] = 1;
            }
        }else{
            //发送模板为空
            $info['mail_status'] = 0;
        }
        //添加日志
        self::addLog($info);
        return $return;
    }
    /**
     * 添加到日志
     * @param $source_id
     * @param $award_type
     * @param $uuid
     * @param $remark
     * @return mixed
     */
    static function addLog($info){
        $SendRewardLog = new SendRewardLog;
        $data['user_id'] = $info['user_id'];
        $data['activity_id'] = $info['source_id'];
        $data['source_id'] = $info['source_id'];
        $data['award_type'] = $info['award_type'];
        $data['uuid'] = $info['uuid'];
        $data['remark'] = $info['remark'];
        $data['award_id'] = $info['id'];
        $data['status'] = $info['status'];
        $data['coupon_code'] = isset($info['code']) ? $info['code'] : '';
        $data['message_status'] = isset($info['message_status']) ? $info['message_status'] : '';
        $data['mail_status'] = isset($info['mail_status']) ? $info['mail_status'] : '';
        $data['created_at'] = date("Y-m-d H:i:s");
        if(isset($info['batch_id']) && !empty($info['batch_id'])){
            $data['batch_id'] = $info['batch_id'];
        }
        $insertID = $SendRewardLog->insertGetId($data);
        return $insertID;
    }
}