<?php
namespace App\Service;

use App\Models\UserAttribute;
use Config;

class SignInSystemBasic
{
    /**
     * 每天签到加倍卡信息添加
     */
    static function signInEveryDayMultiple($userId,$amount = 0){
        $config = Config::get('signinsystem');
        $key = $config['multiple_card_alias_name'];
        $multiple = UserAttribute::where(['user_id'=> $userId,'key' => $key])->where('updated_at',"like",date("Y-m-d")."%")->first();
        //判断是否存在
        if(isset($multiple->number) && $multiple->number > 0){
            return $multiple->number / 10;
        }
        if($amount == 0){
            return 0;
        }
        Attributes::setItem($userId,$key,$amount * 10);
        return $amount;
    }

}