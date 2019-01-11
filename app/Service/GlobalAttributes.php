<?php
namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GlobalAttribute;
use Lib\JsonRpcClient;

class GlobalAttributes
{

    static public function increment($key,$number = 1, $string= null, $text= null){

        $res = GlobalAttribute::where(['key'=>$key])->first();

        if($res){
            $res->increment('number', $number, ['string' => $string, 'text' => $text]);
            return $res->number;
        }

        $attribute = new GlobalAttribute();
        $attribute->key = $key;
        $attribute->string = $string;
        $attribute->number = $number;
        $attribute->text = $text;
        $attribute->save();
        return $number;
    }

    static public function decrement($key,$number = 1){
        $res = GlobalAttribute::where(['key'=>$key])->first();

        if($res){
            $userAttr = $res;
            $res->decrement('number', $number);
            return $userAttr->number-$number;
        }

        $attribute = new GlobalAttribute();
        $attribute->key = $key;
        $attribute->number = -$number;
        $attribute->save();
        return $number;
    }

    static public function getNumber($key, $default = null) {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = GlobalAttribute::where(array('user_id' => $uid, 'key' => $key))->first();
        if(!$res) {
            if(!is_null($default)) {
                $attribute = new UserAttribute();
                $attribute->key = $key;
                $attribute->number = intval($default);
                $attribute->save();
            }
            return $default;
        }

        return $res['number'];
    }

    static function getItem($key) {
        if(empty($key)) {
            return false;
        }
        $res = GlobalAttribute::where(array( 'key' => $key))->first();
        if(!$res) {
            return false;
        }
        return $res;
    }

    static function setItem($key, $number=null, $string=null, $text=null) {
        $res = GlobalAttribute::where(['key'=>$key])->first();

        if($res){
            $res->string = $string;
            $res->text = $text;
            $res->number = $number;
            return $res->save();
        }

        $attribute = new GlobalAttribute();
        $attribute->key = $key;
        $attribute->string = $string;
        $attribute->number = $number;
        $attribute->text = $text;
        return $attribute->save();
    }

    static public function getText($key, $default = '') {
        if(empty($key)) {
            return false;
        }
        $res = GlobalAttribute::where(array('key' => $key))->first();
        if(!$res) {
            $attribute = new GlobalAttribute();
            $attribute->key = $key;
            $attribute->text = $default;
            $attribute->save();
            return $default;
        }

        return $res['text'] ? $res['text'] : '';
    }

    static public function getString($key, $default = '') {
        if(empty($key)) {
            return false;
        }
        $res = GlobalAttribute::where(array('key' => $key))->first();
        if(!$res) {
            $attribute = new GlobalAttribute();
            $attribute->key = $key;
            $attribute->string = $default;
            $attribute->save();
            return $default;
        }

        return $res['string'] ? $res['string'] : '';
    }

    // 按json格式获取text字段
    static public function getJsonText($uid, $key, $default = array()) {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = GlobalAttribute::where(array('user_id' => $uid, 'key' => $key))->first();
        if(!$res) {
            if(count($default) !== 0 ) {
                $attribute = new GlobalAttribute();
                $attribute->user_id  = $uid;
                $attribute->key = $key;
                $attribute->text = json_encode($default);
                $attribute->save();
            }
            return $default;
        }

        return json_decode($res['text'], true);
    }

