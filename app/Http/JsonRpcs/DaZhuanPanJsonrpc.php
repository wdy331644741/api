<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\DaZhuanPan;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\SendAward;
use Config, Request, Cache;

class DaZhuanPanJsonRpc extends JsonRpc
{
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanInfo() {
        global $userId;
        $userId = 1716707;
        $config = Config::get('dazhuanpan');
        $result = ['login' => false, 'available' => false, 'number' => 0];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        if(ActivityService::isExistByAlias($config['alias_name'])) {
            $result['available'] = true;
        }

        // 剩余抽奖次数
        if($result['available'] && $result['login']) {
            $number = $this->getUserNum($userId,$config);
            $result['number'] = $number < 0 ? 0 : $number;
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanDraw($params) {
        global $userId;
        $userId = 1716707;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取抽奖次数
        $num = isset($params->num) ? $params->num : 1;
        $config = Config::get('dazhuanpan');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $number = $this->getUserNum($userId,$config);
        if($number <= 0) {
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }
        if($num > $number){
            throw new OmgException(OmgException::NUMBER_IS_NULL);
        }

        $result = [
            'awardName' => '谢谢参与',
            'aliasName' => 'empty',
        ];

        // 循环获取奖品
        $awardArr = [];
        for($i = 1;$i <= $num; $i++){
            $awardArr[] = $this->getAward( $config);
        }
        print_r($awardArr);exit;
        /**
        //需放到队列执行
        Attributes::incrementByDay($userId, $config['drew_daily_key']);
        Attributes::increment($userId, $config['drew_total_key']);

        // 发奖
        $res = $this->sendAward($userId, $award);
        if($res) {
            $result['awardName'] = $award['name'];
            $result['aliasName'] = $award['alias_name'];
        }
       */
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function dazhuanpanList() {
        $list = Cache::remember('dazhuanpan_list', 2, function() {
            $data = DaZhuanPan::select('user_id', 'award_name')->where('type', '!=', 'empty')->orderBy('id', 'desc')->take(20)->get();
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
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
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    private function sendAward($userId, $award) {
        $remark = [];
        //谢谢参与
        if (!$award || $award['type'] == 'empty') {
            DaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => 'empty',
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'empty',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            return false;
        }
        // 发送现金
        if($award['type'] === 'rmb') {
            $uuid = SendAward::create_guid();
            // 创建记录
            $res = DaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => $award['name'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'rmb',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);

            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $award['size'], 'big_turntable');

            $remark['addMoneyRes'] = $purchaseRes;
            // 成功
            if(isset($purchaseRes['result'])) {
                $res->update(['status' => 1, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
                return true;
            }

            // 失败
            if(!isset($purchaseRes['result'])) {
                $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
                return false;
            }
        }

        // 根据别名发活动奖品
        if($award['type'] === 'activity' ) {
            $aliasName = $award['alias_name'];
            $awards = SendAward::ActiveSendAward($userId, 'dazhuanpan_' . $aliasName);
            $remark['award'] = $awards;
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                DaZhuanPan::create([
                    'user_id' => $userId,
                    'award_name' => $award['name'],
                    'uuid' => '',
                    'ip' => Request::getClientIp(),
                    'user_agent' => Request::header('User-Agent'),
                    'status' => 1,
                    'type' => 'activity',
                    'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                ]);
                return true;
            }else{
                DaZhuanPan::create([
                    'user_id' => $userId,
                    'award_name' => $award['name'],
                    'uuid' => '',
                    'ip' => Request::getClientIp(),
                    'user_agent' => Request::header('User-Agent'),
                    'status' => 0,
                    'type' => 'activity',
                    'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                ]);
                return false;
            }
        }
        //实物商品
        if ($award['type'] == 'shop') {
            DaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => $award['name'],
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'shop',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            return false;
        }
        return false;
    }

    /**
     * 获取奖品
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    private function getAward($config) {
        $awardList = $config['awards'];
        // 获取权重总值
        $weight = 0;
        foreach($awardList as $award) {
            $weight += $award['weight'];
        }

        $target = rand(1, $weight);
        foreach($awardList as $award) {
            $target = $target - $award['weight'];
            if($target <= 0) {
                $globalKey = $config['alias_name'] . '_' . $award['alias_name'] . '_' . date('Ymd');
                $usedNumber = GlobalAttributes::incrementByDay($globalKey);
                // 奖品送完
                if($usedNumber > $award['num']) {
                    return false;
                }
                return $award;
            }
        }
        return false;
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config){
        $loginNum = Attributes::getNumberByDay($userId, $config['drew_daily_key']);
        if($loginNum <= 0){
            $loginNum = $config['draw_number'];
        }
        $userNum = Attributes::getNumber($userId, $config['drew_user_key']);
        return $loginNum + $userNum;
    }
}

