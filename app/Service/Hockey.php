<?php
namespace App\Service;


use App\Models\HdHockeyCardMsg;
use App\Models\UserAttribute;
use Config;
class Hockey
{
     
    /**
     * 投资或者邀请好友送卡接口
     *
     * @param int 用户id 
     * @param num 卡数量
     * 
     * @return int
     */
    static function HockeyCardObtain($userId, $amount ) {
        $config = Config::get("hockey");
        $userAttr = isset($config['user_attr']) ? $config['user_attr'] : [];
        $key = isset($config['card_key']) ? $config['card_key'] : '';
        $num = intval($amount/10000);
        $isExist = 0;
        if($userId > 0 && $num > 0 && !empty($key) && !empty($userAttr)){
            $attr = UserAttribute::where(['user_id'=>$userId,'key'=>$key])->first();
            if(isset($attr['string'])){
                $isExist = 1;
                $userAttr = json_decode($attr['string'],1);
            }
            //添加卡
            for($i=1;$i<=$num;$i++){
                $max = array_search(max($userAttr),$userAttr);
                $min = array_search(min($userAttr),$userAttr);
                foreach($userAttr as $k => $item){
                    if($userAttr[$max] == $userAttr[$min]){
                        $userAttr[$k] += 1;
                        break;
                    }else{
                        if($item < $userAttr[$max]){
                            $userAttr[$min] += 1;
                            break;
                        }
                    }
                }
            }
        }
        //添加卡片次数
        if($isExist == 1){
            $attr->string = json_encode($userAttr);
            $attr->increment('number', $num);
            $attr->save();
        }else{
            UserAttribute::create(
                ['user_id' => $userId, 'key' => $key,  'number' => $num, 'string'=>json_encode($userAttr)]
            );
        }
        //添加投资获取卡片接口
        $userInfo = Func::getUserBasicInfo($userId);
        $phone = isset($userInfo['username']) ? trim($userInfo['username']) : '';
        $displayName = substr_replace($phone, '******', 3, 6);
        $msg = "恭喜".$displayName."投资".$amount."元，获取".$num."张卡片";
        $remark['attr'] = json_encode($userAttr);
        $remark['user_info'] = $userInfo;
        $id = HdHockeyCardMsg::insertGetId(['user_id'=>$userId,'msg'=>$msg,'remark'=>json_encode($remark),'type'=>1,'created_at'=>date("Y-m-d H:i:s")]);
        return $id;
    }

    /**
     * 投资或者邀请好友送卡接口
     *
     * @param int 用户id
     * @param num 卡数量
     *
     * @return int
     */
    static function HockeyGuessObtain($userId, $amount,$invite) {

    }


}