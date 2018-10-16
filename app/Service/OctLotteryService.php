<?php
namespace App\Service;

use App\Models\UserAttribute;
use App\Service\Attributes;
use Config;
use DB;

class OctLotteryService
{
    /**
     * 新存管抽奖活动(专用)  赠送抽奖次数
     * 传用户id ，赠送次数，日期
     */
    static function ctlUserAttributes($user_inc ,$invest_switch ,$reference_date){
        $config = Config::get('octlottery');
        $beforeCount = UserAttribute::where(array('user_id' => $user_inc, 'key' => $config['drew_daily_key']))
                        ->whereDate('updated_at','=',$reference_date)
                        // ->lockForUpdate()
                        ->first();
        if(isset($beforeCount->number) ){
            if($beforeCount->string >= 3)
                return "每日最多3次抽奖机会";
            $beforeString = $beforeCount->string;//今天赠送了多少次抽奖机会
            //今天还能送多少次机会
            $invest_switch = $invest_switch > (3-$beforeString)?(3-$beforeString):$invest_switch;
            $afterString = $beforeString+$invest_switch;
            $res = Attributes::incrementItemByDay($user_inc,$config['drew_daily_key'],$invest_switch,$afterString);
        }else{//每天初始化 额外给用户一次抽奖次数
            $res = Attributes::incrementItemByDay($user_inc,$config['drew_daily_key'],$invest_switch+1,$invest_switch+1);
        }
        return 0;

    }
}
