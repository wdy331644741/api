<?php
namespace App\Service;
use App\Models\MoneyShare;
use App\Models\MoneyShareInfo;
use App\Service\SendAward;
use App\Service\SendMessage;
use Lib\JsonRpcClient;
use Config;
use Validator;
use DB;

class MoneyShareBasic
{
    //格式化数据
    static function sendAward($userId, $awardType, $awardId, $money, $mallId){
        $awardTableObj = SendAward::_getAwardTable($awardType);
        $awardInfo = $awardTableObj->where('id', $awardId)->first();
        if(empty($awardInfo)) {
            return false;
        }
        
        switch($awardType){
            case 3:
                $awards = array();
                $awards['id'] = $awardInfo->id;
                $awards['user_id'] = $userId;
                $awards['uuid'] = SendAward::create_guid();
                $awards['main_id'] = $mallId;
                $awards['source_id'] = Config::get("activity.money_share_batch")+$mallId;
                
                $awards['name'] = $money.'体验金';
                //体验金额
                $awards['amount'] = $money;
                //有效时间
                if ($awardInfo->effective_time_type == 1) {
                    $awards['effective_start'] = date("Y-m-d H:i:s");
                    $awards['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $awardInfo->effective_time_day . " days"));
                } elseif ($awardInfo->effective_time_type == 2) {
                    $awards['effective_start'] = $awardInfo->effective_time_start;
                    $awards['effective_end'] = $awardInfo->effective_time_end;
                }
                $awards['source_name'] = "红包分享";
                $awards['platform'] = $awardInfo->platform_type;
                $awards['limit_desc'] = $awardInfo->limit_desc;
                $awards['remark'] = '';
                $awards['mail'] = $awardInfo->mail;
                $awards['message'] = $awardInfo->message;
                $return = self::experience($awards);
                return $return;
                break;
        }
        return false;
    }


    /**
     * 红包算法
     * 
     * @param $remain int 剩余金额
     * @param $remainNumber int 剩余数量
     * @param $min int 最小值
     * @param $max int 最大值
     * @return int
     */
    static function getRandomMoney($remain, $remainNumber, $min, $max) {
        if($remainNumber == 1) {
            return $remain;
        }
        $randomRemain = $remain - $remainNumber*$min;
        if($randomRemain <= 0 ) {
            return $min;
        }

        $average =  floor($randomRemain/$remainNumber);
        $randomMax = $max-$min;
        
        // 最大值最低为平均值2倍
        $randomMax = $average*2 > $randomMax ? $average*2 : $randomMax;
        // 最大值不能大于剩余
        $randomMax = $randomMax > $randomRemain ? $randomRemain : $randomMax;
        
        $goodLuck =  rand(0, $randomMax);
        if($goodLuck < $average) {
            return rand($average, $randomMax)+$min;   
        }else{
            return rand(0, $average)+$min; 
        }
    }
    
    //体验金发送
    static function experience($award){
        //验证必填
        $validator = Validator::make($award, [
            'id' => 'required|integer|min:0',
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'amount' => 'required|integer|min:1',
            'effective_start' => 'required|date',
            'effective_end' => 'required|date',
        ]);
        if($validator->fails()){
            $result['status'] = false;
            $result['msg'] = $validator->errors()->first();         
            return $result;
        }
        $result = ['status' => true, 'award' => $award, 'message_status' => 0, 'mail_status' => 0, 'remark' => []];
        
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        
        //发送体验金
        $expRes = $client->experience($award);
        
        //发送消息
        if (isset($expRes['result']) && $expRes['result']) {//成功
            $msgRes = self::sendMessage($award);
            return array_merge($result, $msgRes);
        }else{ //失败
            $result['status'] = false; 
            $result['remark'] = $expRes;
            return $result;
        }
    }
    /**
     * 发送站内信及添加日志
     * @param $info
     * @return array
     */
    static function sendMessage($info){
        $message = array();
        $message['sourcename'] = $info['source_name'];
        $message['awardname'] = $info['name'];
        $message['code'] = isset($info['code']) ? $info['code'] : '';

        $return = [];
        
        $result = ['message_status' => 0, 'mail_status' => 0, 'remark' => []];
        if(!empty($info['message'])){
            //发送短信
            $return['message'] = SendMessage::Message($info['user_id'], $info['message'], $message);
            //发送成功
            if($return['message'] == true){
                $result['message_status'] = 1;
            }
            //发送失败
            if($return['message'] == false){
                $result['message_status'] = 0;
            }
        }else{
            //发送模板为空
            $result['message_status'] = 0;
        }
        
        if(!empty($info['mail'])){
            //发送站内信
            $return['mail'] = SendMessage::Mail($info['user_id'], $info['mail'], $message);
            //发送成功
            if($return['mail'] == true){
                $result['mail_status'] = 1;
            }
            //发送失败
            if($return['mail'] == false){
                $result['mail_status'] = 0;
            }
        }else{
            //发送模板为空
            $result['mail_status'] = 0;
        }
        $result['remark'] = $return;

        return $result;
    }
}