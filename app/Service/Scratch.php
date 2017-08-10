<?php
namespace App\Service;

use App\Models\HdScratch;
use Config,Request;

class Scratch
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
        // 发送现金
        if($award['type'] === 'rmb') {
            $uuid = SendAward::create_guid();
            // 创建记录
            $res = HdScratch::create([
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

            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $award['size'], 'magic_ice_cube');

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
            $awards = SendAward::ActiveSendAward($userId, $aliasName);
            $remark['award'] = $awards;
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                HdScratch::create([
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
                HdScratch::create([
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
        return false;
    }

    static function addScratchNum($triggerData){
        $config = Config::get('scratch');
        if(
            isset($triggerData['user_id']) && $triggerData['user_id'] > 0 &&
            isset($triggerData['scatter_type']) && $triggerData['scatter_type'] == 2 &&
            isset($triggerData['period']) && $triggerData['period'] >= 3 &&
            isset($triggerData['Investment_amount'])
        ){
            $key = '';
            if($triggerData['period'] == 3){
                $key = $config['copper']['key'];
            }elseif($triggerData['period'] == 6){
                $key = $config['silver']['key'];
            }elseif($triggerData['period'] == 12){
                $key = $config['gold']['key'];
            }elseif($triggerData['period'] > 12){
                $key = $config['diamonds']['key'];
            }
            $num = intval($triggerData['Investment_amount']/10000);
            if(empty($key) && $num <= 0){
                return false;
            }
            //给用户添加次数
            Attributes::increment($triggerData['user_id'],$key,$num);
        }

    }
}
