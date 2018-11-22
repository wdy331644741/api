<?php
namespace App\Service;

use Config, Cache,DB;


class DoubleTwelveService
{

    //用户随机中奖号码发放
    public  static function addDrawNum($userId, $key)
    {
        $aliasName = Config::get('doubletwelve.alias_name');
        $numDay = Attributes::getNumberByDay($userId, $key);
        if ($numDay >=2) {
            return false;
        }
        Attributes::incrementByDay($userId, $key);
        Attributes::incrementByDay($userId, $aliasName);
    }
}