<?php
namespace App\Service;

use App\Models\DiyIncreases;
use App\Service\Func;
use App\Service\Attributes;
use App\Models\UserAttribute;
use Config;
use Illuminate\Foundation\Auth\User;

class DiyIncreasesBasic
{

    /**
     * 根据用户id添加到用户属性表
     *
     * @param $userId,$fromUserId,$source,$num
     * @return bool
     */
    static function _DIYIncreasesAdd($userId ,$fromUserId ,$source = '',$num = 0) {
        if($userId <= 0 || $fromUserId <= 0 || empty($source) || $num <= 0){
            return false;
        }
        //获取配置
        $config = Config::get("diyIncreases");
        if(empty($config)){
            return false;
        }
        //投资金额为0是注册并绑卡
        $amount = 0;
        //如果是投资就$num为投资金额
        if($source == '投资'){
            foreach($config['config_list'] as $key =>$item){
                if($num >= $item['min'] && $num <= $item['max']){
                    $amount = $num;
                    $num = $key;
                }
            }
        }
        if($num > 10){
            return false;
        }
        //按照传来的$num给用户添加加息值
        $id = self::setUserAttributesItem($fromUserId,$config['key'],$num,$config);
        if($id === false){
            return false;
        }
        //添加到日志
        $log = [
            'increases_id' => $id,
            'user_id' => $fromUserId,
            'invite_user_id' => $userId,
            'amount' => $amount,
            'source' => $source,
            'number' => $num,
            'created_at' => date("Y-m-d H:i:s")
        ];
        $id = DiyIncreases::insertGetId($log);
        return $id;
    }

    static function _DIYIncreasesSend($id,$userId){
        //获取配置
        $config = Config::get("diyIncreases");
        if(empty($config)){
            return false;
        }
        //判断领取是否超过三次
        if($id <= 0){
            $num = Attributes::getNumber($userId,$config['num_key']);
            if($num >= 3){
                return false;
            }
        }
        //获取好友累加的加息值
        $res = UserAttribute::where(['id' => $id])->first();
        //判断是否领取过
        if($id > 0 && isset($res->text) && $res->text == 1){
            return false;
        }
        //给用户累加加息券
        if(isset($res->number) && $res->number > 0){
            $increases = $config['default_value'] + $res->number;
            if($increases > 35){
                $increases = 35;
            }
            $increases = $increases / 10;
        }else{
            $increases = $config['default_value'] / 10;
        }
        $awards['id'] = 0;
        $awards['user_id'] = $userId;
        $awards['source_id'] = $config['source_id'];
        $awards['name'] = $increases.'%加息券';
        $awards['source_name'] = "DIY全周期加息";
        $awards['rate_increases'] = $increases/100;
        $awards['rate_increases_type'] = 1;
        $awards['effective_time_type'] = 1;
        $awards['effective_time_day'] = 7;
        $awards['investment_threshold'] = 0;
        $awards['product_id'] = '';
        $awards['project_type'] = 1;
        $awards['project_duration_type'] = 1;
        $awards['platform_type'] = 0;
        $awards['mail'] = "恭喜您在'".$awards['source_name']."'活动中获得了'".$awards['name']."'奖励。";
        $awards['limit_desc'] = '';
        $awards['trigger'] = '';
        $status = SendAward::increases($awards);
        return $status;

    }

    /**
     * 操作用户属性表
     * @param $userId
     * @param $key
     * @param $number
     * @param $config
     * @return bool|mixed
     */
    static function setUserAttributesItem($userId,$key,$number,$config){
        //获取最新的一个领取完的是第几个
        $thisNum = UserAttribute::where(['user_id'=>$userId,'key'=>$key,'text' => 1])->select('string')->orderBy('id','desc')->first();
        if(empty($thisNum)){
            $thisNum['string'] = 0;
        }
        $thisNum = $thisNum['string'];
        //判断是否超过了活动期间的领取限制
        if($thisNum >= $config['max_num']){
            return false;
        }
        //判断新的加息券是否存在
        $isExist = UserAttribute::where(['user_id'=>$userId,'key'=>$key,'string' => $thisNum+1])->first();
        if(isset($isExist['id']) && $isExist['id'] > 0){
            if($isExist->number >= 20){
                return false;
            }
            //存在
            $isExist->increment('number', $number);
            $isExist->save();
            return $isExist->id;
        }
        $attribute = new UserAttribute();
        $attribute->user_id = $userId;
        $attribute->key = $key;
        $attribute->string = $thisNum+1;
        $attribute->number = $number;
        $attribute->text = 0;
        $attribute->save();
        return $attribute->id;
    }
}
