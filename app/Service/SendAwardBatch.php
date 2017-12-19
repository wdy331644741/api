<?php
namespace App\Service;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/5
 * Time: 14:35
 */
use App\Http\Requests\Request;
use App\Models\Activity;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\AwardCash;
use App\Models\Coupon;
use App\Models\CouponCode;
use Lib\JsonRpcClient;
use App\Models\SendRewardLogBatch;
use App\Service\SendMessage;
use Config;
use Validator;
class SendAwardBatch
{
    /**
     * @需要提出去
     * @param $userID ，$award_type,$award_id
     *
     */
    static function sendDataRole($userID,$uniArray,$award_type, $award_id, $activityID = 0, $sourceName = '',$batch_id = 0,$unSendID = 0)
    {
        //获取数据
        $table = self::_getAwardTable($award_type);
        if($award_type == 6){
            $info = $table::where('id', $award_id)->where('is_del', 0)->select()->get()->toArray();
        }else{
            $info = $table::where('id', $award_id)->select()->get()->toArray();
        }

        if(empty($info)){
            return '奖品不存在';
        }
        if(count($info) >= 1){
            $info = $info[0];
        }
        //来源id
        $info['source_id'] = $activityID;
        //获取出活动的名称
        $activity = Activity::where('id',$activityID)->select('name','trigger_type')->first();
        //来源名称
        $info['source_name'] = isset($activity['name']) ? $activity['name'] : $sourceName;
        //触发类型
        $info['trigger'] = isset($activity['trigger_type']) ? $activity['trigger_type'] : '-2';
        //用户id
        if(empty($userID)){
            return '用户id不存在';
        }
        $info['user_id'] = $userID;
        $info['uni'] = $uniArray;
        //批次id
        if(!empty($batch_id)){
            $info['batch_id'] = $batch_id;
        }
        //补发id
        if(!empty($unSendID)){
            $info['unSendID'] = $unSendID;
        }
        if ($award_type == 1) {
            //加息券
            return self::increases($info);
        } elseif ($award_type == 2) {
            if ($info['red_type'] == 1 || $info['red_type'] == 3) {
                //直抵红包
                return self::redMoney($info);
            } elseif ($info['red_type'] == 2){
                //百分比红包
                return self::redMaxMoney($info);
            }
        } elseif ($award_type == 3) {
            //体验金
            return '暂无';
//            return self::experience($info);
        } elseif ($award_type == 4) {
            //用户积分
            return '暂无';
//            return self::integral($info);
        } elseif ($award_type == 6) {
            //优惠券
            return '暂无';
//            return self::coupon($info);
        }
    }
    //加息券
    static function increases($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 1;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'rate_increases' => 'required|numeric|between:0.0001,1',
            'rate_increases_type' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('rate_increases_time', 'required|integer', function($input) {
            return $input->rate_increases_type == 2;
        });
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //加息券
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = null;
        $data['uni'] = $info['uni'];
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = $info['rate_increases_type'];//直抵红包
        $data['rate'] = $info['rate_increases'];//加息值
        if ($info['rate_increases_type'] == 2) {
            $data['continuous_days'] = $info['rate_increases_time'];//加息天数
        }
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['source_name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['trigger'] = $info['trigger'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->interestCoupon($data);
            //发送消息&存储到日志
            if (isset($result['result']['status']) && $result['result']['status']) {//成功
                //存储到日志
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $result['result']['fail'] = isset($result['result']['fail']) ? $result['result']['fail'] : $result;
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result['result']['fail'],'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //直抵红包
    static function redMoney($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 2;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //直抵红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = null;
        $data['uni'] = $info['uni'];
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = 1;//直抵红包
        $data['amount'] = $info['red_money'];//红包金额
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['trigger'] = $info['trigger'];
        if($info['red_type'] == 3){
            $data['is_novice'] = 1;
        }
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']['status']) && $result['result']['status']) {//成功
                //存储到日志&发送消息
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $result['result']['fail'] = isset($result['result']['fail']) ? $result['result']['fail'] : $result;
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result['result']['fail'],'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    //百分比红包
    static function redMaxMoney($info){
        //添加info里添加日志需要的参数
        $info['award_type'] = 2;
        $info['uuid'] = null;
        $info['status'] = 0;
        $validator = Validator::make($info, [
            'id' => 'required|integer|min:1',
            'user_id' => 'required|min:1',
            'source_id' => 'required|integer|min:0',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|integer|min:1',
            'percentage' => 'required|numeric|between:0.001,1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            //记录错误日志
            $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'params_fail'.$validator->errors()->first());
            $info['remark'] = json_encode($err);
            self::addLog($info);
            return $err;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //百分比红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = null;
        $data['uni'] = $info['uni'];
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $info['project_duration_time'];
        }
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = 2;//百分比红包
        $data['max_amount'] = $info['red_money'];//红包最高金额
        $data['percentage'] = $info['percentage'];//红包百分比
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['investment_threshold'] = $info['investment_threshold'];
        $data['source_name'] = $info['name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['trigger'] = $info['trigger'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //发送消息&存储到日志
            if (isset($result['result']) && $result['result']) {//成功
                //存储到日志&发送消息
                $arr = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>true);
                $info['status'] = 1;
                $info['remark'] = json_encode($arr);
                self::sendMessage($info);
                return $arr;
            }else{//失败
                //记录错误日志
                $result['result']['fail'] = isset($result['result']['fail']) ? $result['result']['fail'] : $result;
                $err = array('award_id'=>$info['id'],'award_name'=>$info['name'],'award_type'=>$info['award_type'],'status'=>false,'err_msg'=>'send_fail','err_data'=>$result['result']['fail'],'url'=>$url);
                $info['remark'] = json_encode($err);
                self::addLog($info);
                return $err;
            }
        }
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    static function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 7) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award3;
            } elseif ($awardType == 4) {
                return new Award4;
            } elseif ($awardType == 5) {
                return new Award5;
            } elseif ($awardType == 6){
                return new Coupon;
            } elseif ($awardType == 7){
                return new AwardCash;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     *  根据awardType获取奖品详情
     *
     * @param $awardType
     * @param $awardId
     * @return mixed
     */

    static function getAward($awardType, $awardId) {
        $table = self::_getAwardTable($awardType);
        return $table->where('id', $awardId)->first();
    }

    //生成Guid
    static function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
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
                $info['message_status'] = 2;
            }
            //发送失败
            if($return['message'] == false){
                $info['message_status'] = 1;
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
                $info['mail_status'] = 2;
            }
            //发送失败
            if($return['mail'] == false){
                $info['mail_status'] = 1;
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
    static function addLog($info){
        $SendRewardLog = new SendRewardLogBatch;
        $data['user_id'] = $info['user_id'];
        $data['activity_id'] = $info['source_id'];
        $data['source_id'] = $info['source_id'];
        $data['award_type'] = $info['award_type'];
        $data['uuid'] = $info['uuid'];
        $data['remark'] = $info['remark'];
        $data['award_id'] = $info['id'];
        $data['status'] = $info['status'];
        $data['coupon_code'] = isset($info['code']) ? $info['code'] : '';
        $data['message_status'] = isset($info['message_status']) ? $info['message_status'] : '';
        $data['mail_status'] = isset($info['mail_status']) ? $info['mail_status'] : '';
        $data['created_at'] = date("Y-m-d H:i:s");
        if(!empty($info['unSendID'])){
            if(empty($info['status'])){
                //取出失败的信息和新的错误信息合并
                $remark = $SendRewardLog->where('id',$info['unSendID'])->select('remark')->get()->toArray();
                $remark = isset($remark[0]['remark']) && !empty($remark[0]['remark']) ? $remark[0]['remark'] : array();
                if(!empty($remark)){
                    $unRemark = '['.$remark.','.$info['remark'].']';
                }else{
                    $unRemark = $info['remark'];
                }
                $SendRewardLog->where('id',$info['unSendID'])->update(array('remark'=>$unRemark));
                return true;
            }
            //修改为补发成功状态
            $SendRewardLog->where('id',$info['unSendID'])->update(array('status'=>'2','remark'=>$info['remark']));
            return true;
        }
        if(isset($info['batch_id']) && !empty($info['batch_id'])){
            $data['batch_id'] = $info['batch_id'];
        }
        $insertID = $SendRewardLog->insertGetId($data);
        return $insertID;
    }
}