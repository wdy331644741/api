<?php
namespace App\Service;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/5
 * Time: 14:35
 */
use App\Http\Requests\Request;
use App\Jobs\CollectCard;
use App\Models\Activity;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\AwardCash;
use App\Models\Coupon;
use App\Models\CouponCode;
use App\Models\Hd19AmountShare;
use App\Models\HdWorldCupExtra;
use Lib\JsonRpcClient;
use App\Models\SendRewardLog;
use App\Service\SendMessage;
use App\Service\RuleCheck;
use App\Models\ActivityJoin;
use App\Service\Attributes;
use App\Service\OneYuanBasic;
use App\Service\Flow;
use App\Service\ActivityService;
use App\Models\UserAttribute;
use Config;
use Validator;
use DB;
use Carbon\Carbon;
use App\Service\Func;
use App\Service\NvshenyueService;
use App\Service\TzyxjService;
use App\Service\Open;
use App\Service\AfterSendAward;
use App\Service\PoBaiYiService;
use App\Service\CollectCardService;
use App\Service\OctLotteryService;
use App\Service\Hockey;
use App\Service\CatchDollService;//邀请注册送 抓娃娃机会
use App\Service\InviteTaskService;//好友邀请3.0

class SendAward
{
    static private $userID;
    static private  $activityID;

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

        //好友邀请3.0  绑卡、首投。不满足发奖条件时(需要先领取任务)  by：王东洋
        if(in_array($activityInfo['alias_name'], ['invite_limit_task_bind', 'invite_limit_task_invest'])){
            
            if($activityInfo['alias_name'] == 'invite_limit_task_invest' 
                && isset($triggerData['tag']) && !empty($triggerData['tag']) 
                && isset($triggerData['user_id']) && !empty($triggerData['user_id']) 
                && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id']) 
                && $triggerData['tag'] == 'investment' ){

                $task3_user = $triggerData['from_user_id'];//任务3 奖励是给from_user_id的
            }else{
                $task3_user = 0;
            }
            $server = new InviteTaskService($userID);

            $d = $server->isTouchTask($activityInfo['alias_name'],$triggerData);
            if(!$d){
                return '不发奖';
            }
        }
        //好友邀请3.0 end

        //*****活动参与人数加1*****
        Activity::where('id',$activityInfo['id'])->increment('join_num');

        //*****发奖之前做的附加条件操作*****
        $additional_status = self::beforeSendAward($activityInfo, $triggerData);
        //10月03日闯关状态修改
        if($activityInfo['alias_name'] == 'gq_1003' && isset($additional_status['investment'])){
            if(empty($additional_status) || $additional_status['investment'] < 10000){
                return '不发奖';
            }else{
                $Attributes = new Attributes();
                $Attributes->status($triggerData['user_id'],'cg_success','1003:1');
            }
        }

        //*****给本人发的奖励*****
        $status = self::addAwardByActivity($userID, $activityID,$triggerData);

        //******给邀请人发奖励*****
        $invite_status = self::InviteSendAward($userID, $activityID,$triggerData);

        //发奖后操作
        AfterSendAward::afterSendAward($activityInfo,$triggerData);

        //拼接状态
        if(!empty($additional_status)){
            $status[] = $additional_status;
            if(isset($invite_status['awards']) && !empty($invite_status['awards'])) {
                $invite_status['awards'][] = $additional_status;
            }
        }


