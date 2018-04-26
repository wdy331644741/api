<?php
namespace App\Service;

use App\Models\HdCollectCard;
use App\Models\SendRewardLog;
use Request;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use Config;


class CollectCardService
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
            HdCollectCard::create([
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
            // 根据别名发活动奖品
        } else if($award['type'] === 'activity' ) {
            $aliasName = $award['alias_name'];
            $awards = SendAward::ActiveSendAward($userId, 'collect_card_' . $aliasName);
            $remark['award'] = $awards;
            $status = 0;
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                $status = 1;
            }
            HdCollectCard::create([
                'user_id' => $userId,
                'card_name'=> $award['card_name'],
                'award_name' => $award['name'],
                'alias_name' => $award['alias_name'],
                'uuid' => isset($awards[0]['uuid']) ? $awards[0]['uuid'] : '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => $status,
                'type' => 'activity',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                'effective_start'=> isset($awards[0]['effective_start']) ? $awards[0]['effective_start'] : '',
                'effective_end'=> isset($awards[0]['effective_end']) ? $awards[0]['effective_end'] : '',
            ]);
            //显示一条奖的记录  188新手红包, 翻倍奖励
        } else if($award['type'] === 'virtual' ) {
            HdCollectCard::create([
                'user_id' => $userId,
                'card_name'=> $award['card_name'],
                'award_name' => $award['name'],
                'alias_name' => $award['alias_name'],
                'uuid' => isset($award['uuid']) ? $award['uuid'] : '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'virtual',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
                'effective_start'=> isset($award['effective_start']) ? $award['effective_start'] : '',
                'effective_end'=> isset($award['effective_end']) ? $award['effective_end'] : '',
            ]);
        }
    }

    public  static function addRedByRealName($userId) {
        $config = Config::get('collectcard');
        $register_award = $config['register_award'];
        $activity = ActivityService::GetActivityInfoByAlias($register_award['alias_name']);
        $where['user_id'] = $userId;
        $where['activity_id'] = $activity->id;
        //todo  :status=1
        $where['status'] = 1;
        $reg_awad = HdCollectCard::select('created_at')->where(['user_id'=>$userId, 'status'=>1, 'alias_name'=>$config['register_award']['alias_name']])->orderBy('id','desc')->first();
        if($reg_awad) {
            $award_log = SendRewardLog::select('user_id', 'uuid', 'remark', 'award_id', 'award_type')->where($where)->where('created_at', '>=', $reg_awad['created_at'])->get()->toArray();
        } else {
            $award_log = SendRewardLog::select('user_id', 'uuid', 'remark', 'award_id', 'award_type')->where($where)->get()->toArray();
        }
        if ($award_log) {
            foreach($award_log as &$val) {
                $award = json_decode( $val['remark'], 1);
                $award['uuid'] = $val['uuid'];
                $award['alias_name'] = $register_award['alias_name'];
                $award['name'] = $award['award_name'];
                $award['card_name'] = $register_award['card_name'];
                $award['type'] = $register_award['type'];
                self::sendAward($userId, $award);
            }
        }
    }
}