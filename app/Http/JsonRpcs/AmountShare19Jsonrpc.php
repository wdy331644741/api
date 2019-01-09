<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\GlobalAttribute;
use App\Models\UserAttribute;
use App\Service\ActivityService;
use App\Service\AmountShareBasic;
use App\Models\HdAmountShareMasterNew;
use App\Models\HdAmountShareMasterNewInfo;
use App\Service\Func;
use DB, Request;

class AmountShare19JsonRpc extends JsonRpc
{

    /*-------------------春节红包分享-------------------*/


    /**
     *  生成分享链接
     *
     * @JsonRpcMethod
     */

    public function createShareUrl($params){
        global $userId;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

    }

    /**
     *  领取分享现金
     *
     * @JsonRpcMethod
     */
    public function receiveAmount($params){
        global $userId;
        $userId = 2250796;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $fromUserId = $params->from_user_id;
        if(empty($fromUserId)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $actInfo = ActivityService::GetActivityedInfoByAlias('19amountshare_send');
        if($actInfo['end_at'] < date('Y-m-d H:i:s')){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //活动全局配置
        $actGlobalConf = GlobalAttribute::where('key','19amountshare')->value('text');
        $confData = json_decode($actGlobalConf,true);
        $userInfo = Func::getUserBasicInfo($userId,true);
        $userType = self::getuserType($userInfo,$actInfo);
        //用户类型
        $sendAmount = self::getSendAmount($userId,$userType,$confData);
echo 1111;exit;

    }


    /**
     *  领取红包记录
     *
     * @JsonRpcMethod
     */
    public function receiveLog($params){

    }


    //获取用户类型
    private function getuserType($userInfo,$actInfo){
        $userType = null;
        if($userInfo['create_time'] < $actInfo['start_at'] && $userInfo['open_status'] == 1){
            $userType = 2;
        }elseif ($userInfo['create_time'] < $actInfo['start_at'] && $userInfo['open_status'] == 1){
            $userType = 3;
        }elseif($userInfo['create_time'] >= $actInfo['start_at']){
            $userType = 1;
        }
        return $userType;
    }

    //获取用户发奖金额
    private function getSendAmount($userId,$userType,$confData){
        //单日实际总成本
        $allcost_byday = GlobalAttribute::where('key','allcost_byday')->whereRaw(" to_days(created_at) = to_days(now())")->value('string');
        if(!$allcost_byday){
            $allcost_byday = 0;
        }
        if ($allcost_byday && $allcost_byday >= $confData['allcost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }
        $userCost_byday = UserAttribute::where(['key'=>'usercost_byday','user_id'=>$userId])->whereRaw(" to_days(created_at) = to_days(now())")->value('string');
        if(!$userCost_byday){
            $userCost_byday = 0;
        }
        if ($userCost_byday && $userCost_byday >= $confData['usercost_byday']){
            throw new OmgException(OmgException::TODAY_REDPACK_IS_NULL);
        }

        //获取当天用户最高还能领取的红包金额
        $userRestToday = null;
        $restCost = bcsub($confData['allcost_byday'],$allcost_byday,2);
        $user_restCost = bcsub($confData['usercost_byday'],$userCost_byday,2);
        if(bccomp($restCost,$user_restCost,2) == 1){
            $userRestToday = $user_restCost;
        }elseif (bccomp($restCost,$user_restCost,2) == -1){
            $userRestToday = $restCost;
        }else{
            $userRestToday = $restCost;
        }
        $sendAmount = 0;
        switch ($userType){
            case 1:
                //实际的新用户名次
                $realTopNum = GlobalAttribute::where('key','top_num')->whereRaw(" to_days(created_at) = to_days(now())")->value('number');
                if($realTopNum <= $confData['top_num']){
                    if(bccomp($userRestToday,$confData['top_onehundred']['max'],2) >= 0){
                        $sendAmount = mt_rand($confData['top_onehundred']['min'] * 100,$confData['top_onehundred']['max'] * 100);
                    }elseif(bccomp($userRestToday,$confData['top_onehundred']['max'],2) == -1 && bccomp($userRestToday,$confData['top_onehundred']['min'],2) >= 0){
                        $sendAmount = mt_rand($confData['top_onehundred']['min'] * 100,$userRestToday * 100);
                    }else{
                        $sendAmount = $userRestToday *  100;
                    }
                }else{
                    if(bccomp($userRestToday,$confData['newuser_reward']['max'],2) >= 0){
                        $sendAmount = mt_rand($confData['newuser_reward']['min'] * 100,$confData['newuser_reward']['max'] * 100);
                    }elseif(bccomp($userRestToday,$confData['newuser_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['top_onehundred']['min'],2) >= 0){
                        $sendAmount = mt_rand($confData['newuser_reward']['min'] * 100,$userRestToday * 100);
                    }else{
                        $sendAmount = $userRestToday *  100;
                    }
                }
                break;
            case 2:
                if(bccomp($userRestToday,$confData['olduser_unbind_reward']['max'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_unbind_reward']['min'] * 100,$confData['olduser_unbind_reward']['max'] * 100);
                }elseif(bccomp($userRestToday,$confData['olduser_unbind_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['olduser_unbind_reward']['min'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_unbind_reward']['min'] * 100,$userRestToday * 100);
                }else{
                    $sendAmount = $userRestToday *  100;
                }
                break;
            case 3:
                if(bccomp($userRestToday,$confData['olduser_reward']['max'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_reward']['min'] * 100,$confData['olduser_reward']['max'] * 100);
                }elseif(bccomp($userRestToday,$confData['olduser_reward']['max'],2) == -1 && bccomp($userRestToday,$confData['olduser_reward']['min'],2) >= 0){
                    $sendAmount = mt_rand($confData['olduser_reward']['min'] * 100,$userRestToday * 100);
                }else{
                    $sendAmount = $userRestToday *  100;
                }
                break;
        }

        return bcdiv($sendAmount, 100, 2);
    }

    /**
     *  个人的生成的现金红包列表
     *
     * @JsonRpcMethod
     */
    public function amountShareList($params)
    {
        global $userId;

        $num = isset($params->num) ? $params->num : 0;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $result = ['my_top' => 0, 'my_total_money' => 0, 'level' => 1, 'my_list' => [], 'my_expire_list' => []];

        //获取vip等级
        $userInfo = Func::getUserBasicInfo($userId,true);
        $result['level'] = isset($userInfo['level']) && $userInfo['level'] > 1 ? $userInfo['level'] : 1;
        //我的投资生成的红包列表
        $where['user_id'] = $userId;
        if($num == 0){
            $list = HdAmountShareMasterNew::where($where)->where('award_status',0)->whereRaw('now() < end_time')->orderByRaw("id desc")->get()->toArray();
            $expireList = HdAmountShareMasterNew::where($where)->where(
                function($query) {
                    $query->whereRaw('award_status = 1')->orWhereRaw('now() > end_time');
                }
            )->orderByRaw("id desc")->get()->toArray();
        }else{
            $list = HdAmountShareMasterNew::where($where)->where('award_status',0)->whereRaw('now() < end_time')->take($num)->orderByRaw("id desc")->get()->toArray();
            $expireList = HdAmountShareMasterNew::where($where)->where(
                function($query) {
                    $query->whereRaw('award_status = 1')->orWhereRaw('now() > end_time');
                }
            )->orderByRaw("id desc")->take($num)->get()->toArray();
        }
        //正常列表
        foreach($list as $item){
            if(isset($item['id']) && !empty($item['id'])){
                //我的新用户领取金额
                $item['new_user_money'] = AmountShareBasic::getNewUserMoney($item);
                $result['my_list'][] = $item;
            }
        }
        //失效列表
        foreach($expireList as $item){
            if(isset($item['id']) && !empty($item['id'])){
                //我的新用户领取金额
                $item['new_user_money'] = AmountShareBasic::getNewUserMoney($item);
                $result['my_expire_list'][] = $item;
            }
        }
        //这一周总排名
        $thisWeek = date("W");
        $totalList = HdAmountShareMasterNew::where('week',$thisWeek)->select(DB::raw('sum(total_money) as money,user_id,max(id) as max_id'))->groupBy("user_id")->orderByRaw("money desc,max_id asc")->get()->toArray();

        if (!empty($list)) {
            //自己的分享领取完金额
            $myTotalMoneyList = HdAmountShareMasterNew::where('week',$thisWeek)->where('user_id',$userId)->get()->toArray();
            if(!empty($myTotalMoneyList)){
                //自己的排名
                $top = 0;
                foreach($totalList as $key => $item){
                    if(isset($item['user_id']) && !empty($item['user_id']) && $item['user_id'] == $userId){
                        $top = $key + 1;
                    }
                }
                foreach($myTotalMoneyList as $item){
                    if(isset($item['total_money']) && !empty($item['total_money'])){
                        //自己的分享领取完金额
                        $result['my_total_money'] += $item['total_money'];
                    }
                }
                //我的排名
                $result['my_top'] = $top;
            }
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }

    /**
     *  现金红包排行列表
     *
     * @JsonRpcMethod
     */
    public function amountShareTopList($params)
    {
        $num = isset($params->num) && !empty($params->num) ? $params->num : 3;
        $thisWeek = date("W");
        $list = HdAmountShareMasterNew::where('week',$thisWeek)
            ->select(DB::raw('sum(total_money) as money,user_id,max(id) as max_id'))
            ->groupBy("user_id")
            ->orderByRaw("money desc,max_id asc")
            ->take($num)->get()->toArray();
        foreach ($list as &$item) {
            if (!empty($item)) {
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list
        );

    }

    /**
     *  发送余额
     *
     * @JsonRpcMethod
     */
    public function amountShareSendAward($params)
    {
        global $userId;

        $result = ['isLogin' => 1, 'amount' => 0, 'isGot' => 0, 'mall' => [], 'recentList' => []];
        if (empty($userId)) {
            $result['isLogin'] = 0;
        }
        $identify = $params->identify;
        if (empty($identify)) {
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $activityInfo = ActivityService::GetActivityInfoByAlias('amount_share');
        if(empty($activityInfo)){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //显示条数
        $num = isset($params->num) ? $params->num : 5;

        // 商品是否存在
        $date = date("Y-m-d H:i:s");
        DB::beginTransaction();
        $mallInfo = HdAmountShareMasterNew::where(['identify' => $identify])
            ->where("start_time", "<=", $date)
            ->where("end_time", ">=", $date)
            ->lockForUpdate()->first();
        if (!$mallInfo) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //获取用户的微信昵称和手机号
        $mallInfo['user_name'] = '';
        $mallInfo['phone'] = '';
        $mallInfo['user_photo'] = '';
        if (!empty($mallInfo['user_id'])) {
            //获取微信昵称
            $nickName = Func::wechatInfoByUserID($mallInfo['user_id']);
            $mallInfo['user_name'] = isset($nickName['nick_name']) && !empty($nickName['nick_name']) ? $nickName['nick_name'] : "";
            $mallInfo['user_photo'] = isset($nickName['headimgurl']) && !empty($nickName['headimgurl']) ? $nickName['headimgurl'] : "";
            //获取用户手机号
            $phone = Func::getUserPhone($mallInfo['user_id'], true);
            $mallInfo['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
        }
        $result['mall'] = $mallInfo;
        // 计算剩余金额和剩余数量
        $remain = $mallInfo->total_money - $mallInfo->use_money;
        $remain = $remain > 0 ? $remain : 0;
        $remainNum = $mallInfo->total_num - $mallInfo->receive_num;
        $remainNum = $remainNum > 0 ? $remainNum : 0;

        //用户领取过
        if ($result['isLogin']) {
            $join = HdAmountShareMasterNewInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
            if ($join) {
                $result['isGot'] = 1;
                $result['amount'] = $join['money'];

                //获奖记录
                $recentList = HdAmountShareMasterNewInfo::where('main_id', $mallInfo['id'])->where('is_new',"!=", 2)->orderBy('id', 'desc')->take($num)->get();
                $result['recentList'] = self::_formatData($recentList);

                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result
                );
            }
            //奖品已抢光
            if ($remainNum == 0) {
                $result['isGot'] = 2;
            }
        }
        // 发送现金
        if ($result['isLogin'] && !$result['isGot']) {
            $money = AmountShareBasic::getRandomMoney($remain * 100, $remainNum, $mallInfo->min * 100, $mallInfo->max);
            $money = $money / 100;
            $mallInfo->increment('use_money', $money);
            $mallInfo->increment('receive_num', 1);

            //给用户加金额
            $uuid = Func::create_guid();
            $res = Func::incrementAvailable($userId, $mallInfo->id, $uuid, $money, 'share');
            if (!isset($res['result']['code'])) {
                throw new OmgException(OmgException::API_FAILED);
            }
            HdAmountShareMasterNewInfo::insertGetId([
                'user_id' => $userId,
                'main_id' => $mallInfo->id,
                'uuid' => $uuid,
                'is_new' => AmountShareBasic::isActivityNewUser($userId,$mallInfo->user_id,$activityInfo),
                'money' => $money,
                'remark' => json_encode($res, JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $result['amount'] = $money;
            //判断首次领取就更新当前周数
            if(isset($mallInfo->week) && $mallInfo->week == 0){
                HdAmountShareMasterNew::where('id',$mallInfo->id)->update(['week'=>date("W")]);
                HdAmountShareMasterNew::where('id',$mallInfo->id)->update(['day'=>date("Y-m-d")]);
            }
            //判断分享的是否领取完
            if(!empty($mallInfo->id) && $mallInfo->total_num  === $mallInfo->receive_num){
                //修改为领取完状态
                HdAmountShareMasterNew::where('id',$mallInfo->id)->update(['status'=>1]);
            }
        }
        DB::commit();

        //获奖记录
        $recentList = HdAmountShareMasterNewInfo::where('main_id', $mallInfo['id'])->where('is_new',"!=", 2)->orderBy('id', 'desc')->take($num)->get();
        $result['recentList'] = self::_formatData($recentList);

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
        );
    }
    /**
     *  现金红包被分完后给自己发送余额
     *
     * @JsonRpcMethod
     */
    public function amountShareMineAward($params){
        global $userId;

        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if($id <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        DB::beginTransaction();
        //判断该红包是否被全部领取
        $where['user_id'] = $userId;
        $where['id'] = $id;
        $where['status'] = 1;
        $where['award_status'] = 0;
        $isFinish = HdAmountShareMasterNew::where($where)->lockForUpdate()->first();
        if(!empty($isFinish) && $isFinish->total_money === $isFinish->use_money && $isFinish->total_num === $isFinish->receive_num){
            //判断有没有新注册的用户领取
            $newList = HdAmountShareMasterNewInfo::select(DB::raw('SUM(money) as money'))
                ->where('main_id',$isFinish->id)
                ->where('is_new',1)->first();
            if(!empty($newList) && isset($newList['money']) && $newList['money'] > 0){
                //获取应得金额
                $sendMoney = AmountShareBasic::getNewUserMoney($isFinish);
                //发奖
                $uuid = Func::create_guid();
                $res = Func::incrementAvailable($userId, $isFinish->id, $uuid, $sendMoney, 'share');
                if (!isset($res['result']['code'])) {
                    throw new OmgException(OmgException::API_FAILED);
                }
                $result['money'] = $sendMoney;
                //添加记录
                HdAmountShareMasterNewInfo::insertGetId([
                    'user_id' => $userId,
                    'main_id' => $id,
                    'uuid' => $uuid,
                    'is_new' => 2,//2为最后领取的金额
                    'money' => $sendMoney,
                    'remark' => json_encode($res, JSON_UNESCAPED_UNICODE),
                    'status' => 1,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                //修改为本人领取完状态
                HdAmountShareMasterNew::where('id',$isFinish->id)->update(['award_status'=>1]);

                DB::commit();
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $result
                );
            }
        }
        DB::commit();
        throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
    }
    //将列表的数据整理出手机号
    public static function _formatData($data)
    {
        if (empty($data)) {
            return $data;
        }
        foreach ($data as &$item) {
            if (!empty($item) && isset($item['user_id']) && !empty($item['user_id'])) {
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                //获取微信信息
                $wechatInfo = Func::wechatInfoByUserID($item['user_id']);
                $item['user_name'] = isset($wechatInfo['nick_name']) && !empty($wechatInfo['nick_name']) ? $wechatInfo['nick_name'] : "";
                $item['user_photo'] = isset($wechatInfo['headimgurl']) && !empty($wechatInfo['headimgurl']) ? $wechatInfo['headimgurl'] : "";
            }
        }
        return $data;
    }
}