        //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
        return self::addJoins($userID, $activityInfo, 3, json_encode($status), json_encode($invite_status));
    }

    /**
     * 主动触发活动发奖
     * @param $userID
     * @param $activityID
     * @return array
     */
    static function ActiveSendAward($userId, $aliasName)
    {
        if(empty($aliasName)){
            return array('msg'=>'活动别名不能为空');
        }
        if(empty($userId)){
            return array('msg'=>'未获取到用户id');
        }
        //获取该主动触发活动信息
        $where = array();
        $where['alias_name'] = trim($aliasName);
        $where['trigger_type'] = 0;
        $where['enable'] = 1;
        $list = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where($where)->first();
        if(empty($list)){
            return array('msg'=>'活动不存在！');
        }

        //获取活动下的奖品
        $data = self::ruleCheckAndSendAward($list,$userId,array('tag' => 'active', 'user_id' => $userId));

        if(!empty($data) && isset($data['status'])){
            if($data['status'] == 3){
                return json_decode($data['remark'],1);
            }
            if($data['status'] == 1){
                return array('msg'=>'频次验证不通过');
            }
            return array('msg'=>json_decode($data['remark'],1));
        }
        return array('msg'=>'发奖失败！');
    }

    /**
     * 给邀请人发奖
     * @param $userID
     * @param $activityID
     * @return array
     */
    static function InviteSendAward($userID, $activityID,$triggerData = array())
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
                //如果是注册触发就添加一个下级id，刘奇那边全民淘金用到
                if(isset($triggerData['tag']) && $triggerData['tag'] == "register"){
                    $triggerData['child_user_id'] = $userID;
                }
                $status = self::addAwardToInvite($inviteUserID, $activityID,$triggerData);
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
        $Attributes = new Attributes();

        if(!$activityInfo['alias_name']) {
            return $return;
        }

        switch ($activityInfo['alias_name']) {
            /** 踏青活动 start **/
            case 'spring_investment':
                if(
                    isset($triggerData['tag']) && $triggerData['tag'] == 'investment' && $triggerData['period'] >= 6
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id'])
                    && ( empty($activityInfo['start_at']) || $triggerData['buy_time'] >= $activityInfo['start_at'] )
                ){
//                    渠道ID：wdtyfl 渠道名称：网贷天眼返利
//                    渠道ID：wangdaizhijia  渠道名称：网贷之家
//                    渠道ID：htfl   渠道名称：虎投返利
//                    渠道ID：jffl   渠道名称：九富返利
                    $user_info = Func::getUserBasicInfo($triggerData['user_id']);
                    if (
                        in_array($user_info['from_channel'], ['wdtyfl', 'wangdaizhijia', 'htfl', 'jffl'])
                    && $triggerData['register_time'] >= $activityInfo['start_at']
                    ) {
                        return $return;
                    }
                //每邀请一名好友注册并首次出借 2000 元（6 月及以上标）
                //邀请人和被邀请人均可获得 3000出游基金。
                    //邀请人需点击“参与活动”后的邀请好友才可以满足奖励条件；
                    $fromUserId = intval($triggerData['from_user_id']);
                    if (
                        $triggerData['is_first'] && $triggerData['Investment_amount'] >= 2000
                        && $fromUserId && (empty($activityInfo['start_at']) || $triggerData['register_time'] >= $activityInfo['start_at'])
                    )
                    {
                        $join = Attributes::getItem($fromUserId, 'spring_join_key');
                        if ($join) {
                            Attributes::increment($triggerData['user_id'], 'spring_drew_user', 3000);
                            Attributes::increment($fromUserId, 'spring_drew_user', 3000);
                        }

                    }
                    //出借送踏青基金
                    if ($triggerData['Investment_amount'] >= 1000) {
                        $fund = 0;
                        switch ($triggerData['period']) {
                            case 6:
                                $fund = 500;
                                break;
                            case 12:
                                $fund = 1000;
                                break;
                            case 18:
                                $fund = 1200;
                                break;
                            case 24:
                                $fund = 1500;
                                break;
                        }
                        $fund = $fund * intval($triggerData['Investment_amount']/1000);
                        Attributes::increment($triggerData['user_id'], 'spring_drew_user', $fund);
                    }
                }
                break;
            /** 踏青活动 start **/
            /** 19 新春现金红包 start **/
            case '19amountshare_send':
                if(
                    isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'real_name'
                 && isset($triggerData['user_id']) && !empty($triggerData['user_id']) )
                {
                    if($activityInfo['end_at'] > date('Y-m-d H:i:s')){
                        $res = Hd19AmountShare::where(['user_id'=>$triggerData['user_id'],'receive_status'=>1])->get()->toArray();
                        foreach ($res as $val){
                            $uuid = SendAward::create_guid();
                            $uuid2 = SendAward::create_guid();
                            $res1 = Func::incrementAvailable($val['user_id'], $val['id'], $uuid, $val['amount'], '19amountshare_newyear_cash');
                            $res2 = Func::incrementAvailable($val['share_user_id'], $val['id'], $uuid2, $val['amount'], '19amountshare_newyear_cash');
                            $remark = ['user'=>0,'invite_user'=>0];
                            // 成功
                            if(isset($res1['result'])) {
                                $remark['user'] = 1;
                                $MailTpl = "恭喜您绑卡成功并获得“新年全民红包”活动红包奖励".$val['amount']."元，现金发放至您网利宝账户余额。";
                                SendMessage::Mail($val['user_id'],$MailTpl);
                                SendMessage::sendPush($val['user_id'],'19as_sendPush');
                            }
                            if(isset($res2['result'])) {
                                $remark['invite_user'] = 1;
                                SendMessage::sendPush($val['share_user_id'],'19asi_sendPush');
                            }
                            if($remark['user'] == 0 && $remark['invite_user'] == 0){
                                Hd19AmountShare::where('id',$val['id'])->update(['receive_status'=>3,'remark'=>json_encode($remark)]);
                            }else{
                                Hd19AmountShare::where('id',$val['id'])->update(['receive_status'=>2,'remark'=>json_encode($remark)]);
                            }
                        }
                    }
                }
                break;
            /** 19 新春现金红包 end **/

            /** 周末竞猜 start **/
            case 'weeksguess_investment':
                if(
                    isset($triggerData['tag']) && !empty($triggerData['tag'])
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id'])
                    && $triggerData['tag'] == 'investment' && $triggerData['novice_exclusive'] != 1 //非新手标(只有1是新手标)
                    && ( empty($activityInfo['start_at']) || $triggerData['buy_time'] >= $activityInfo['start_at'] )
                ){
                    //周六周日
                    $w = date('w',strtotime($triggerData['buy_time']));
                    if( $w == 6 || $w == 0 ){
                        //年化达到1000元 获得一次
                        $investmentNum = 0;
                        if($triggerData['scatter_type'] == 2){ //月标
                            $period = $triggerData['period'] > 12 ? 12 : $triggerData['period'];
                            $investmentNum = intval($triggerData['Investment_amount']*$period/12/1000);
                        }else if($triggerData['scatter_type'] == 1){
                            $investmentNum = intval($triggerData['Investment_amount']*$triggerData['period']/360/1000);
                        }
                        WeeksGuessService::addGuessNumber($triggerData['user_id'] ,'weeksguess_drew_user' ,$investmentNum);
                    }
                }
                break;
            case 'weeksguess_real_name':
                if(
                    isset($triggerData['tag']) && !empty($triggerData['tag'])
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id'])
                    && $triggerData['tag'] == 'real_name'
                    && ( empty($activityInfo['start_at']) || $triggerData['time'] >= $activityInfo['start_at'] )
                ){
                    //周六周日
                    $w = date('w',strtotime($triggerData['time']));
                    if( $w == 6 || $w == 0 ) {
                        $fromUser = Func::getUserBasicInfo($triggerData['user_id']);
                        if (
                            (empty($activityInfo['start_at']) || $fromUser['create_time'] >= $activityInfo['start_at'])
                            && $fromUser['from_user_id']
                        ) {
                            $investmentNum = 1;
                            $key = Config::get('weeksguess.drew_user_key');
                            WeeksGuessService::addGuessNumber($triggerData['user_id'], $key, $investmentNum);
                            WeeksGuessService::addGuessNumber($fromUser['from_user_id'], $key, $investmentNum);
                        }
                    }
                }
                break;
            /** 周末竞猜 end **/
            /** 双旦 砸蛋抽奖 start **/
            case 'double_egg_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) 
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id']) 
                    && $triggerData['tag'] == 'investment' 
                    ){
                    $key = 'double_egg_lott';//活动标示

                    //年化达到1000元 获得一次
                    $investmentNum = 0;
                    $fromUserNum = 0;
                    if($triggerData['novice_exclusive'] == 0){//非新手标
                        if($triggerData['scatter_type'] == 2){
                            $investmentNum = intval($triggerData['Investment_amount']*$triggerData['period']/12/1000);
                        }else if($triggerData['scatter_type'] == 1){
                            $investmentNum = intval($triggerData['Investment_amount']*$triggerData['period']/360/1000);
                        }
                    }
                    
                    //邀请好友 首投》1000 邀请人获得一次
                    if($triggerData['is_first']){
                        //当日注册并首投 额外再加一次(前提条件：如果注册时间在活动区间内)
                        $act_start = Carbon::parse($activityInfo['start_at'])->toDateTimeString();
                        $act_end = Carbon::parse($activityInfo['end_at'])->toDateTimeString();
                        if( $triggerData['register_time'] >= $act_start && $triggerData['register_time'] <= $act_end && substr($triggerData['register_time'], 0,10) ==  substr($triggerData['buy_time'], 0,10) ){
                            //()
                            $fromUserNum = $triggerData['Investment_amount']>=1000?1:0;
                        }
                        //$fromUserNum = $triggerData['Investment_amount']>=1000?1:0;
                    }

                    Attributes::increment($triggerData['user_id'] ,$key ,$investmentNum);
                    if($triggerData['from_user_id']){
                        Attributes::increment($triggerData['from_user_id'] ,$key ,$fromUserNum);
                    }

                    

                }
                break;
            /** 双旦 砸蛋抽奖 end **/

            /** 嗨翻双12 start **/
            case 'dec_twelve_register':
                if(
                    isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'register'
                    && !empty($triggerData['from_user_id']) )
                {
                    DoubleTwelveService::addDrawNum($triggerData['from_user_id'],"dec_twelve_register");
                }
                break;
            /** 嗨翻双12 end **/
            /** 曲棍球正式场活动 START */
            //投资得卡
            case 'hockey_card_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) ){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $userId = intval($triggerData['user_id']);
                    if (isset($triggerData['novice_exclusive']) && $triggerData['novice_exclusive'] != 1 && $amount >= 10000 && $userId > 0 && (empty($activityInfo['start_at']) || $activityInfo['start_at'] <= $triggerData['buy_time'])) {
                        //添加集卡和竞猜机会
                        Hockey::HockeyCardObtain($userId,$amount);
                    }
                }
                break;
            //投资得竞猜
            case 'hockey_guess_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) ){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $userId = intval($triggerData['user_id']);
                    if (isset($triggerData['novice_exclusive']) && $triggerData['novice_exclusive'] != 1 && $amount > 0 && $userId > 0 && (empty($activityInfo['start_at']) || $activityInfo['start_at'] <= $triggerData['buy_time'])) {
                        //添加投资竞猜机会
                        $num = intval($amount/10000);
                        if($num < 1){
                            return false;
                        }
                        $config = Config::get("hockey");
                        Attributes::increment($userId,$config['guess_key'],$num);
                        //发送站内信
                        $activityUrl = env("APP_URL")."/active/hockey_active/guess/index.html";
                        $msg = "亲爱的用户，恭喜您在助力女曲-竞猜场活动中通过出借获得".$num."个竞猜机会（1个竞猜=1注），下注竞猜比赛结果，竞猜正确即可参与瓜分万元现金，点击下注".$activityUrl."。";
                        SendMessage::Mail($userId,$msg);//发送站内信
                        SendMessage::Message($userId,$msg);//发送短信
                    }
                }
                break;
            //注册-邀请人和注册人都获取竞猜机会
            case 'hockey_guess_invite':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'register' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) ){
                    $userId = intval($triggerData['user_id']);
                    $fromUserId = intval($triggerData['from_user_id']);
                    if ( $userId > 0 && $fromUserId > 0 && (empty($activityInfo['start_at']) || $activityInfo['start_at'] <= $triggerData['time'])) {
                        $config = Config::get("hockey");
                        //添加邀请人竞猜机会
                        Attributes::increment($fromUserId,$config['guess_key'],1);
                        //添加注册人竞猜机会
                        Attributes::increment($userId,$config['guess_key'],1);
                        //发送站内信
                        $activityUrl = env("APP_URL")."/active/hockey_active/guess/index.html";
                        $msg = "亲爱的用户，恭喜您在助力女曲-竞猜场活动中通过邀请注册获得1个竞猜机会（1个竞猜=1注），下注竞猜比赛结果，竞猜正确即可参与瓜分万元现金，点击下注".$activityUrl."。";
                        SendMessage::Mail($fromUserId, $msg);//邀请人发送站内信
                        SendMessage::Message($fromUserId,$msg);//邀请人发送短信
                        SendMessage::Mail($userId,$msg);//发送站内信
                        SendMessage::Message($userId,$msg);//发送短信
                    }
                }
                break;
            /** 曲棍球正式场活动 END */


            /** 拆礼物 投资送机会 START */
            case 'open_gift_investment_give_change'://注册送抽奖
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) 
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id']) 
                    && $triggerData['tag'] == 'investment' 
                    ){
                    $key = 'open_gift';//活动标示

                    //新用户 首投满2000元获得一次拆礼物机会、4000元2次
                    //当日注册并首投 额外再加一次

                    $investmentNum = 0;
                    $fromUserNum = 0;
                    if($triggerData['is_first']){
                        $investmentNum += intval($triggerData['Investment_amount']/2000);
                        $act_start = Carbon::parse($activityInfo['start_at'])->toDateTimeString();
                        $act_end = Carbon::parse($activityInfo['end_at'])->toDateTimeString();

                        //当日注册并首投 额外再加一次(前提条件：如果注册时间在活动区间内)
                        //open_gift_investment_give_change 和 open_gift 活动的时间配置一致
                        if( $triggerData['register_time'] >= $act_start && $triggerData['register_time'] <= $act_end && substr($triggerData['register_time'], 0,10) ==  substr($triggerData['buy_time'], 0,10) ){
                            //()
                            $investmentNum += 1;
                        }
                        //新用户 每邀请1名好友注册并首投满500 ，双方各+1次
                        // if($triggerData['from_user_id']){
                        //     $fromUserIsNew = Func::isNewUser($triggerData['from_user_id']);
                        //     if($fromUserIsNew && $triggerData['Investment_amount'] >+ 500){
                        //         $investmentNum += 1;
                        //         $fromUserNum += 1;
                        //     }
                        // }
                    }
                    Attributes::increment($triggerData['user_id'] ,$key ,$investmentNum);
                    if($triggerData['from_user_id']){
                        Attributes::increment($triggerData['from_user_id'] ,$key ,$fromUserNum);
                    }

                    

                }
                break;
            /** 拆礼物 投资送机会 end */
            
            /** 抓娃娃机 邀请注册送机会 START */
            case 'catch_doll_register_change'://注册送抽奖
                if(isset($triggerData['tag']) 
                    && !empty($triggerData['tag']) && $triggerData['tag'] == 'register' 
                    && $triggerData['from_user_id'] != 0
                    ){
                    $reference_date = $triggerData['time'];
                    //时间必须以 请求达到运营中心 为基准。不然每个自然日0点 有bug
                    $user_inc = $triggerData['from_user_id'];
                    CatchDollService::registerGiveChange($user_inc,2);
                    CatchDollService::registerGiveChange($triggerData['user_id'],1);//注册人给一次
                    // OctLotteryService::ctlUserAttributes($user_inc,$invest_switch,$reference_date);

                }
                break;
            /** 抓娃娃机 邀请注册送机会 end */

            /** 十月份抽奖投资送次数 START */
            case 'oct_lottery_registergive'://注册送抽奖
                if(isset($triggerData['tag']) 
                    && !empty($triggerData['tag']) && $triggerData['tag'] == 'register' 
                    && $triggerData['from_user_id'] != 0
                    ){
                    $reference_date = $triggerData['time'];
                    //时间必须以 请求达到运营中心 为基准。不然每个自然日0点 有bug
                    $user_inc = $triggerData['from_user_id'];
                    $invest_switch = 1;//注册给一次
                    OctLotteryService::ctlUserAttributes($user_inc,$invest_switch,$reference_date);

                }
                break;
            case 'oct_lottery_investgive'://投资送抽奖
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) 
                    && isset($triggerData['user_id']) && !empty($triggerData['user_id']) 
                    && $triggerData['tag'] == 'investment' 
                    ){
                    $reference_date = $triggerData['buy_time'];
                    //时间必须以 请求达到运营中心 为基准。不然每个自然日0点 有bug
                    $user_inc = $triggerData['user_id'];
                    //最大赠送2次
                    $invest_switch = intval($triggerData['Investment_amount']/10000) > 2?2:intval($triggerData['Investment_amount']/10000);
                    OctLotteryService::ctlUserAttributes($user_inc,$invest_switch,$reference_date);

                }
                break;
            /** 十月份抽奖投资送次数 end */

            /** 双11 START */
            //签到
            case 'nov_eleven_sign_in':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'daylySignin'){
                    DoubleElevenService::signInCard($triggerData['user_id'], 'nov_eleven_sign_in');
                }
                break;
            /** 双11 END */

            /** 四周年活动投多少送多少体验金 START */
            //投资
            case 'four_birthday_invest_experience':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) ){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if ( $amount > 0) {
                        $awards['id'] = 0;
                        $awards['user_id'] = $triggerData['user_id'];
                        $awards['source_id'] = $activityInfo['id'];
                        $awards['name'] = "四周年".$amount.'体验金';
                        $awards['source_name'] = $activityInfo['name'];
                        $awards['experience_amount_money'] = $amount;
                        $awards['effective_time_type'] = 1;
                        $awards['effective_time_day'] = 7;
                        $awards['platform_type'] = 0;
                        $awards['limit_desc'] = '';
                        $awards['trigger'] = isset($activityInfo['trigger_type']) ? $activityInfo['trigger_type'] : '-1';
                        $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
                        $return = self::experience($awards);
                    }
                }
                break;
            /** 四周年活动投多少送多少体验金 END */
            /** 跳一跳 start **/
            case 'jump_investment':
                if( isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && $triggerData['is_first'] ){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $num = 0;
                    if ( $amount >= 2000 ) {
                        $num = 3;
                    } else if ($amount >= 1000) {
                        $num = 2;
                    } else if ($amount >= 500) {
                        $num = 1;
                    }
                    $register_date = date('Ymd', strtotime($triggerData['register_time']));
                    $now = date('Ymd', time());
                    if ($register_date == $now) {
                        $num +=1;
                    }
                    if ( $num > 0 ) {
                        JumpService::addDrawNum($triggerData['user_id'],$num);
                    }
                }
                break;
            /** 跳一跳 end **/
            /** 逢百抽大奖 start **/
            case 'perbai_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && $triggerData['buy_time'] >= $activityInfo['start_at']){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if ( $triggerData['period'] >= 3 && $amount >= 5000 ) {
                        $num = intval($amount/5000);
                        PerBaiService::addDrawNum($triggerData['user_id'],$num, 'investment', $triggerData['buy_time']);
                    }
                }
                break;
                //邀请人首投
            case 'perbai_invite_investment':
                if( isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id']) && $triggerData['is_first'] && $triggerData['buy_time'] >= $activityInfo['start_at']){
                        PerBaiService::addDrawNum($triggerData['from_user_id'],1, 'invite', $triggerData['buy_time']);
                }
                break;
            /** 逢百抽大奖 end **/

            /** FIFA World Cup 活动 start **/
            //投资
            case 'world_cup_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) ){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if ( $amount >= 5000) {
                        $num = intval($amount/5000);
                        $config = Config::get('worldcup');
                        Attributes::incrementByDay($triggerData['user_id'],$config['drew_user_key'],$num);
                    }
                }
                break;
            //绑卡
            case 'world_cup_bind_bank_card':
