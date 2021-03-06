<?php
namespace App\Service;

use App\Models\HdDoubleEleven;
use App\Models\User;
use App\Models\UserAttribute;
use Config, Request;


class DoubleElevenService
{

    //签到-卡片
    public static function signInCard($userId, $key)
    {
        $number = Attributes::getNumber($userId, $key);
        if ($number >= 3) {
            return false;
        }
        $aliasName = Config::get('doubleeleven.alias_name');
        $default = self::getInitCard();
        $user_attr = UserAttribute::where(['user_id'=>$userId, 'key'=>$aliasName])->first();
        if(!$user_attr) {
            $user_attr = new UserAttribute();
            $user_attr->user_id  = $userId;
            $user_attr->key = $aliasName;
            $user_attr->text = json_encode($default);
            $user_attr->save();
        }
        $text = json_decode($user_attr->text, true);
        switch ($number) {
            case 0 :
                $text['wang'] = 1;
                break;
            case 1 :
                $text['li'] = 1;
                break;
            case 2 :
                $text['shuang'] = 1;
                break;
        }
//        $card_number = array_count_values($text);
//        $card_number = isset($card_number[1]) ? $card_number[1] : 0;
        $text = json_encode($text);
        $user_attr->text = $text;
        $user_attr->save();
        Attributes::increment($userId,$key);
    }

    //使用红包得到卡片
    public static function redPacketCard($userId, $number)
    {
        $aliasName = Config::get('doubleeleven.alias_name');
        $default = self::getInitCard();
        $user_attr = UserAttribute::where(['user_id'=>$userId, 'key'=>$aliasName])->first();
        if(!$user_attr) {
            $user_attr = new UserAttribute();
            $user_attr->user_id  = $userId;
            $user_attr->key = $aliasName;
            $user_attr->text = json_encode($default);
            $user_attr->save();
        }
        $text = json_decode($user_attr->text, true);
        if ($number == 1 && $text['eleven'] == 0) {
            $text['eleven'] = 1;
        } else if ($number == 2 && $text['kuang'] == 0) {
            if ($text['eleven'] == 0) {
                $text['eleven'] = 1;
            }
            $text['kuang'] = 1;
        } else if ($number == 3  && $text['huan'] == 0) {
            if ($text['eleven'] == 0) {
                $text['eleven'] = 1;
            }
            if ($text['kuang'] == 0) {
                $text['kuang'] = 1;
            }
            $text['huan'] = 1;
        } else if ($number >= 4 && $text['lai'] == 0) {
            if ($text['eleven'] == 0) {
                $text['eleven'] = 1;
            }
            if ($text['kuang'] == 0) {
                $text['kuang'] = 1;
            }
            if ($text['huan'] == 0) {
                $text['huan'] = 1;
            }
            $text['lai'] = 1;
        } else {
            return ;
        }
//        $card_number = array_count_values($text);
//        $card_number = isset($card_number[1]) ? $card_number[1] : 0;
        $text = json_encode($text);
        $user_attr->text = $text;
        $user_attr->save();
    }
    public static function getInitCard()
    {
        $cards = [
            'wang'=>0,//0待领取 1领取 2已领取
            'li'=>0,
            'shuang'=>0,
            'eleven'=>0,
            'kuang'=>0,
            'huan'=>0,
            'lai'=>0,
            'xi'=>0,
        ];
        return $cards;
    }
    /**
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($userId, $type) {
        $remark = [];
        $aliasName = Config::get('doubleeleven.alias_name');
        $template = "恭喜您在'乐赚双十一 狂欢不剁手'活动中获得'{{awardname}}'奖励。";
        //谢谢参与
        if ($type == 3 || $type == 5) {
            $award_aliasname = $type == 3 ? $aliasName . '_jifen100' : $aliasName . '_jiaxi18';
            $award_name = $type == 3 ? '100积分' : '1.8%加息券';
            // 根据别名发活动奖品
            $awards = SendAward::ActiveSendAward($userId, $award_aliasname);
            $remark['award'] = $awards;
            $status = 0;
            if(isset($awards[0]['award_name']) && $awards[0]['status']) {
                $status = 1;
            }
            return HdDoubleEleven::create([
                'user_id' => $userId,
                'award_name' => $award_name,
                'alias_name' => $award_aliasname,
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => $status,
                'type' => 'activity',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
        } else if($type == 7 ) {
            $awardName = "200元京东卡";
            $ret = HdDoubleEleven::create([
                'user_id' => $userId,
                'award_name'=> $awardName,
                'alias_name' => 'jingdongka',
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'virtual',
                'remark' => '',
            ]);
            $arr = ['awardname'=>$awardName];
            SendMessage::Mail($userId, $template, $arr);
            SendMessage::Message($userId, $template, $arr);
            return $ret;
        } else if($type == 8 ) {
            $awardName = "1111元专属返现";
            $ret = HdDoubleEleven::create([
                'user_id' => $userId,
                'award_name' => $awardName,
                'alias_name' => 'fanxian',
                'uuid' => '',
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => 'virtual',
                'remark' => json_encode($remark, JSON_UNESCAPED_UNICODE),
            ]);
            //短信站内信
            $arr = ['awardname'=>$awardName];
            SendMessage::Mail($userId, $template, $arr);
            SendMessage::Message($userId, $template, $arr);
            return $ret;
        }
        return false;
    }
}