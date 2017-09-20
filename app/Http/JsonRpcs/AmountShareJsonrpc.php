<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\AmountShareBasic;
use App\Models\HdAmountShare;
use App\Models\HdAmountShareInfo;
use App\Service\Func;
use DB, Request;

class AmountShareJsonRpc extends JsonRpc
{
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
            $list = HdAmountShare::where($where)->orderByRaw("id desc")->get()->toArray();
        }else{
            $list = HdAmountShare::where($where)->take($num)->orderByRaw("id desc")->get()->toArray();
        }
        //失效列表
        foreach($list as $item){
            if(isset($item['id']) && !empty($item['id'])){
                //我的新用户领取金额
                $item['new_user_money'] = AmountShareBasic::getNewUserMoney($item);
                //判断是否过期
                $endTime = strtotime($item['end_time']);
                if(time() > $endTime || $item['award_status'] == 1){
                    $result['my_expire_list'][] = $item;
                }else{
                    $result['my_list'][] = $item;
                }
            }
        }
        //这一周总排名
        $thisWeek = date("W");
        $totalList = HdAmountShare::where('week',$thisWeek)->select(DB::raw('sum(total_money) as money,user_id,max(id) as max_id'))->groupBy("user_id")->orderByRaw("money desc,max_id asc")->get()->toArray();

        if (!empty($list)) {
            //自己的分享领取完金额
            $myTotalMoneyList = HdAmountShare::where('week',$thisWeek)->where('user_id',$userId)->get()->toArray();
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
        $list = HdAmountShare::where('week',$thisWeek)
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
        $mallInfo = HdAmountShare::where(['identify' => $identify])
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
            $join = HdAmountShareInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
            if ($join) {
                $result['isGot'] = 1;
                $result['amount'] = $join['money'];

                //获奖记录
                $recentList = HdAmountShareInfo::where('main_id', $mallInfo['id'])->where('is_new',"!=", 2)->orderBy('id', 'desc')->take($num)->get();
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
            $res = Func::incrementAvailable($userId, $mallInfo->id, $uuid, $money, 'cash_bonus');
            if (!isset($res['result']['code'])) {
                throw new OmgException(OmgException::API_FAILED);
            }
            HdAmountShareInfo::insertGetId([
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
                HdAmountShare::where('id',$mallInfo->id)->update(['week'=>date("W")]);
                HdAmountShare::where('id',$mallInfo->id)->update(['day'=>date("Y-m-d")]);
            }
            //判断分享的是否领取完
            if(!empty($mallInfo->id) && $mallInfo->total_num  === $mallInfo->receive_num){
                //修改为领取完状态
                HdAmountShare::where('id',$mallInfo->id)->update(['status'=>1]);
            }
        }
        DB::commit();

        //获奖记录
        $recentList = HdAmountShareInfo::where('main_id', $mallInfo['id'])->where('is_new',"!=", 2)->orderBy('id', 'desc')->take($num)->get();
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
        $isFinish = HdAmountShare::where($where)->lockForUpdate()->first();
        if(!empty($isFinish) && $isFinish->total_money === $isFinish->use_money && $isFinish->total_num === $isFinish->receive_num){
            //判断有没有新注册的用户领取
            $newList = HdAmountShareInfo::select(DB::raw('SUM(money) as money'))
                ->where('main_id',$isFinish->id)
                ->where('is_new',1)->first();
            if(!empty($newList) && isset($newList['money']) && $newList['money'] > 0){
                //获取应得金额
                $sendMoney = AmountShareBasic::getNewUserMoney($isFinish);
                //发奖
                $uuid = Func::create_guid();
                $res = Func::incrementAvailable($userId, $isFinish->id, $uuid, $sendMoney, 'cash_bonus');
                if (!isset($res['result']['code'])) {
                    throw new OmgException(OmgException::API_FAILED);
                }
                $result['money'] = $sendMoney;
                //添加记录
                HdAmountShareInfo::insertGetId([
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
                HdAmountShare::where('id',$isFinish->id)->update(['award_status'=>1]);

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
