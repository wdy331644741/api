<?php
namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserAttribute;

class Attributes
{
   private $default = [
       'gq_0930'=>0,
       'gq_1001'=>0,
       'gq_1002'=>0,
       'gq_1003'=>0,
       'gq_1004'=>0,
       'gq_1005'=>0,
       'gq_1006'=>0,
       'gq_1007'=>0
   ];

    public function increment($uid,$key,$number){
        $attribute = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->count();
        if($attribute >1){
            return false;
        }
        if($attribute){
            $res = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->first();
            $userAttr = $res;
            $res->increment('number', $number);
            if($res) return $userAttr->number;
        }else{
            $UserAttribute = new UserAttribute();
            $UserAttribute->user_id = $uid;
            $UserAttribute->key = $key;
            $UserAttribute->number = $number;
            $res = $UserAttribute->save();
            if($res) return $number;
        }
        return false;
    }

    public function status($uid,$key,$status){
        $attribute = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->count();
        if($attribute >1){
            return false;
        }
        $kvarr = explode(':',$status);
        if($attribute){
            $res = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->first();
            $status = (array)json_decode($res->text);
            $status['gq_'.$kvarr[0]] = intval($kvarr[1]);
            $json = json_encode($status);
            $res->text = $json;
            $res->update();
            if($res) return $res->text;
        }else{
            $this->default['gq_'.$kvarr[0]] = $kvarr[1];
            $UserAttribute = new UserAttribute();
            $UserAttribute->user_id = $uid;
            $UserAttribute->key = $key;
            $UserAttribute->text = json_encode($this->default);
            $UserAttribute->save();
            if($UserAttribute->id) return $UserAttribute->text;
        }
        return false;
    }

    public function rank($key){
        $data = UserAttribute::where('key',$key)->orderBy('number','DESC')->paginate(100)->toArray();
        return $data;

    }
}