//                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'bind_bank_card' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])){
                    $url = Config::get('award.reward_http_url');
                    $client = new JsonRpcClient($url);
                    //获取邀请人id
                    $user_info = $client->getInviteUser(array('uid' => $triggerData['user_id']));
                    if (isset($user_info['result']['data']['id'])) {
                        WorldCupService::addExtraBall($user_info['result']['data']['id'], $triggerData['user_id'], 1, 1);
                    }
//                }
                break;
            //邀请人首投
            case 'world_cup_invite_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id']) && $triggerData['is_first']){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount >= 2000){
                        WorldCupService::addExtraBall($triggerData['from_user_id'], $triggerData['user_id'], 2, 2);
                    }
                }
                break;
            /** FIFA World Cup 活动 end **/
            /**快乐大本营集卡活动 start**/
            //注册
//            case 'collect_card_register':
//                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'register'){
//                    Attributes::increment($triggerData['user_id'],"collect_card_drew_user",1);
//                }
//                break;
            //每日登陆送一次抽卡次数
//            case 'collect_card_login':
//            case 'collect_card_share':
//                if(isset($triggerData['tag']) && $triggerData['tag'] == 'active'){
//                    Attributes::increment($triggerData['user_id'],"collect_card_drew_user",1);
//                }
//                break;
                //把实名奖加入到该活动发奖记录表
