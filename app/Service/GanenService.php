<?php
namespace App\Service;

use App\Models\UserAttribute;
use App\Models\GanenInfo;
use App\Models\Ganen;

class GanenService
{

    /**
     * "我的奖励"
     * key => 2
     *
     */
    static function getMyReward($userId){
        //$config = config('ganen');
        $result = Ganen::select('award_name as value','type')->where(['user_id' => $userId ])->get();
        //type：2红包 1加息券 7现金
        return $result;
    }
    /**
     * 投资送次数
     *
     * @param $num
     * @return array
     */
    static function addChanceByInvest($userId, $num) {
        $randomRes = self::getRandomRes($num);
        return self::addChance($userId, $randomRes, 'invest');
    }

    /**
     * 邀请送次数
     *
     * @param $num
     * @return array
     */
    static function addChanceByInvite($userId) {
        $randomRes = self::getRandomRes(1);
        return self::addChance($userId, $randomRes, 'invite');
    }

    /**
     * 购买送次数
     *
     * @param $num
     * @return array
     */
    static function addChanceByBuy($userId, $key, $num) {
        return self::addChance($userId, [$key => $num], 'buy');
    }

    /**
     * 减次数
     *
     * @param $userId
     * @param $number
     * @return boolean
     */
    static function minusChance($userId, $number = 1) {
        $config = config('ganen');
        $item  = UserAttribute::where(['user_id' => $userId, 'key' => $config['key'] ])->first();
        if(!$item) {
            return false;
        }

        $words = json_decode($item->text, true);
        if(!is_array($words)) {
            return false;
        }

        // 超过清空时间
        $seconds = strtotime(date('Y-m-d 00:00:00')) + $config['fresh_time'];
        $lastSeconds = strtotime($item->updated_at);
        $nowSeconds = time();
        if($lastSeconds < $seconds && $nowSeconds > $seconds) {
            $item->text = json_encode([]);
            $item->save();
            return false;
        }

        foreach($config['probability'] as $key => $value) {
            if(!isset($words[$key])) {
                return false;
            }

            $words[$key] -= $number;
            if($words[$key] < 0) {
                return false;
            }
        }

        $item->number +=1;
        $item->text = json_encode($words);
        $item->save();
        return true;

    }

    /**
     * 查询次数
     *
     * @param $userId
     * @return array
     */
    static function getChance($userId) {
        $config = config('ganen');
        $item  = UserAttribute::where(['user_id' => $userId, 'key' => $config['key'] ])->first();
        $resultWords = [];
        if(!$item) {
            $words = [];
        }else{
            $words = json_decode($item->text, true);
        }

        if(!is_array($words)) {
            $words = [];
        }

        // 超过清空时间
        if($item) {
            $seconds = strtotime(date('Y-m-d 00:00:00')) + $config['fresh_time'];
            $lastSeconds = strtotime($item->updated_at);
            $nowSeconds = time();

            if($lastSeconds < $seconds && $nowSeconds > $seconds) {
                $item->text = json_encode([]);
                $item->save();
                $words = [];
            }
        }


        foreach($config['probability'] as $key => $value) {
            $resultWords[$key] = 0;

            if(isset($words[$key])) {
                $resultWords[$key] += $words[$key];
            }
        }
        return $resultWords;
    }
    /**
     * 获取今日兑换套数
     *
     */
    static function getExchangeNum($userId) {
        $config = config('ganen');
        $dateToday = date("Y-m-d")." 00:00:00";
        $item  = Ganen::where(['user_id' => $userId,'status' => 1])->where('created_at','>',$dateToday)->count();
        if(!$item) {
            return 0;
        }else{
            return intval($item);
        }
    }

    /**
     *  添加次数
     *
     * @param $userId
     * @param $addition
     * @source $source
     * @return array
     */
    static function addChance($userId, $addition, $source) {
        $config = config('ganen');
        $now = [];

        $item  = UserAttribute::where(['user_id' => $userId, 'key' => $config['key'] ])->first();
        if (!$item){
            $item = UserAttribute::create([
                'user_id' => $userId,
                'key' => $config['key'],
                'number' => 0,
                'string' => '',
                'text' => json_encode([])
            ]);
        }

        $old = json_decode($item->text, true);


        // 无法解析
        if(!is_array($old)) {
            $old = [];
        }

        // 超过清空时间
        $seconds = strtotime(date('Y-m-d 00:00:00')) + $config['fresh_time'];
        $lastSeconds = strtotime($item->updated_at);
        $nowSeconds = time();
        if($lastSeconds < $seconds && $nowSeconds > $seconds) {
            $old = [];
        }



        foreach($config['probability'] as $key => $value) {
            $now[$key] = 0;

            if(isset($old[$key])) {
                $now[$key] += intval($old[$key]);
            }

            if(isset($addition[$key])) {
                $now[$key] += intval($addition[$key]);
                //写入记录
                if(intval($addition[$key])) {
                    Ganeninfo::create([
                        'user_id' => $userId,
                        'word' => $key,
                        'source' => $source,
                        'number' => $addition[$key],
                    ]);
                }
            }
        }

        $item->text = json_encode($now);
        $item->updated_at = date('Y-m-d H:i:s');
        $item->save();

        return $now;
    }

    static function getRandomRes($num) {
        $config = config('ganen');
        $arr = $config['probability'];
        $rangeMultiple = 1000;
        $rangeMin = $config['min'] * $rangeMultiple;
        $rangeMax = $config['max'] * $rangeMultiple;
        $total = array_sum($arr);
        $result = [];
        // 粗略分配
        foreach($arr as $key => $value) {
            $range = rand($rangeMin, $rangeMax)/$rangeMultiple;
            $prop = $num*$range*($value/$total);
            if(intval(($prop*1000))%1000 > rand(1, 1000)) {
                $result[$key] = intval($prop)+1;
            }else{
                $result[$key] = intval($prop);
            }
        }
        //补余
        $nowTotal = array_sum($result);
        $diffValue = $num - $nowTotal;
        $i = 0;
        while($diffValue !== 0) {
            $i++;
            $rand = rand(1, $total);
            foreach($arr as $key => $value) {
                $rand -= $value;
                if($rand <= 0) {
                    break;
                }
            }
            $result[$key] += $diffValue;
            if($result[$key] < 0) {
                $diffValue = $result[$key];
                $result[$key] = 0;
            }else{
                $diffValue = 0;
            }
        }
        return $result;

    }
}
