<?php
namespace App\Service;

use App\Exceptions\OmgException;
use Config, Cache,DB;


class JumpService
{
    //加抽奖次数
    public  static function addDrawNum($userId, $number)
    {
        $key = Config::get('jump.alias_name');
        Attributes::increment($userId,$key,$number);
    }
}