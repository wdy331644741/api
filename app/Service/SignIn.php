<?php
namespace App\Service;

use Illuminate\Support\Facades\DB;
use App\Service\Attributes;


class SignIn
{
     
    /**
     * 根据开始日期获取连续签到天数
     *
     * @param int 用户id 
     * @param string 开始计数日期:'2016-10-11'
     * 
     * @return int
     */
    static function getSignInNum($userId, $startDate ) {
        $aliasName = 'signin';     
        $signIn = Attributes::getItem($userId, $aliasName);
        
        if(!$signIn) {
            return 0;
        }
        
        $lastUpdate = $signIn['updated_at'] ? $signIn['updated_at'] : $signIn['created_at'];
        $lastUpdateDate = date('Y-m-d', strtotime($lastUpdate));
        
        $maxNum = self::getNumByStartDate($startDate);

        // 今天已签到
        if($lastUpdateDate == date('Y-m-d', time())) {
            $continue = $signIn['number'] ?  $signIn['number'] : 0;
            return $maxNum > $continue ? $continue : $maxNum;
        }
        
        // 昨天已签到
        if($lastUpdateDate == date('Y-m-d', time() - 3600*24)) {
            $continue = $signIn['number'] ?  $signIn['number'] : 0;
            return $maxNum-1 > $continue ? $continue : $maxNum-1;
        }
        
        return 0;
    }

    /**
     * 根据开始日期获取到目前的天数
     *
     * @param $startDate
     *
     * @return int
     */
    static function getNumByStartDate($startDate) {
        $startTime = strtotime($startDate);
        return ceil((time()-$startTime)/(3600*24));
    }

}