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

    static function setItem($uid, $key, $number=null, $string=null, $text=null) {
        $res = GlobalAttribute::where(['user_id'=>$uid,'key'=>$key])->first();

        if($res){
            $res->string = $string;
            $res->text = $text;
            $res->number = $number;
            return $res->save();
        }

        $attribute = new GlobalAttribute();
        $attribute->user_id = $uid;
        $attribute->key = $key;
        $attribute->string = $string;
        $attribute->number = $number;
        $attribute->text = $text;
        return $attribute->save();
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
     * 根据日期获取number,每天number从零开始
     * 
     * @param $key
     * @return int
     */
    static public function getNumberByDay($key) {
        $res = GlobalAttribute::where(array('key' => $key))->first();
        if(!$res) {
            return 0;
        }
        // 不是今天
        if(date('Ymd', strtotime($res['update_at'])) !== date('Ymd')) {
            return 0;            
        }
        return intval($res->number);
    }

    /**
     * 根据日期递增number,每天number从零开始
     *
     * @param $key
     * @param $num
     * @return int
     */
    static public function incrementByDay($key, $num) {
        $res = GlobalAttribute::where(['key' => $key])->first();

        if(!$res) {
            $res = GlobalAttribute::create(['key' => $key,  'number' => $num]);
            return $res->number;
        }

        if(date('Ymd', strtotime($res['update_at'])) !== date('Ymd')) {
            $res->number = $num;
            $res->save();
            return $res->number;
        }
        
        $res->increment('number', $num);
        return $res->number;
    }

}