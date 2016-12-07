<?php
namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserAttribute;
use Lib\JsonRpcClient;

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
    private $user_url;

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


    //-------------------------圣诞节活动--------------------------//


    //活动1
    public function setSd1Number($key1,$key2,$user_id,$from_user_id){
        if(!$key1 || !$key2 || !$user_id || !$from_user_id){
            return array('inviteNum'=>0,'errsmg'=>'参数错误');
        }
        //邀请3名好友投资
        $res1 = $this->_inviteNum($from_user_id,$user_id,$key1);
        //邀请2名好友连续投资两天
        $this->_inviteCastDay($from_user_id,$user_id,$key2);
        return $res1;
    }

    //活动2
    public function setSd2Number($key,$user_id,$from_user_id){
        if(!$key || !$user_id || !$from_user_id){
            return array('inviteNum'=>0,'errsmg'=>'参数错误');
        }
        $res = $this->_inviteCastDay($from_user_id,$user_id,$key);
        return $res;
    }

    //邀请3名好友投资设置number
    private function _inviteNum($from_user_id,$user_id,$key){
        $userAttr = new UserAttribute();
        //邀请3名好友投资
        $res = $userAttr->where(['user_id'=>$from_user_id,'key'=>$key])->first();

        if(empty($res)){
            $userAttr->key = $key;
            $userAttr->user_id = $from_user_id;
            $userAttr->number = 1;
            $userAttr->text = json_encode(array($user_id));
            $userAttr->save();
            return array('inviteNum'=>1);
        }else{
            if($res->number < 3){
                $text = json_decode($res->text,true);
                if(in_array($user_id,$text)){
                    return array('inviteNum'=>count($text));
                }
                $number = array_push($text,$user_id);
                $res = $userAttr
                    ->where(['key'=>$key,'user_id'=>$from_user_id])
                    ->update(['number'=>$number,'text'=>json_encode($text)]);
                if($res){
                    return array('inviteNum'=>$number);
                }else{
                    return array('inviteNum'=>0,'errmsg'=>'数据写入失败');
                }
            }
            return array('inviteNum'=>0);
        }
    }

    //连续投资两天设置number
    private function _inviteCastDay($from_user_id,$user_id,$key){
        $userAttr = new UserAttribute();
        $res = $userAttr->where(['user_id'=>$from_user_id,'key'=>$key])->first();
        if(empty($res)){
            $userAttr->key = $key;
            $userAttr->user_id = $from_user_id;
            $userAttr->number = 0;
            $userAttr->text = json_encode(array($user_id=>date('Y-m-d')));
            $userAttr->save();
            return array('inviteNum'=>0);
        }else{
            if($res->number == 0){
                $userArr = json_decode($res->text,true);
                if(isset($userArr[$user_id])){
                    $yeDay = date('Y-m-d',time()-24*60*60);
                    if($yeDay == $userArr[$user_id]){
                        $userArr[$user_id] = 'ok';
                        $text = json_encode($userArr);
                        $userAttr->where(['key'=>$key,'user_id'=>$from_user_id])
                            ->update(['number'=>1,'text'=>$text]);
                        return array('inviteNum'=>1);
                    }
                }
                $userArr[$user_id] = date('Y-m-d');
                $userAttr->where(['key'=>$key,'user_id'=>$from_user_id])
                    ->update(['text'=>json_encode($userArr)]);
                return array('inviteNum'=>0);
            }else{
                if($res->number < 2){
                    $userArr = json_decode($res->text,true);
                    if(isset($userArr[$user_id])){
                        if($userArr[$user_id] == 'ok'){
                            return $res->number;
                        }
                        $yeDay = date('Y-m-d',time()-24*60*60);
                        if($yeDay == $userArr[$user_id]){
                            $userArr[$user_id] = 'ok';
                            $arrNum = array_count_values($userArr);
                            $okNnum = $arrNum['ok'];
                            $userAttr->where(['key'=>$key,'user_id'=>$from_user_id])
                                ->update(['number'=>$okNnum,'text'=>json_encode($userArr)]);
                            return array('inviteNum'=>$okNnum);
                        }
                    }
                    $userArr[$user_id] = date('Y-m-d');
                    $userAttr->where(['key'=>$key,'user_id'=>$from_user_id])
                        ->update(['text'=>json_encode($userArr)]);
                    return array('inviteNum'=>$userAttr->number);
                }
                return array('inviteNum'=>0);
            }
        }
    }
}