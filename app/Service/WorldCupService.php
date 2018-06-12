<?php
namespace App\Service;

use App\Models\HdWorldCupSupport;
use App\Service\Attributes;
use App\Models\HdWorldCupExtra;
use App\Models\HdWorldCupConfig;
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
        if (!$userId || !$f_userid || $number <=0 || !$type) {
            return false;
        }
            //额外球记录添加
            HdWorldCupExtra::create([
                'user_id'=> $userId,
                'f_userid'=> $f_userid,
                'number'=> intval($number),
                'type'=> intval($type),
            ]);
            return true;
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

    //我的总进球数  进球数+额外进球数
    public static function getTotalBallCounts($userId)
    {
        //进球数
        $ball_nums = self::getBallCounts($userId);
        $extra_ball_nums = self::getExtraBallCounts($userId);
        return intval($ball_nums + $extra_ball_nums);
    }

    //进球数
    public  static function getBallCounts($userId)
    {
        $world_cup_config = HdWorldCupConfig::select(['hd_world_cup_config.id','hd_world_cup_config.team','hd_world_cup_config.number','s.user_id', 's.number AS count'])
            ->leftJoin('hd_world_cup_support AS s', 'hd_world_cup_config.id','=','s.world_cup_config_id')
            ->where(['s.user_id'=>$userId])
            ->orderBy('hd_world_cup_config.number', 'desc')
            ->get()->toArray();
        $sum = 0;
        foreach ($world_cup_config as $val) {
            $sum += (intval($val['number']) * intval($val['count']));
        }
        return $sum;
    }

    //我的额外进球数
    public  static function getExtraBallCounts($userId, $type=null)
    {

        $where['user_id'] = $userId;
        if ($type) {
            $where['type'] = $type;
        }
        $data = HdWorldCupExtra::selectRaw('SUM(number) AS total_num')
            ->where($where)
            ->first()->toArray();
        if (!$data) {
            return 0;
        }
        return $data['total_num'];
    }

    //发奖用
    //总进球数列表  进球数+额外进球数
    public static function getTotalBallList()
    {
        //进球数
        $ball_list = self::getTotalBall();
        $extra_list = self::getTotalExtraBall();
        foreach ($extra_list as $val) {
            $ball_list[$val['user_id']]['numbetr'] += $val['number'];
        }
        return $ball_list;
    }

    //发奖
    //进球数列表
    public  static function getTotalBall()
    {
        $world_cup_config = HdWorldCupConfig::select(['hd_world_cup_config.id','hd_world_cup_config.team','hd_world_cup_config.number','s.user_id', 's.number AS count'])
            ->leftJoin('hd_world_cup_support AS s', 'hd_world_cup_config.id','=','s.world_cup_config_id')
            ->where(['s.status'=> 0])
            ->orderBy('hd_world_cup_config.number', 'desc')
            ->get()->toArray();
        $data = array();
        foreach ($world_cup_config as $val) {
            $data[$val['user_Id']]['user_id'] = $val['user_id'];
            $data[$val['user_Id']]['number'] +=  intval($val['number'] * $val['count']);
        }
        return $data;
    }

    //发奖
    //我的额外进球数
    public  static function getTotalExtraBall()
    {

        $data = HdWorldCupExtra::selectRaw('user_id, SUM(number) AS total_num')
            ->where(['status'=> 0])
            ->groupBy('user_id')
            ->get()->toArray();
        return $data;
    }

    public static function  getDateList($key)
    {
        $dates = GlobalAttributes::getText($key);
        if ($dates) {
            return json_decode($dates, true);
        }
        return [];
    }
}