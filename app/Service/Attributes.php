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

   static public function increment($uid,$key,$number = 1){
        $res = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->first();
        
        if($res){
            $userAttr = $res;
            $res->increment('number', $number);
            return $userAttr->number+$number;
        }
        
        $attribute = new UserAttribute();
        $attribute->user_id = $uid;
        $attribute->key = $key;
        $attribute->number = $number;
        $attribute->save();
        return $number;
    }
    
    static public function decrement($uid,$key,$number = 1){
        $res = UserAttribute::where(['user_id'=>$uid,'key'=>$key])->first();
        
        if($res){
            $userAttr = $res;
            $res->decrement('number', $number);
            return $userAttr->number-$number;
        }
        
        $attribute = new UserAttribute();
        $attribute->user_id = $uid;
        $attribute->key = $key;
        $attribute->number = -$number;
        $attribute->save();
        return $number;
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
            $countArr = array_count_values($status);
            $json = json_encode($status);
            $res->text = $json;
            $res->number = $countArr[1];
            $res->update();
            if($res) return $res->text;
        }else{
            $this->default['gq_'.$kvarr[0]] = $kvarr[1];
            $UserAttribute = new UserAttribute();
            $UserAttribute->user_id = $uid;
            $UserAttribute->key = $key;
            $UserAttribute->number = 1;
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

    public function prize($key){
        $where = ['number'=>8,'key'=>$key];
        $data = UserAttribute::where($where)->get()->toArray();
        return $data;
    }
    
   static public function getNumber($uid, $key, $default = null) {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = UserAttribute::where(array('user_id' => $uid, 'key' => $key))->first();
        if(!$res) {
            if(!is_null($default)) {
                $attribute = new UserAttribute();
                $attribute->user_id  = $uid;
                $attribute->key = $key;
                $attribute->number = intval($default);
                $attribute->save();
            }
            return $default;
        }
       
        return $res['number'];
    }

    // 按json格式获取text字段
    static public function getJsonText($uid, $key, $default = array()) {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = UserAttribute::where(array('user_id' => $uid, 'key' => $key))->first();
        if(!$res) {
            if(count($default) !== 0 ) {
                $attribute = new UserAttribute();
                $attribute->user_id  = $uid;
                $attribute->key = $key;
                $attribute->text = json_encode($default);
                $attribute->save();
            }
            return $default;
        }
       
        return json_decode($res['text'], true);
    }
    
    // 设置text字段
    static public function setText($uid, $key, $value = '') {
        if(empty($uid) || empty($key)) {
            return false;
        }
        $res = UserAttribute::where(array('user_id' => $uid, 'key' => $key))->first();
        if(!$res) {
            $attribute = new UserAttribute();       
            $attribute->user_id = $uid;
            $attribute->key = $key;
            $attribute->text = $value;
            $attribute->save();
            return true;
        }
        $res->text = $value;
        $res->update();
        return true;
    }

    //---------------------新春嘉年华-----------------------//

    //投资标个数记录
    static public function setNyBiao($user_id,$key,$bid){
        $userAttr = new UserAttribute();
        //投资标个数记录
        $res = $userAttr->where(['user_id'=>$user_id,'key'=>$key])->first();
        if(empty($res)){
            $userAttr->key = $key;
            $userAttr->user_id = $user_id;
            $userAttr->number = 1;
            $userAttr->text = json_encode(array($bid));
            $userAttr->save();
            return array('inviteNum'=>1);
        }else{
            $text = json_decode($res->text,true);
            if(in_array($bid,$text)){
                return array('inviteNum'=>count($text));
            }
            $number = array_push($text,$bid);
            $res = $userAttr
                ->where(['key'=>$key,'user_id'=>$user_id])
                ->update(['number'=>$number,'text'=>json_encode($text)]);
            if($res){
                return array('inviteNum'=>$number);
            }else{
                return array('inviteNum'=>0,'errmsg'=>'数据写入失败');
            }
        }
    }
}