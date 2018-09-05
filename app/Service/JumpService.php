<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\HdJump;
use Config, Cache,DB;


class JumpService
{
    //加抽奖次数
    public  static function addDrawNum($userId, $number)
    {
        $key = Config::get('jump.drew_user_key');
        Attributes::increment($userId,$key,$number);
    }

    /**
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($userId, $award) {
        $remark = [];
        $insertData = [
            'user_id' => $userId,
            'award_name' => isset($award['name']) ? $award['name'] : 'empty',
            'alias_name' => isset($award['alias_name']) ? $award['alias_name'] : 'empty',
            'number' => isset($award['id']) ? $award['id'] : 0,
            'dice' => isset($award['dice']) ? $award['dice'] : 0,
            'uuid' => '',
            'status' => 1,
            'type' => isset($award['type']) ? $award['type'] : 'empty',
            'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
        ];
        if (!$award || $award['type'] === 'empty') {
            HdJump::create($insertData);
            return true;
        }
        // 根据别名发活动奖品
        if($award['type'] === 'activity' ) {
            $awards = SendAward::ActiveSendAward($userId, 'jump_' . $award['alias_name']);
            $remark['award'] = $awards;
            $insertData['remark'] = json_encode($remark, JSON_UNESCAPED_UNICODE);
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                HdJump::create($insertData);
                return true;
            }
            $insertData['status'] = 0;
            HdJump::create($insertData);
            return false;
        } else if($award['type'] === 'rmb') {
            $uuid = SendAward::create_guid();
            $insertData['uuid'] = $uuid;
            $res = HdJump::create($insertData);
            $purchaseRes = Func::incrementAvailable($userId, $res->id, $uuid, $award['size'], 'jump');
            $remark['addMoneyRes'] = $purchaseRes;
            // 成功
            if(isset($purchaseRes['result'])) {
                $res->update(['remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
                return true;
            }
            $res->update(['status' => 0, 'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE)]);
            return false;
        } else if ($award['type'] == 'virtual') {
            HdJump::create($insertData);
            return true;
        }
        return false;
    }
}