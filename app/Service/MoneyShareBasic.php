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
    //发送奖品
    static function sendMoney($id,$userId){
        //判断商品是否正常
        $isExist = array();
        $isExist['id'] = $id;
        $isExist['status'] = 1;
        $mallInfo = MoneyShare::where($isExist)->first();
        if(empty($mallInfo)){
            return array("status"=>false,"msg"=>"红包分享商品不存在","code"=>-1);
        }
        //判断有没有给该用户发送过
        $isJoin = array();
        $isJoin['user_id'] = $userId;
        $isJoin['main_id'] = $id;
        $join = MoneyShareInfo::where($isJoin)->count();
        if(!empty($join)){
            return array("status"=>false,"msg"=>"已经领取该奖品","code"=>-2);
        }
        /*******发送奖品*******/
        //获取该奖品信息
        if(empty($mallInfo->award_type) || empty($mallInfo->award_id)){
            return array("status"=>false,"msg"=>"数据有误","code"=>-3);
        }
        $awardTableObj = SendAward::_getAwardTable($mallInfo->award_type);
        $awardInfo = $awardTableObj->where('id', $mallInfo->award_id)->first();
        if(empty($awardInfo)){
            return array("status"=>false,"msg"=>"配置的奖品不存在","code"=>-4);
        }
        $awardInfo->user_id = $userId;
        //开始发奖
        $status = self::formatData($mallInfo,$awardInfo);
        if(isset($status['status']) && $status['status'] === true){
            return array("status"=>true,"msg"=>"发奖成功");
        }
        return array("status"=>false,"msg"=>"发奖失败","code"=>-5);
    }
    //格式化数据
    static function formatData($mallInfo,$awardInfo){
        if(empty($mallInfo->award_type) || empty($mallInfo->award_id) || empty($mallInfo) || empty($awardInfo)){
            return false;
        }
        switch($mallInfo->award_type){
            case 3:
                $awards = array();
                $awards['id'] = $awardInfo->id;
                $awards['user_id'] = $awardInfo->user_id;
                $awards['uuid'] = SendAward::create_guid();
                $awards['main_id'] = $mallInfo->id;
                $awards['source_id'] = Config::get("activity.money_share_batch")+$mallInfo->id;
                //根据算法获得体验金额
                $money = 1000;
                //如果是补发就用原有金额
                if(isset($awardInfo->unSendID) && !empty($awardInfo->unSendID)){
                    $money = $awardInfo->amount;
                    $awards['unSendID'] = $awardInfo->unSendID;
                }
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
    }
    //体验金发送
    static function experience($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 3;
        $info['status'] = 0;
        //验证必填
        $validator = Validator::make($info, [
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
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //体验金
        $data['user_id'] = $info['user_id'];
        $data['source_id'] = $info['source_id'];
        $data['name'] = $info['name'];
        $data['uuid'] = $info['uuid'];
        //体验金额
        $data['amount'] = $info['amount'];
        $data['effective_start'] = $info['effective_start'];
        $data['effective_end'] = $info['effective_end'];
        $data['source_name'] = $info['source_name'];
        $data['platform'] = $info['platform'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->experience($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //发送消息&存储日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                //修改主表
                MoneyShare::where("id",$info['main_id'])->increment("use_money",$data['amount']);
                MoneyShare::where("id",$info['main_id'])->increment("receive_num");
                return $arr;
            }else{//失败
                //记录错误日志
                $info['uuid'] = null;
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result,'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
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
        $return = array();
        $info['message_status'] = 0;
        $info['mail_status'] = 0;
        if(!empty($info['message'])){
            //发送短信
            $return['message'] = SendMessage::Message($info['user_id'],$info['message'],$message);
            //发送成功
            if($return['message'] == true){
                $info['message_status'] = 1;
            }
            //发送失败
            if($return['message'] == false){
                $info['message_status'] = 0;
            }
        }else{
            //发送模板为空
            $info['message_status'] = 0;
        }
        if(!empty($info['mail'])){
            //发送站内信
            $return['mail'] = SendMessage::Mail($info['user_id'],$info['mail'],$message);
            //发送成功
            if($return['mail'] == true){
                $info['mail_status'] = 1;
            }
            //发送失败
            if($return['mail'] == false){
                $info['mail_status'] = 0;
            }
        }else{
            //发送模板为空
            $info['mail_status'] = 0;
        }
        //添加日志
        self::addLog($info);
        return $return;
    }
    /**
     * 添加到日志
     * @param $source_id
     * @param $award_type
     * @param $uuid
     * @param $remark
     * @return mixed
     */
    static protected function addLog($info){
        $MoneyShareInfo = new MoneyShareInfo();
        $data['user_id'] = $info['user_id'];
        $data['main_id'] = $info['main_id'];
        $data['uuid'] = $info['uuid'];
        $data['money'] = $info['amount'];
        $data['source_id'] = $info['source_id'];
        $data['award_type'] = $info['award_type'];
        $data['award_id'] = $info['id'];
        $data['remark'] = $info['remark'];
        $data['message_status'] = isset($info['message_status']) ? $info['message_status'] : '';
        $data['mail_status'] = isset($info['mail_status']) ? $info['mail_status'] : '';
        $data['status'] = $info['status'];
        $data['created_at'] = date("Y-m-d H:i:s");
        if(!empty($info['unSendID'])){
            if(empty($info['status'])){
                //取出失败的信息和新的错误信息合并
                $remark = $MoneyShareInfo->where('id',$info['unSendID'])->select('remark')->get()->toArray();
                $remark = isset($remark[0]['remark']) && !empty($remark[0]['remark']) ? $remark[0]['remark'] : array();
                if(!empty($remark)){
                    $unRemark = '['.$remark.','.$info['remark'].']';
                }else{
                    $unRemark = $info['remark'];
                }
                $MoneyShareInfo->where('id',$info['unSendID'])->update(array('remark'=>$unRemark,'updated_at'=>date("Y-m-d H:i:s")));
                return true;
            }
            //修改为补发成功状态
            $MoneyShareInfo->where('id',$info['unSendID'])->update(array('status'=>'2','remark'=>$info['remark'],'updated_at'=>date("Y-m-d H:i:s")));
            return true;
        }
        $insertID = $MoneyShareInfo->insertGetId($data);
        return $insertID;
    }
}