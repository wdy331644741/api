<?php
namespace App\Service;

use App\Models\KbDaZhuanPan;
use App\Service\GlobalAttributes;
use Request;

class DazhuanpanService
{
    /**
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($userId, $award) {
        $remark = [];
        //谢谢参与
        if (!$award || $award['type'] == 'empty') {
            KbDaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => 'empty',
                'alias_name' => $award['alias_name'],
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'empty',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            return true;
        }
        // 发送现金
        if($award['type'] === 'rmb') {
            $uuid = SendAward::create_guid();
            // 创建记录
            $res = KbDaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => $award['name'],
                'alias_name' => $award['alias_name'],
                'uuid' => $uuid,
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'rmb',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            $globalMoney = GlobalAttributes::getString('kb_dazhuanpan_total_money');
            if(empty($globalMoney)){
                $addMoney = $award['size'];
            }else{
                $addMoney = bcadd($globalMoney,$award['size'],2);
            }
            GlobalAttributes::setItem('kb_dazhuanpan_total_money',null,$addMoney);
            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $award['size'], 'kb_dazhuanpan');
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
            $awards = SendAward::ActiveSendAward($userId, 'kb_dazhuanpan_' . $aliasName);
            $remark['award'] = $awards;
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                KbDaZhuanPan::create([
                    'user_id' => $userId,
                    'award_name' => $award['name'],
                    'alias_name' => $award['alias_name'],
                    'uuid' => '',
                    'ip' => Request::getClientIp(),
                    'user_agent' => Request::header('User-Agent'),
                    'status' => 1,
                    'type' => 'activity',
                    'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                ]);
                return true;
            }else{
                KbDaZhuanPan::create([
                    'user_id' => $userId,
                    'award_name' => $award['name'],
                    'alias_name' => $award['alias_name'],
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
            KbDaZhuanPan::create([
                'user_id' => $userId,
                'award_name' => $award['name'],
                'alias_name' => $award['alias_name'],
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

}
