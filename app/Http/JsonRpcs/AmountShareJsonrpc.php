<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\UserAttribute;
use App\Service\AmountShareBasic;
use App\Models\AmountShare;
use App\Models\AmountShareInfo;
use App\Service\Attributes;
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
        $result = ['my_top' => 0, 'my_total_money' => 0, 'my_list' => [], 'my_expire_list' => []];

        //我的投资生成的红包列表
        $where['user_id'] = $userId;
        if($num == 0){
            $list = AmountShare::where($where)->orderByRaw("id desc")->get()->toArray();
        }else{
            $list = AmountShare::where($where)->take($num)->orderByRaw("id desc")->get()->toArray();
        }
        //失效列表
        foreach($list as $item){
            if(isset($item['id']) && !empty($item['id'])){
                $endTime = strtotime($item['end_time']);
                if(time() > $endTime || $item['award_status'] == 1){
                    $result['my_expire_list'][] = $item;
                }else{
                    $result['my_list'][] = $item;
                }
            }
        }
        //我的排名&总计生成的红包金额
        $totalList = AmountShare::where('status',1)->select(DB::raw('sum(total_money) as money,user_id,max(id) as max_id'))->groupBy("user_id")->orderByRaw("money desc,max_id asc")->get();
        $total_count = count($totalList);

        if (!empty($list)) {
            //自己的分享领取完金额
            $myTotalMoney = AmountShare::where('status',1)->where('user_id',$userId)->sum('total_money');
            if(!empty($myTotalMoney)){
                //自己的排名
                $top = 0;
                foreach($totalList as $key => $item){
                    if(isset($item['user_id']) && !empty($item['user_id']) && $item['user_id'] == $userId){
                        $top = $key + 1;
                    }
                }
                $result['my_total_money'] = $myTotalMoney;
                $result['my_top'] = $top;
            }else{
                $result['my_total_money'] = 0;
                $result['my_top'] = $total_count+1;
            }
        }else{
            $result['my_top'] = $total_count+1;
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
        $result = ['amount_share_1'=>0,'amount_share_3'=>0,'amount_share_6'=>0,'top_list'=>[]];
        $num = isset($params->num) && !empty($params->num) ? $params->num : 5;
        $list = AmountShare::where('status',1)->select(DB::raw('sum(total_money) as money,user_id,max(id) as max_id'))->groupBy("user_id")->orderByRaw("money desc,max_id asc")->take($num)->get();
        foreach ($list as &$item) {
            if (!empty($item)) {
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        $result['top_list'] = $list;
        //查询各个标的的参与人数
        $res = UserAttribute::select(DB::raw('sum(number) as num,`key`'))->whereIn('key', ['amount_share_1', 'amount_share_3', 'amount_share_6'])->groupBy("key")->get();

        foreach($res as $item){
            if(!empty($item->key)){
                $result[$item->key] = $item->num;
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $result
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

        //显示条数
        $num = isset($params->num) ? $params->num : 5;

        // 商品是否存在
        $date = date("Y-m-d H:i:s");
        DB::beginTransaction();
        $mallInfo = AmountShare::where(['identify' => $identify])
            ->where("start_time", "<=", $date)
            ->where("end_time", ">=", $date)
            ->lockForUpdate()->first();
        if (!$mallInfo) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //获取用户的微信昵称和手机号
        $mallInfo['user_name'] = '';
        $mallInfo['phone'] = '';
        if (!empty($mallInfo['user_id'])) {
            //获取微信昵称
            $nickName = Func::wechatInfoByUserID($mallInfo['user_id']);
            $mallInfo['user_name'] = isset($nickName['nick_name']) && !empty($nickName['nick_name']) ? $nickName['nick_name'] : "";
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
            $join = AmountShareInfo::where(['user_id' => $userId, 'main_id' => $mallInfo->id])->first();
            if ($join) {
                $result['isGot'] = 1;
                $result['amount'] = $join['money'];

                //获奖记录
                $recentList = AmountShareInfo::where('main_id', $mallInfo['id'])->orderBy('id', 'desc')->take($num)->get();
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


        // 发体现金
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
            AmountShareInfo::insertGetId([
                'user_id' => $userId,
                'main_id' => $mallInfo->id,
                'uuid' => $uuid,
                'money' => $money,
                'remark' => json_encode($res, JSON_UNESCAPED_UNICODE),
                'status' => 1,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            $result['amount'] = $money;
            //判断分享的是否领取完
            if(!empty($mallInfo->id) && $mallInfo->total_num  === $mallInfo->receive_num){
                //修改为领取完状态
                AmountShare::where('id',$mallInfo->id)->update(['status'=>1]);
            }
        }
        DB::commit();

        //获奖记录
        $recentList = AmountShareInfo::where('main_id', $mallInfo['id'])->orderBy('id', 'desc')->take($num)->get();
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
        //判断该红包是否被全部领取
        $where['user_id'] = $userId;
        $where['id'] = $id;
        $where['status'] = 1;
        $where['award_status'] = 0;
        $isFinish = AmountShare::where($where)->first();
        if(!empty($isFinish) && $isFinish->total_money === $isFinish->use_money && $isFinish->total_num === $isFinish->receive_num){
            //发奖
            $uuid = Func::create_guid();
            $res = Func::incrementAvailable($userId, $isFinish->id, $uuid, $isFinish->total_money, 'cash_bonus');
            if (!isset($res['result']['code'])) {
                throw new OmgException(OmgException::API_FAILED);
            }
            $result['money'] = $isFinish->total_money;
            //修改为本人领取完状态
            AmountShare::where('id',$isFinish->id)->update(['award_status'=>1]);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $result
            );
        }
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