//            case 'collect_card_real_name':
//                if(isset($triggerData['tag']) && $triggerData['tag'] == 'real_name'){
//                    CollectCardService::addRedByRealName($triggerData['user_id']);
//                }
//                break;
            /**快乐大本营集卡活动 end**/
            /**快本欢乐大转盘 start**/
            case 'kb_dazhuanpan_sign_in':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'daylySignin'){
                    //签到加1次抽奖机会
                    Attributes::increment($triggerData['user_id'],"kb_dazhuanpan_drew_user",1);
                }
                break;
            //投资
            case 'kb_dazhuanpan_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //判断是否是6个月以上标
                    if(isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                        if($amount >= 1000){
                            $num = intval($amount/1000);
                            Attributes::increment($triggerData['user_id'],"kb_dazhuanpan_drew_user",$num);
                        }
                    }
                }
                break;
            case 'kb_dazhuanpan_invite_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id']) && $triggerData['is_first']){
                    Attributes::increment($triggerData['from_user_id'],"kb_dazhuanpan_drew_user",1);
                }
                break;
            case 'kb_dazhuanpan_register':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'register'){
                    Attributes::increment($triggerData['user_id'],"kb_dazhuanpan_drew_user",1);
                }
                break;
            /**快本欢乐大转盘 end**/
            /**龙吟虎啸活动 start**/
                //注册
            case 'longyinhuxiao_register':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'register'){
                    Attributes::increment($triggerData['user_id'],"longyinhuxiao_drew_user",1);
                }
                break;
                //签到
            case 'longyinhuxiao_sign_in':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'daylySignin'){
                    //签到加1次抽奖机会
                    Attributes::increment($triggerData['user_id'],"longyinhuxiao_drew_user",1);
                }
                break;
            //投资
            case 'longyinhuxiao_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //判断是否是6个月以上标
                    if(isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                        if($amount >= 1000){
                            $num = intval($amount/1000);
                            Attributes::increment($triggerData['user_id'],"longyinhuxiao_drew_user",$num);
                        }
                    }
                }
                break;
            //邀请人投资
            case 'longyinhuxiao_invite_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])){
                    Attributes::increment($triggerData['from_user_id'],"longyinhuxiao_drew_user",1);
                }
                break;
            /**龙吟虎啸活动 end**/
            /** 网剧活动 start */
            //投资
            case 'network_drama_invest':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if(isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 12 && $amount >= 600){
                        $config = Config::get('networkdrama');
                        //判断是否生产数据
                        $isHas = Attributes::getItem($triggerData['user_id'],$config['key']);
                        if($isHas == false){
                            //不存在就添加
                            Attributes::setItem($triggerData['user_id'],$config['key']);
                        }
                    }
                }
                break;
            /** 网剧活动 end */

            /** 刮刮乐活动 start */
            //投资
            case 'scratch_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //根据标的不同添加抽奖次数
                    Scratch::addScratchNum($triggerData);
                }
                break;
            /** 刮刮乐活动 end */
            
            /** 七月大转盘活动 start */
            //签到
            case 'dazhuanpan_sign_in':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'daylySignin'){
                    //签到加1次抽奖机会
                    Attributes::increment($triggerData['user_id'],"dazhuanpan_drew_user",1);
                }
                break;
            //投资
            case 'dazhuanpan_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //判断是否是6个月以上标
                    if(isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                        if($amount >= 1000){
                            $num = intval($amount/1000)*3;
                            Attributes::increment($triggerData['user_id'],"dazhuanpan_drew_user",$num);
                        }
                    }
                }
                break;
            //邀请人投资
            case 'dazhuanpan_invite_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])){
                    Attributes::increment($triggerData['from_user_id'],"dazhuanpan_drew_user",1);
                }
                break;
            /** 七月大转盘活动 end */

            /** 签到系统活动 start */
            //投资就给该用户添加48小时摇红包时间
            case 'sign_in_system_threshold':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //获取摇一摇新规则的开始时间
                    $newThreshold = GlobalAttributes::getItem("sign_in_system_new_threshold_time");
                    $newThresholdStart = isset($newThreshold['string']) && $newThreshold['string'] != '' ? strtotime($newThreshold['string']) : 0;
                    //判断当前时间是否超过配置时间
                    if($newThresholdStart > 0 && time() > $newThresholdStart){
                        //6个月标才可以加摇一摇时间
                        if((isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6)){
                            $config = Config::get('signinsystem');
                            $expiredTime = time() + 3600 * $config['expired_hour'];
                            Attributes::setItem($triggerData['user_id'],"sign_in_system_threshold",$expiredTime);
                        }
                    }else{
                        $config = Config::get('signinsystem');
                        $expiredTime = time() + 3600 * $config['expired_hour'];
                        Attributes::setItem($triggerData['user_id'],"sign_in_system_threshold",$expiredTime);
                    }

                }
                break;
            //投资给邀请人增加倍数
            case 'sign_in_system_invite_first':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    if(isset($triggerData['is_first']) && $triggerData['is_first'] == 1){
                        Attributes::setNyBiao($triggerData['from_user_id'],'sign_in_system_invite_first',$triggerData['user_id']);
                    }
                }
                break;
            /** 签到系统活动 end */
            /** DIY加息券活动 start */
            //绑卡
            case 'diy_increases_bind_bank_card':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'bind_bank_card' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    $fromUserId = Func::getUserBasicInfo($triggerData['user_id']);
                    DiyIncreasesBasic::_DIYIncreasesAdd($triggerData['user_id'],$fromUserId['from_user_id'],'注册并绑卡',1);
                }
                break;
            //投资
            case 'diy_increases_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id']) && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])){
                    //这里的$num为投资金额
                    $num = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    DiyIncreasesBasic::_DIYIncreasesAdd($triggerData['user_id'],$triggerData['from_user_id'],'投资',$num);
                }
                break;
            /** DIY加息券活动 end */
            /** 网贷天眼首投送积分 start */
            case 'wdty_investment_first':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    self::_wdtyIsSendAward($triggerData);
                }
                break;
            /** 网贷天眼首投送积分 end */
            /** 现金分享 start */
            case 'amount_share_investment':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    $return['amount_share_status'] = AmountShareBasic::amountShareCreate($triggerData);
                }
                break;
            /** 现金分享 end */
            /** 破百亿 start **/
            case 'pobaiyi':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    //非新手标
                    if(!(isset($triggerData['novice_exclusive']) && $triggerData['novice_exclusive'] == 1)){
                        PoBaiYiService::addMoneyByInvestment($triggerData);
                    }
                }
                break;
            /** 破百亿 end **/
            /* 现金宝箱 start */
            case 'treasure_num':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    $num = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']/1000) : 0;
                    if($num > 0 && isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        Attributes::increment($triggerData['user_id'],"treasure_num",$num);
                    }
                }
                break;
            case 'treasure_invite':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['from_user_id']) && !empty($triggerData['from_user_id'])){
                    Attributes::increment($triggerData['from_user_id'],"treasure_num",1);
                }
                break;
            /* 现金宝箱 end */
            /* 投资赢现金 start */
            case 'tzyxj_invest':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount > 0 && isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        TzyxjService::addRecord($triggerData['user_id'], $amount);
                    }
                }
                break;
            /* 投资赢现金 end */
            /**女神月活动*****开始****/
            //投资送次数(满一千送一次)
            case "nvshenyue_invest":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])) {
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $num = intval($amount/1000);
                    if(!empty($num)){
                        NvshenyueService::addChanceByInvest($triggerData['user_id'], $num);
                    }
                }
                break;
            //邀请人首投（给邀请人）
            case "nvshenyue_invite":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    NvshenyueService::addChanceByInvite($triggerData['from_user_id']);
                }
                break;
            /**女神月活动*****结束****/

            /**感恩活动*****开始****/
            //投资送次数(满一千送一次)
            case "ganen_invest":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])) {
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $num = intval($amount/1000);
                    if(!empty($num)){
                        GanenService::addChanceByInvest($triggerData['user_id'], $num);
                    }
                }
                break;
            //邀请人首投（给邀请人）
            case "ganen_invite":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    GanenService::addChanceByInvite($triggerData['from_user_id']);
                }
                break;
            /**感恩活动*****结束****/

            //流量包渠道首投触发
            case "channel_liuliangbao":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $open = new Open();
                    $open->sendNb($triggerData);
                }
                break;

            //摇一摇活动3 门槛
            case "shake_to_shake3_threshold":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    Attributes::increment($triggerData['user_id'],"shake_to_shake3_threshold",1);
                }
                break;
            //摇一摇活动3 邀请好友首投累计
            case "shake_to_shake3_invite_first":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    if(isset($triggerData['is_first']) && $triggerData['is_first'] == 1){
                        Attributes::setNyBiao($triggerData['from_user_id'],'shake_to_shake3_invite_first',$triggerData['user_id']);
                    }
                }
                break;

            //投资是否满足投资6个月的标，且投资金额大于等于1000元
            case "shake_to_shake_6_1000":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount >= 1000 && isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 6){
                        Attributes::increment($triggerData['user_id'],"shake_to_shake_is_satisfy",1);
                    }
                }
                break;
            //摇一摇活动2
            case "shake_to_shake2_invite_first":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    if(isset($triggerData['is_first']) && $triggerData['is_first'] == 1){
                        Attributes::setNyBiao($triggerData['from_user_id'],'shake_to_shake2_invite_first',$triggerData['user_id']);
                    }
                }
                break;
            //摇一摇活动
            case "shake_to_shake_invite_first":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    if(isset($triggerData['is_first']) && $triggerData['is_first'] == 1){
                        Attributes::setNyBiao($triggerData['from_user_id'],'shake_to_shake_invite_first',$triggerData['user_id']);
                    }
                }
                break;
            //*********新春嘉年华活动****START****//
            // 记录最大投资额
            case "xjdb_max_invest":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount >= 0){
                        Attributes::setNumberByMax($triggerData['user_id'], 'xjdb_max_invest', $amount);
                    }
                }
                break;
            //金牌投手奖
            case "new_year_bidding" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['project_id'])){
                    Attributes::setNyBiao($triggerData['user_id'],'new_year_bidding',$triggerData['project_id']);
                }
                break;
            //推广贡献奖
            case "new_year_invite_investment" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id']) && !empty($triggerData['from_user_id'])){
                    $yearInvestment = self::yearInvestment($triggerData['Investment_amount'],$triggerData['scatter_type'],$triggerData['period']);
                    if($yearInvestment){
                        Attributes::setNyExtension($triggerData['from_user_id'],'new_year_invite_investment',$yearInvestment,$triggerData['is_first']);
                    }
                }
                break;
            //蛋无虚发获取锤子
            case "new_year_hammer_num" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && !empty($triggerData['user_id'])){
                    $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount >= 1000){
                        $num = intval($amount/1000);
                        Attributes::increment($triggerData['user_id'],'new_year_hammer_num',$num);
                        //发送站内信
                        if($num >= 1){
                            $template = "恭喜您在新春嘉年华活动中获取".$num."把锤子，请于活动期间在活动页面使用。";
                            SendMessage::Mail($triggerData['user_id'],$template);
                        }
                    }
                }
                break;
            //统计年化
            case "new_year_year_investment" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $yearInvestment = self::yearInvestment($triggerData['Investment_amount'],$triggerData['scatter_type'],$triggerData['period']);
                    if($yearInvestment){
                        Attributes::increment($triggerData['user_id'],'new_year_year_investment',$yearInvestment);
                    }
                }
                break;
            //*********新春嘉年华活动****END****//

            //邀请人首次投资
            case "invite_investment_first" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $data = $Attributes->setSd1Number("invite_investment_first","invite_investment",$triggerData['user_id'],$triggerData['from_user_id']);
                    if($data['inviteNum'] == 3){
                        //获取种树状态
                        $christmasClick = UserAttribute::where('key','sd_tree_status')->where('user_id',$triggerData['from_user_id'])->first();
                        if(isset($christmasClick['number']) && $christmasClick['number'] == 1){
                            //发奖
                            self::ActiveSendAward($triggerData['from_user_id'],'invite_investment_first_send');
                        }
                    }
                }
                break;
            //邀请人连续投资
            case "invite_investment" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $data = $Attributes->setSd2Number("invite_investment",$triggerData['user_id'],$triggerData['from_user_id']);
                    if($data['inviteNum'] == 2){
                        //获取种树状态
                        $christmasClick = UserAttribute::where('key','sd_tree_status')->where('user_id',$triggerData['from_user_id'])->first();
                        if(isset($christmasClick['number']) && $christmasClick['number'] == 1){
                            //发奖
                            self::ActiveSendAward($triggerData['from_user_id'],'invite_investment_send');
                        }
                    }
                }
                break;
            //算年化投资5w
            case "year_investment_50000" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    //得到年化
                    $yearInvestment = self::yearInvestment($triggerData['Investment_amount'],$triggerData['scatter_type'],$triggerData['period']);
                    if(!$yearInvestment){
                        $return = array('model'=>'Christmas','status'=>false);
                    }else{
                        //调用接口计算累计年化
                        $amount = Attributes::increment($triggerData['user_id'],"year_investment_50000",$yearInvestment);
                        if($amount < 50000){
                            $return = array('model'=>'Christmas','status'=>false);
                        }else{
                            //获取种树状态
                            $christmasClick = UserAttribute::where('key','sd_tree_status')->where('user_id',$triggerData['user_id'])->first();
                            if(isset($christmasClick['number']) && $christmasClick['number'] == 1){
                                //发奖
                                self::ActiveSendAward($triggerData['user_id'],'year_investment_50000_send');
                            }
                        }
                    }
                }
                break;
            //算年化投资10w
            case "year_investment_100000" :
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    //得到年化
                    $yearInvestment = self::yearInvestment($triggerData['Investment_amount'],$triggerData['scatter_type'],$triggerData['period']);
                    if(!$yearInvestment){
                        $return = array('model'=>'Christmas','status'=>false);
                    }else{
                        //调用接口计算累计年化
                        $amount = Attributes::increment($triggerData['user_id'],"year_investment_100000",$yearInvestment);
                        if($amount < 100000){
                            $return = array('model'=>'Christmas','status'=>false);
                        }else{
                            //获取种树状态
                            $christmasClick = UserAttribute::where('key','sd_tree_status')->where('user_id',$triggerData['user_id'])->first();
                            if(isset($christmasClick['number']) && $christmasClick['number'] == 1){
                                //发奖
                                self::ActiveSendAward($triggerData['user_id'],'year_investment_100000_send');
                            }
                        }
                    }
                }
                break;
            //投资12月及以上标送直抵红包2017.11.6-11.17 start
            case"investment_send_zdhb":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment' && isset($triggerData['user_id']) && !empty($triggerData['user_id'])){
                    //判断是否是12个月以上标
                    if(isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 && isset($triggerData['period']) && $triggerData['period'] >= 12){
                        $amount = isset($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                        $nowtimeStamp = time();
                        if($nowtimeStamp >= strtotime($activityInfo['start_at']) && $nowtimeStamp <= strtotime($activityInfo['end_at'])){
                            $num = bcmul($amount,0.005,2);
                            //直抵红包相关参数
                            $info = [
                                'id'=>0,
                                'user_id'=>$triggerData['user_id'],
                                'source_id'=>$activityInfo['id'],
                                'name'=>$num."元直抵红包",
                                'source_name'=>'清空购物车',
                                'red_money'=>$num,
                                'effective_time_type'=>2,
                                'effective_time_start'=>date('Y-m-d H:i:s'),
                                'effective_time_end'=>'2017-11-18 00:00:00',
                                'investment_threshold'=>0,
                                'project_duration_type'=>1,
                                'product_id'=>'',
                                'project_type'=>null,
                                'platform_type'=>0,
                                'limit_desc'=>null,
                                'trigger'=>null,
                                'mail'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}"
                            ];
                            self::redMoney($info);
                        }
                    }
                }
                break;
            //投资12月及以上标送直抵红包2017.11.6-11.17 end
            //实名送100M流量
            case "gdyidong_flow_100M":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'real_name'){
                    $awards['user_id'] = $triggerData['user_id'];
                    $awards['source_id'] = $activityInfo['id'];
                    $awards['name'] = '100M流量';
                    $awards['source_name'] = $activityInfo['name'];
                    $awards['spec'] = 100;
                    $return = self::sendFlow($awards);
                }
                break;
            //首次投资2000送500M流量
            case "gdyidong_flow_500M":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $awards['user_id'] = $triggerData['user_id'];
                    $awards['source_id'] = $activityInfo['id'];
                    $awards['name'] = '500M流量';
                    $awards['source_name'] = $activityInfo['name'];
                    $awards['spec'] = 500;
                    $return = self::sendFlow($awards);
                }
                break;
            //首次投资5000送1GB流量
            case "gdyidong_flow_1024M":
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $awards['user_id'] = $triggerData['user_id'];
                    $awards['source_id'] = $activityInfo['id'];
                    $awards['name'] = '1GB流量';
                    $awards['source_name'] = $activityInfo['name'];
                    $awards['spec'] = 1024;
                    $return = self::sendFlow($awards);
                }
                break;
            //一元购按投资金额送抽奖参与次数
            case Config::get('activity.one_yuan.alias_name'):
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    if($amount >= 1000){
                        $investmentNum = Config::get('activity.one_yuan.investment_num');
                        foreach($investmentNum as $num =>$value){
                            if($amount >= $value['min'] && $amount <= $value['max'] && !empty($triggerData['user_id'])){
                                //给用户加次数
                                OneYuanBasic::addNum($triggerData['user_id'],$num,'investment',array('investment'=>$amount));
                            }
                        }
                    }
                }
                break;
            //积分商城按投资金额送积分
            case 'investment_to_integral':
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $userBase = Func::globalUserBasicInfo($triggerData['user_id']);
                    if(isset($userBase['result']['data']) && !empty($userBase['result']['data']) && isset($userBase['result']['data']['level'])){
                        $level = $userBase['result']['data']['level'] <= 0 ? 1 : $userBase['result']['data']['level'];
                    }
                    $amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    $period = isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 ? $triggerData['period'] : 1;
                    $integral = ($amount/100)*$level*$period;
                    if(empty($integral) || !isset($triggerData['name']) || !isset($triggerData['short_name'])){
                        return false;
                    }
                    $info = array();
                    $info['user_id'] = $triggerData['user_id'];
                    $info['trigger'] = 4;
                    $info['activity_id'] = isset($activityInfo['id']) ? $activityInfo['id'] : 0;
                    $info['source_name'] = "投资";
                    $info['integral'] = $integral;
                    $info['remark'] = "标的：".$triggerData['name'].$triggerData['short_name']." 投资金额 ".$triggerData['Investment_amount']."元";
                    self::integralSend($info);
                }
                break;
            //双十一抱团取暖活动
            case Config::get('activity.double_eleven.baotuan'):
                $probability =  Config::get('activity.double_eleven.baotuan_probability');
                $rand = rand(1, $probability);
                if($rand === 1){
                    $url = env('INSIDE_HTTP_URL');
                    $client = new JsonRpcClient($url);
                    $userBase = $client->userBasicInfo(array('userId' =>$triggerData['user_id']));
                    $phone = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : '';
                    if(!empty($phone)) {
                        $res = $Attributes::setText($triggerData['user_id'], Config::get('activity.double_eleven.baotuan'), $phone);
                    }
                }
                break;
            //双十一大转盘送次数
            case Config::get('activity.double_eleven.chance1'):
                $alias = Config::get('activity.double_eleven.key2');
                $Attributes::increment($triggerData['user_id'], $alias);
                break;
            case Config::get('activity.double_eleven.chance2'):
                $alias = Config::get('activity.double_eleven.key3');
                $Attributes::increment($triggerData['user_id'], $alias);
                break;
            //按照充值金额送比例体验金
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
                    $awards['trigger'] = isset($activityInfo['trigger_type']) ? $activityInfo['trigger_type'] : '-1';
                    $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
                    $return = self::experience($awards);
                }
                break;
            //当天天标累计投资
            case 'cast_tianbiao':
                //如果是投资触发
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $investment_amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    //金额不不能为空
                    if(empty($investment_amount)){
                        return $return;
                    }
                    $Attributes->increment($triggerData['user_id'],$activityInfo['alias_name'].'_'.date('ymd'),$investment_amount);
                }
                break;
            //当天月标累计投资
            case 'cast_yuebiao':
                //如果是投资触发
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $investment_amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    //金额不不能为空
                    if(empty($investment_amount)){
                        return $return;
                    }
                    $Attributes->increment($triggerData['user_id'],$activityInfo['alias_name'].'_'.date('ymd'),$investment_amount);
                }
                break;
            //9月30日闯关状态修改
            case 'gq_0930':
                $Attributes->status($triggerData['user_id'],'cg_success','0930:1');
                break;
            //10月01日闯关状态修改
            case 'gq_1001':
                //如果是投资触发
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $investment_amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    //金额不小于2万不发奖
                    if(empty($investment_amount) || $investment_amount < 20000){
                        return $return;
                    }
                    $awards['id'] = 0;
                    $awards['user_id'] = $triggerData['user_id'];
                    $awards['source_id'] = $activityInfo['id'];
                    $awards['name'] = $investment_amount.'元体验金';
                    $awards['source_name'] = $activityInfo['name'];
                    $awards['experience_amount_money'] = $investment_amount;
                    $awards['effective_time_type'] = 2;
                    $awards['effective_time_start'] = "2016-10-01 00:00:00";
                    $awards['effective_time_end'] = "2016-10-15 23:59:59";
                    $awards['platform_type'] = 0;
                    $awards['limit_desc'] = '';
                    $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
                    $return = self::experience($awards);
                }
                $Attributes->status($triggerData['user_id'],'cg_success','1001:1');
                break;
            //10月02日闯关状态修改
            case 'gq_1002':
                $Attributes->status($triggerData['user_id'],'cg_success','1002:1');
                break;
            //10月03日邀请人累计投资
            case 'gq_1003':
                //如果是邀请人投资触发
                if(isset($triggerData['tag']) && !empty($triggerData['tag']) && $triggerData['tag'] == 'investment'){
                    $investment_amount = isset($triggerData['Investment_amount']) && !empty($triggerData['Investment_amount']) ? intval($triggerData['Investment_amount']) : 0;
                    //金额不不能为空
                    if(empty($investment_amount)){
                        return $return;
                    }
                    $return['investment'] = $Attributes->increment($triggerData['user_id'],$activityInfo['alias_name'].'_'.date('ymd'),$investment_amount);
                }
                break;
            //10月04日闯关状态修改
            case 'gq_1004':
                $Attributes->status($triggerData['user_id'],'cg_success','1004:1');
                break;
            //10月05日闯关状态修改
            case 'gq_1005':
                $Attributes->status($triggerData['user_id'],'cg_success','1005:1');
                break;
            //10月06日闯关状态修改
            case 'gq_1006':
                $Attributes->status($triggerData['user_id'],'cg_success','1006:1');
                break;
            //10月07日闯关状态修改
            case 'gq_1007':
                $Attributes->status($triggerData['user_id'],'cg_success','1007:1');
                break;
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
    static function addAwardByActivity($userId, $activityId,$triggerData = array()) {
        $activity = Activity::where('id', $activityId)->with('awards')->first();
        //判断原生之罪的实名获奖上限600
        if(isset($activity['alias_name']) && $activity['alias_name'] == 'original_sin_real_name_limit'){
            $status = self::originalSinLimit('original_sin_real_name_limit');
            if($status === false){
                return [];
            }
        }
        //判断原生之罪的首投获奖上限288
        if(isset($activity['alias_name']) && $activity['alias_name'] == 'original_sin_investment_limit'){
            $status = self::originalSinLimit('original_sin_investment_limit');
            if($status === false){
                return [];
            }
        }

        if(isset($activity['alias_name']) && $activity['alias_name'] == 'channel_hstvbkshy'){
            $status = self::originalEveryNumLimit('channel_hstvbkshy');
            if($status === false){
                return [];
            }
        }
        $awards = $activity['awards'];
        $res = [];
        if($activity['award_rule'] == 1) {
            foreach($awards as $award) {
                $res[] = Self::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'],'',0,0,$triggerData);
            }
        }
        if($activity['award_rule'] == 2) {
            $awards = $activity['awards'];
            $finalAward = null;
            $priority = 0;
            foreach($awards as $award) {
                $priority += $award['priority'];
            }

            $target = rand(1, $priority);
            foreach($awards as $award) {
                $target = $target - $award['priority'];
                if($target <= 0) {
                    $finalAward = $award;
                    break;
                }
            }

            if($finalAward) {
                $res[] = Self::sendDataRole($userId, $award['award_type'], $finalAward['award_id'], $activity['id'],'',0,0, $triggerData);
            }
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
    static function addAwardToInvite($userId, $activityId,$triggerData = array()) {
        $activity = Activity::where('id', $activityId)->with('award_invite')->first();
        $awardInvite = $activity['award_invite'];
        $res = [];
        //判断用户是否超过邀请发奖次数200
        if(isset($activity['alias_name']) && $activity['alias_name'] == 'invite_send_award_limit'){
            $status = self::inviteNumLimit($userId);
            if($status === false){
                return [];
            }
        }
        //邀请好友2.0判断用户是否超过邀请发奖次数1
        if(isset($activity['alias_name']) && $activity['alias_name'] == 'invite_send_award_limit2'){
            $status = self::inviteNumLimit2($userId);
            if($status === false){
                return [];
            }
        }

        foreach($awardInvite as $award) {
            $res[] = Self::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'] ,'',0,0,$triggerData);
        }
        return $res;
    }
    /**
     * @需要提出去
     * @param $userID ，$award_type,$award_id
     *
     */
    static function inviteNumLimit($userId){
        if($userId < 0){
            return false;
        }
        $num = Attributes::increment($userId,'invite_send_award_limit',1);
        $limit = Config::get("activity.invite_send_award_limit");
        if($num == $limit+1){
            $message = "系统检测到您可能正通过技术手段获取体验金奖励，故不继续发放邀请注册体验金，其他邀请奖励不受影响，如有疑问请联系客服，感谢您对网利宝的支持！";
            //发送站内信
            SendMessage::Mail($userId,$message,[]);
            //发送短信
            SendMessage::Message($userId,$message,[]);
            return false;
        }
        //不发奖
        if($num > $limit){
            return false;
        }
        return true;
    }
    /**
     * 邀请好友2.0限制
     * @param $userID
     *
     */
    static function inviteNumLimit2($userId){
        if($userId < 0){
            return false;
        }
        $num = Attributes::incrementByDay($userId,'invite_send_award_limit2',1);
        $limit = Config::get("activity.invite_send_award_limit2");
        //不发奖
        if($num > $limit){
            return false;
        }
        return true;
    }
    /**
     * 原生之罪发奖限制
     * @param $userID
     *
     */
    static function originalSinLimit($limitName){
        if(empty($limitName)){
            return false;
        }
        $num = GlobalAttributes::incrementByDay($limitName,1);
        $limit = Config::get("activity.".$limitName);
        //不发奖
        if($num > $limit){
            return false;
        }
        return true;
    }

    /**
     * 每日数量限制
     * @param $userID
     *
     */
    static function originalEveryNumLimit($limitName){
        if(empty($limitName)){
            return false;
        }
        $attr = GlobalAttributes::getItem($limitName . '_limit');
        if (!$attr) {
            return false;
        }
        $limit = $attr->number;
        $num = GlobalAttributes::incrementByDay($limitName,1);
        //不发奖
        if($num > $limit){
            return false;
        }
        return true;
    }
    /**
     * @需要提出去
     * @param $userID ，$award_type,$award_id
     *
     */
    static function sendDataRole($userID,$award_type, $award_id, $activityID = 0, $sourceName = '',$batch_id = 0,$unSendID = 0,$triggerData = array())
    {
        self::$userID = $userID;
        self::$activityID = $activityID;
        //获取数据
        $table = self::_getAwardTable($award_type);
        if($award_type == 6){
            $info = $table::where('id', $award_id)->where('is_del', 0)->select()->get()->toArray();
        }else{
            $info = $table::where('id', $award_id)->select()->get()->toArray();
        }

        if(empty($info)){
            return '奖品不存在';
        }
        if(count($info) >= 1){
            $info = $info[0];
        }
        //来源id
        $info['source_id'] = $activityID;
        //获取出活动的名称
        $activity = Activity::where('id',$activityID)->select('name','trigger_type', 'alias_name')->first();
        //来源名称
        $info['source_name'] = isset($activity['name']) ? $activity['name'] : $sourceName;
        //来源活动别名
        $info['alias_name'] = isset($activity['alias_name']) ? $activity['alias_name'] : $sourceName;
        //触发类型
        $info['trigger'] = isset($activity['trigger_type']) ? $activity['trigger_type'] : -1;
        //用户id
        $info['user_id'] = $userID;
        //批次id
        if(!empty($batch_id)){
            $info['batch_id'] = $batch_id;
        }
        //补发id
        if(!empty($unSendID)){
            $info['unSendID'] = $unSendID;
        }
        if ($award_type == 1) {
            //加息券
            return self::increases($info);
        } elseif ($award_type == 2) {
            if ($info['red_type'] == 1 || $info['red_type'] == 3) {
                //直抵红包
                return self::redMoney($info);
            } elseif ($info['red_type'] == 2){
                //百分比红包
                return self::redMaxMoney($info);
            }
        } elseif ($award_type == 3) {
            //体验金
            //如果是注册触发就添加一个下级id，刘奇那边全民淘金用到
            if(isset($triggerData['child_user_id'])){
                $info['child_user_id'] = $triggerData['child_user_id'];
            }
            return self::experience($info);
        } elseif ($award_type == 4) {
            //用户积分
            return self::integral($info,$triggerData);
        } elseif ($award_type == 6) {
            //优惠券
            return self::coupon($info);
        } elseif ($award_type == 7) {
            //现金
            return self::cash($info);
        }
    }
    //加息券
    static function increases($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 1;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:0',
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
        $data['trigger'] = $info['trigger'];
        $data['remark'] = '';
        $data['award_id'] = $info['id'];//奖品id
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->interestCoupon($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志
                $arr = array(
                    'award_id'=>$info['id'],
                    'award_name'=>$info['name'],
                    'award_type'=>$info['award_type'],
                    'effective_start'=>$data['effective_start'],
                    'effective_end'=>$data['effective_end'],
                    'uuid'=>$uuid,
                    'status'=>true
                );
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array(
                    'award_id'=>$info['id'],
                    'award_name'=>$info['name'],
                    'award_type'=>$info['award_type'],
                    'effective_start'=>$data['effective_start'],
                    'effective_end'=>$data['effective_end'],
                    'uuid'=>$uuid,
                    'status'=>false,
                    'err_msg'=>'send_fail',
                    'err_data'=>$result,
                    'url'=>$url
                );
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
            'id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|numeric|min:0',
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

        $data['trigger'] = $info['trigger'];

        if(isset($info['red_type']) && $info['red_type'] == 3){
            $data['is_novice'] = 1;
        }

        $data['remark'] = '';
        $data['award_id'] = $info['id'];//奖品id
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志&发送消息
                $arr = array(
                    'award_id'=>$info['id'],
                    'award_name'=>$info['name'],
                    'award_type'=>$info['award_type'],
                    'effective_start'=>$data['effective_start'],
                    'effective_end'=>$data['effective_end'],
                    'uuid'=> $data['uuid'],
                    'status'=>true
                );
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array(
                    'award_id'=>$info['id'],
                    'award_name'=>$info['name'],
                    'award_type'=>$info['award_type'],
                    'effective_start'=>$data['effective_start'],
                    'effective_end'=>$data['effective_end'],
                    'uuid'=> $data['uuid'],
                    'status'=>false,
                    'err_msg'=>'send_fail',
                    'err_data'=>$result,
                    'url'=>$url
                );
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
        $data['trigger'] = $info['trigger'];
        $data['remark'] = '';
        $data['award_id'] = $info['id'];//奖品id
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志&发送消息
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'uuid'=>$uuid,'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'uuid'=>$uuid,'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
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
        //如果是注册触发就添加一个下级id，刘奇那边全民淘金用到
        if(isset($info['child_user_id'])){
            $data['child_user_id'] = $info['child_user_id'];
        }
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
        $data['trigger'] = $info['trigger'];
        $data['remark'] = '';
        $data['award_id'] = $info['id'];//奖品id
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->experience($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //发送消息&存储日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'uuid'=>$uuid,'status'=>true,'child_user_id' => isset($data['child_user_id']) ? $data['child_user_id'] : '');
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'uuid'=>$uuid,'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url,'child_user_id' => isset($data['child_user_id']) ? $data['child_user_id'] : '');
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //用户积分
    static public function integral($info,$triggerData){
        //添加info里添加日志需要的参数
        $info['award_type'] = 4;
        $info['uuid'] = null;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'source_name' => 'required|min:2|max:255',
            'integral' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>3,'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //用户积分
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = isset($info['source_id']) ? $info['source_id'] : 0;
        $data['source_name'] = $info['source_name'];
        $data['integral'] = $info['integral'];
        if(isset($triggerData['tag']) && $triggerData['tag'] == 'investment'){
            $remark = "标的：".$triggerData['name'].$triggerData['short_name']." 投资金额 ".$triggerData['Investment_amount']."元";
        }else{
            $remark = "活动赠送";
        }
        $data['remark'] = $remark;
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->integralIncrease($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //发送消息&存储日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'uuid'=>$uuid,'status'=>true);
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','uuid'=>$uuid,'err_data'=>$result,'url'=>$url);
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
        //事物开始
        DB::beginTransaction();

        //根据id获取出可用的优惠卷
        $where = array();
        $where['coupon_id'] = $info['id'];
        $where['is_use'] = 0;
        $data = CouponCode::where($where)->lockForUpdate()->first();
        if (!empty($data) && isset($data['code']) && !empty($data['code']) && isset($data['id']) && !empty($data['id'])) {
            //发送消息
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>6,'status'=>true,'code'=>$data['code']);
            $info['code'] = $data['code'];
            $info['remark'] = json_encode($err);
            $info['status'] = 1;
            self::sendMessage($info);
            //修改优惠码状态
            CouponCode::where("id",$data['id'])->update(array('is_use'=>1));
            DB::commit();
            //奖品预警
            $num = CouponCode::where($where)->count();
            if($num <= 50){
                Func::earlyWarning($num,$info['name'],$info['id']);
            }
            return $err;
        }else{
            //存储到日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>6,'status'=>false,'err_msg'=>'coupon_empty');
            $info['remark'] = json_encode($err);
            self::addLog($info);
            DB::commit();
            return $err;
        }

    }
    //用户现金
    static public function cash($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 7;
        $info['uuid'] = null;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'source_name' => 'required|min:2|max:255',
            'money' => 'required|numeric|min:0.01',
            'type' => 'required|min:1|max:64',
        ]);
        if($validator->fails()){
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $uuid = self::create_guid();
        //发送接口
        $result = Func::incrementAvailable($info['user_id'],$info['id'],$uuid,$info['money'],$info['type']);
        //发送消息&存储到日志
        if (isset($result['result']['code']) && $result['result']['code'] == 0) {//成功
            //发送消息&存储日志
            $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
            $info['status'] = 1;
            $info['uuid'] = $uuid;
            $info['remark'] = json_encode($arr);
            self::sendMessage($info);
            return $arr;
        }else{//失败
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result);
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

    //由基础信息 获取到用户尊称
    static function setUserRespectedName($userBasicInfo){
        $respected = '';
        if(isset($userBasicInfo['realname']) && !empty($userBasicInfo['realname']) )
            $last_name = mb_substr($userBasicInfo['realname'] ,0,1,'utf-8');
        if(isset($userBasicInfo['gender']) && $userBasicInfo['gender'] != 0 ){
            $gender = $userBasicInfo['gender'] == 1?'先生':'女士';
        }
        //如果都存在，生成尊称
        if(isset($last_name) && isset($gender)){
            $respected = $last_name.$gender;
        }else{
            $respected = '网利宝用户';
        }
        return $respected;
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
        $message['money'] = isset($info['money']) ? $info['money'] : 0;

        $userBasicInfo = Func::getUserBasicInfo($info['user_id']);//获取用户基本信息
        $message['respecteduname'] = self::setUserRespectedName($userBasicInfo);//用户尊称key：respecteduname
        $return = array();
        $info['message_status'] = 0;
        $info['mail_status'] = 0;
        if(!empty($info['message'])){
            //发送短信
            $return['message'] = self::messageNodeName($info, $message);
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
        $data['award_id'] = isset($info['id']) ? $info['id'] : 0;
        $data['status'] = $info['status'];
        $data['coupon_code'] = isset($info['code']) ? $info['code'] : '';
        $data['message_status'] = isset($info['message_status']) ? $info['message_status'] : '';
        $data['mail_status'] = isset($info['mail_status']) ? $info['mail_status'] : '';
        $data['created_at'] = date("Y-m-d H:i:s");
        if(!empty($info['unSendID'])){
            if(empty($info['status'])){
                //取出失败的信息和新的错误信息合并
                $remark = $SendRewardLog->where('id',$info['unSendID'])->select('remark')->get()->toArray();
                $remark = isset($remark[0]['remark']) && !empty($remark[0]['remark']) ? $remark[0]['remark'] : array();
                if(!empty($remark)){
                    $unRemark = '['.$remark.','.$info['remark'].']';
                }else{
                    $unRemark = $info['remark'];
                }
                $SendRewardLog->where('id',$info['unSendID'])->update(array('remark'=>$unRemark,'updated_at'=>date("Y-m-d H:i:s")));
                return true;
            }
            //修改为补发成功状态
            $SendRewardLog->where('id',$info['unSendID'])->update(array('status'=>'2','remark'=>$info['remark'],'updated_at'=>date("Y-m-d H:i:s")));
            return true;
        }
        if(isset($info['batch_id']) && !empty($info['batch_id'])){
            $data['batch_id'] = $info['batch_id'];
        }
        $insertID = $SendRewardLog->insertGetId($data);
        return $insertID;
    }
    /**
     * 用户积分公共接口
     */
    static public function integralSend($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 4;
        $info['uuid'] = null;
        $info['status'] = 0;
        $info['id'] = 0;
        $info['source_id'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'source_name' => 'required|min:2|max:255',
            'trigger'=>'required|integer|min:0',
            'integral' => 'required|integer|min:1',
            'remark' => 'required|min:1'
        ]);
        if($validator->fails()){
            $err = array('award_type'=>4,'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //用户积分
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = isset($info['activity_id']) ? $info['activity_id'] : 0;
        $data['source_name'] = $info['source_name'];
        $data['integral'] = $info['integral'];
        $data['remark'] = $info['remark'];
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->integralIncrease($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //发送消息&存储日志
                $arr = array('award_type'=>$info['award_type'],'status'=>true);
                $info['name'] = $info['integral']."积分";
                $info['status'] = 1;
                $info['uuid'] = $uuid;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $err = array('award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    /**
     * 根据奖品类型和奖品id获取奖品信息
     */
    static function _getAwardInfo($award_type,$award_id){
        $return = array();
        if(empty(intval($award_type)) || empty(intval($award_id))){
            return $return;
        }
        //获取数据
        $table = self::_getAwardTable($award_type);
        $info = $table::where('id', $award_id)->select()->get()->toArray();
        $return = isset($info[0]) ? $info[0] : array();
        return $return;
    }
    /**
     * 发送流量
     */
    static function sendFlow($info){
        $info['award_type'] = 7;
        $info['uuid'] = null;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'name' => 'required|min:2|max:255',
            'source_id' => 'required|min:1',
            'source_name' => 'required|min:2|max:255',
            'spec' => 'required|min:2|max:255'
        ]);
        if($validator->fails()){
            $err = array('award_type'=>4,'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $return = array();
        if(empty(intval($info['user_id'])) || empty(intval($info['spec']))){
            return $return;
        }
        $param = array();
        $param['user_id'] = $info['user_id'];
        $param['spec'] = $info['spec'];
        //获取数据
        $status = Flow::buyFlow($param);
        if(isset($status['send'])  && $status['send'] === true){
            //发送消息&存储日志
            $arr = array('award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
            $info['status'] = 1;
            $info['remark'] = json_encode($arr);
            self::addLog($info);
            return $arr;
        }
        //存储到日志
        $err = array('award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>$status);
        $info['remark'] = json_encode($err);
        self::addLog($info);
        return $err;
    }
    static function yearInvestment($amount,$type,$time){
        if(empty($amount) || empty($type) || empty($time)){
            return false;
        }
        $yearMoney = 0;
        if($type == 1){
            $yearMoney = ceil(($amount*$time)/360);
        }else if ($type == 2){
            $yearMoney = ceil(($amount*$time)/12);
        }
        return $yearMoney;
    }

    /**
     * 网贷天眼首投送积分活动是否发奖
     * @param $triggerData
     * @return bool
     */
    static function _wdtyIsSendAward($triggerData){
        $wdtyConfig = config::get("wdty");
        if(empty($wdtyConfig)){
            return false;
        }
        $money = isset($triggerData['Investment_amount']) ? $triggerData['Investment_amount'] : 0;
        if($money < 1000){
            return false;
        }
        //判断是否超过
        $globalKey = $wdtyConfig['alias_name']."_".date("Ymd");
        $totalIntegral = GlobalAttributes::getItem($globalKey);
        if(isset($totalIntegral['number']) && $totalIntegral['number']>= $wdtyConfig['max_integral']){
            return false;
        }
        $integral = 0;
        foreach($wdtyConfig['integral_list'] as $key => $item){
            if($money >= $item['min'] && $money <= $item['max']){
                $integral = $key;
            }
        }
        if($integral <= 0){
            return false;
        }
        //发奖
        $status = self::ActiveSendAward($triggerData['user_id'],'wdty_'.$integral);
        //如果发奖成功才累加
        if($status[0]['status'] === true){
            //累加
            GlobalAttributes::increment($globalKey,$integral);
            return true;
        }
        return true;
    }

    /**
     * 根据活动别名判断短信的node_name
     * @param $info
     * @param $message
     * @return bool
     */
    public static function messageNodeName($info, $message)
    {
        if (in_array($info['alias_name'], ['channel_cibn'])) {
            $return = SendMessage::MessageByNode($info['user_id'],'cibn_carnival',['password'=>$message['code']]);
        } else if (in_array($info['alias_name'], ['channel_hstvbkshy'])) {
            $return = SendMessage::MessageByNode($info['user_id'],'huashu_tv_jianianhua',['card'=>$message['code']]);
        } else if (in_array($info['alias_name'], ['original_sin_real_name_limit'])) {
            $return = SendMessage::MessageByNode($info['user_id'],'original_sin_iqiyi',['password'=>$message['code']]);
        } else if (in_array($info['alias_name'], ['original_sin_investment_limit'])) {
            $return = SendMessage::MessageByNode($info['user_id'],'original_sin_fifty_jd',['password'=>$message['code']]);
        } else {
            $return = SendMessage::Message($info['user_id'],$info['message'],$message);
        }
        return $return;
    }
}
