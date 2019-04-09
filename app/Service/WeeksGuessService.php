<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Service\GlobalAttributes;
use App\Models\GlobalAttribute;
use App\Models\HdWeeksGuess;
use App\Models\HdWeeksGuessLog;
use App\Models\HdWeeksGuessConfig;
use App\Models\UserAttribute;
use Config, Cache,DB;


class WeeksGuessService
{
    public static $messageTpl = "亲爱的用户，周末专享竞猜比赛活动{{race_time}}{{home_team}}队VS{{guest_team}}已开奖，本次共{{total_people}}人{{total_count}}次竞猜正确，恭喜您获得现金{{money}}元，奖励将于开奖后七个工作日发放至您的网利宝账户，请注意查收。客服电话：400-858-8066";
    public static $numTpl = "亲爱的用户，恭喜您在周末专享竞猜比赛活动中获得{{count}}次竞猜机会，参与竞猜比赛结果，竞猜正确即可参与瓜分20000元现金";
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
        $totalPeople = count($data);
        $weekConfig = HdWeeksGuessConfig::find($period);
        foreach ($data as $v) {
            $money = bcdiv(bcmul($totalMoney, $v['total'], 2), $totalCount, 2);
            $tplParam = [
                'money'=>$money,
                'home_team'=>$weekConfig->home_team,
                'guest_team'=>$weekConfig->guest_team,
                'race_time'=>$weekConfig->race_time,
                'total_count'=>$totalCount,
                'total_people'=>$totalPeople,
            ];
            SendMessage::Mail($v['user_id'], self::$messageTpl, $tplParam);
            SendMessage::Message($v['user_id'], self::$messageTpl, $tplParam);
            $insert = HdWeeksGuessLog::create([
                'user_id'=>$v['user_id'],
                'period'=>$period,
                'money'=>$money
            ]);
            //发现金
            $uuid = Func::create_guid();
            $result = Func::incrementAvailable($v['user_id'],999999, $uuid, $money,'weekend_guessing');
            //发送消息&存储到日志
            if ( !(isset($result['result']['code']) && $result['result']['code'] == 0) ) {
                $err = array('err_data'=>$result);
                $insert->remark = json_encode($err);
                $insert->save();
            }
            HdWeeksGuess::where(['period'=>$period, 'type'=>$type, 'user_id'=>$v['user_id']])->update(['status'=>1]);
        }
        //次数清0
        UserAttribute::where(['key'=>'weeksguess_drew_user'])->update(['number'=>0]);
        return true;
    }

    public static function addGuessNumber($userId, $key, $number)
    {
        Attributes::increment($userId ,$key ,$number);
        $tplParam = ['count'=>$number];
        SendMessage::Mail($userId, self::$numTpl, $tplParam);
    }
}