    /**
     * 根据$key获取json,每日json清空
     *
     * @param $key
     * @param $default
     * @return array
     */
    static public function getJsonByDay($key, $default=[]) {
        $res = GlobalAttribute::where(array('key' => $key))->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'text' => json_encode($default)]);
            return json_decode($res->text, true);
        }
        // 不是今天
        if(date('Ymd', strtotime($res['updated_at'])) !== date('Ymd')) {
            $res->text = json_encode($default);
            $res->updated_at = date('Y-m-d H:i:s');
            $res->save();
            return json_decode($res->text, true);
        }
        return json_decode($res->text, true);
    }

    /**
     * 根据$key设置json, 每日json清空
     */
    static public function setJsonByDay($key, $default=[]) {
        $res = GlobalAttribute::where(array('key' => $key))->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'text' => json_encode($default)]);
            return json_decode($res->text, true);
        }

        $res->text = json_encode($default);
        $res->updated_at = date('Y-m-d H:i:s');
        $res->save();
        return json_decode($res->text, true);
    }


    /**
     * 根据$key设置string
     */
    static public function setStringByDay($key,$string=null) {
        $res = GlobalAttribute::where(array('key' => $key))->whereRaw(" to_days(created_at) = to_days(now())")->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'string' => $string]);
            return $res->string;
        }

        $res->string = bcadd($res->string,$string,2);
        $res->save();
        return $res->string;
    }

    /**
     * 根据$key设置number
     */
    static public function setNumberByDay($key,$num=1) {
        $res = GlobalAttribute::where(array('key' => $key))->whereRaw(" to_days(created_at) = to_days(now())")->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'number' => $num]);
            return $res->number;
        }

        $res->increment('number', $num);
        return $res->number;
    }

    /**
     * 根据$key获取number,每日number清空
     *
     * @param $key
     * @return int
     */
    static public function getNumberByDay($key) {
        $res = GlobalAttribute::where(array('key' => $key))->lockforupdate()->first();
        if(!$res) {
            return 0;
        }
        // 不是今天
        if(date('Ymd', strtotime($res['updated_at'])) !== date('Ymd')) {
            return 0;
        }
        return intval($res->number);
    }

    /**
     * 根据$key获取number, 按今天的秒数清空
     *
     * @param $key
     * @return int
     */
    static public function getNumberByTodaySeconds($key, $start=0, $seconds = 0) {
        $res = GlobalAttribute::where(array('key' => $key))->lockforupdate()->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'number' => $start]);
            return $res->number;
        }

        // 超过清空时间
        $seconds = strtotime(date('Y-m-d 00:00:00')) + $seconds;
        $lastSeconds = strtotime($res->updated_at);
        $nowSeconds = time();
        if($lastSeconds < $seconds && $nowSeconds > $seconds) {
            $res->number = $start;
            $res->updated_at = date('Y-m-d H:i:s');
            $res->save();
            return $res->number;
        }

        return $res->number;
    }

    /**
     * 根据$key递减number, 按今天的秒数清空
     *
     * @param $key
     * @param $num
     * @param $seconds
     * @param $start
     * @return int
     */
    static public function decrementByTodaySeconds($key, $num = 1, $seconds = 0, $start = 0) {
        $res = GlobalAttribute::where(array('key' => $key))->lockforupdate()->first();
        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'number' => $start - $num]);
            return $res->number;
        }

        // 超过清空时间
        $seconds = strtotime(date('Y-m-d 00:00:00')) + $seconds;
        $lastSeconds = strtotime($res->updated_at);
        $nowSeconds = time();
        if($lastSeconds < $seconds && $nowSeconds > $seconds) {
            $res->number = $start;
            $res->updated_at = date('Y-m-d H:i:s');
            $res->save();
            return $res->number;
        }

        $res->decrement('number', $num);
        return $res->number;
    }

    /**
     * 根据$key递增number,每日number清空
     *
     * @param $key
     * @param $num
     * @return int
     */
    static public function incrementByDay($key, $num=1) {
        $res = GlobalAttribute::where(['key' => $key])->first();

        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'number' => $num]);
            return $res->number;
        }

        if(date('Ymd', strtotime($res['updated_at'])) !== date('Ymd')) {
            $res->number = $num;
            $res->updated_at = date('Y-m-d H:i:s');
            $res->save();
            return $res->number;
        }

        $res->increment('number', $num);
        return $res->number;
    }

}
