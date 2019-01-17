<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Service\GlobalAttributes;
use App\Models\GlobalAttribute;
use App\Models\HdWeeksGuess;
use App\Models\HdWeeksGuessLog;
use Config, Cache,DB;


class WeeksGuessService
{
    public static $messageTpl = "亲爱的用户，周末竞猜专享活动已开奖，恭喜您获得现金{{money}}元，奖励将于比赛休息日发放至您的网利宝账户，请届时注意查收。";
    public static $numTpl = "亲爱的用户，周末竞猜专享活动，恭喜您获得竞猜次数{{count}}次，请届时注意查收。";
    public static function sendAward($period, $totalMoney, $type)
    {
        //总次数
        $totalCount = HdWeeksGuess::select(DB::raw('SUM(number) as total'))->where(['period'=>$period, 'type'=>$type])->value('total');
        $totalCount = intval($totalCount);
        if (!$totalCount) {
            return false;
        }
        $data = HdWeeksGuess::select('user_id', DB::raw('SUM(number) as total'))->where(['period'=>$period, 'type'=>$type, 'status'=>0])->groupBy('user_id')->get()->toArray();
        if (!$data) {
            return false;
        }
        foreach ($data as $v) {
            $money = bcdiv(bcmul($totalMoney, $v['total'], 2), $totalCount, 2);
            $tplParam = ['money'=>$money];
            SendMessage::Mail($v['user_id'], self::$messageTpl, $tplParam);
            SendMessage::Message($v['user_id'], self::$messageTpl, $tplParam);
            $insert = HdWeeksGuessLog::create([
                'user_id'=>$v['user_id'],
                'period'=>$period,
                'money'=>$money
            ]);
            HdWeeksGuess::where(['period'=>$period, 'type'=>$type, 'user_id'=>$v['user_id']])->update(['status'=>1]);
        }
        return true;
    }

    public static function addGuessNumber($userId, $key, $number)
    {
        Attributes::increment($userId ,$key ,$number);
        $tplParam = ['count'=>$number];
        SendMessage::Mail($userId, self::$numTpl, $tplParam);
    }
}