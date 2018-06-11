<?php
namespace App\Service;

use App\Models\HdWorldCupSupport;
use App\Service\Attributes;
use App\Models\HdWorldCupExtra;
use Config;

class WorldCupService
{
    /**
     *  增加额外进球数
     * @param $userId
     * @param $number
     * @param $type  1 注册绑卡; 2 首投>=2000
     * @return boolean
     * @throws OmgException
     */
    public static function addExtraBall($userId, $f_userid, $number, $type)
    {
        $key = Config::get('worldcup.extra_ball_key');
        if (!$userId || !$f_userid || $number <=0 || !$type) {
            return false;
        }
        $num = Attributes::increment($userId, $key, $number);
        if ($num) {
            //额外球记录添加
            HdWorldCupExtra::create([
                'user_id'=> $userId,
                'f_userid'=> $f_userid,
                'number'=> intval($number),
                'type'=> intval($type),
            ]);
            return true;
        }
        return false;
    }

    public static function addBallSupport($userId, $world_cup_config_id, $number)
    {
        $res = HdWorldCupSupport::where(['user_id'=>$userId,'world_cup_config_id'=>$world_cup_config_id])->first();
        if($res){
            $res->increment('number', $number);
            $res->save();
            return $res->number;
        }
        $world_cup = new HdWorldCupSupport;
        $world_cup->user_id = $userId;
        $world_cup->number = $number;
        $world_cup->world_cup_config_id = $world_cup_config_id;
        $world_cup->save();
        return $world_cup->number;
    }

    public static function getTotalBallCounts() {

    }
}