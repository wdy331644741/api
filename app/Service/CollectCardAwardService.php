<?php
namespace App\Service;

use App\Models\HdCollectCardAward;
use Request;
use Config;


class CollectCardAwardService
{
    /**
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($userId, $award)
    {
        //谢谢参与
        if (!$award || $award['type'] == 'empty') {
            HdCollectCardAward::create([
                'user_id' => $userId,
                'award_name' => 'empty',
                'alias_name' => $award['alias_name'],
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'empty',
                'remark' => '',
            ]);
            // 根据别名发活动奖品
        } else if ($award['type'] === 'activity') {
            HdCollectCardAward::create([
                'user_id' => $userId,
                'award_name' => $award['name'],
                'alias_name' => $award['alias_name'],
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'activity',
                'remark' => '',
            ]);
        }
    }
}