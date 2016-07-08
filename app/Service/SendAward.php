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
use App\Models\Coupon;
use Lib\JsonRpcClient;
use App\Models\SendRewardLog;
use Config;
use Validator;
class SendAward
{
    static private $userID;
    static private  $activityID;
    static private  $money;
    /**
     * @需要提出去
     * @param $userID ，$award_type,$award_id
     *
     */
    static function sendDataRole($userID,$award_type, $award_id, $activityID = 0)
    {
        self::$userID = $userID;
        self::$activityID = $activityID;
        //获取数据
        $table = self::_getAwardTable($award_type);
        $info = $table::where('id', $award_id)->select()->get()->toArray();
        if(count($info) >= 1){
            $info = $info[0];
        }
        //来源id
        $info['source_id'] = $activityID;
        //获取出活动的名称
        $activity = Activity::where('id',$activityID)->select('name')->get()->toArray();
        //来源名称
        $info['source_name'] = isset($activity[0]['name']) ? $activity[0]['name'] : '';
        //用户id
        $info['user_id'] = $userID;
        if ($award_type == 1) {
            //加息券
            return self::increases($info);
        } elseif ($award_type == 2) {
            if ($info['red_type'] == 1) {
                //直抵红包
                return self::redMoney($info);
            } elseif ($info['red_type'] == 2){
                //百分比红包
                return self::redMaxMoney($info);
            }
        } elseif ($award_type == 3) {
            //体验金
            return self::experience($info);
        } elseif ($award_type == 6) {
            //优惠券
        }
    }
    //加息券
    static function increases($info){
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:1',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'rate_increases' => 'required|numeric|between:0.0001,1',
            'rate_increases_type' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:1',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('rate_increases_day', 'required|integer', function($input) {
            return $input->rate_increases_type == 2;
        });
        $validator->sometimes(array('rate_increases_start','rate_increases_end'), 'required|date', function($input) {
            return $input->rate_increases_type == 3;
        });
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            return false;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.account_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //加息券
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
        $data['name'] = $info['name'];//奖品名称
        $data['type'] = $info['rate_increases_type'];//直抵红包
        $data['rate'] = $info['rate_increases'];//加息值
        if ($info['rate_increases_type'] == 2) {
            $data['continuous_days'] = $info['rate_increases_day'];//加息天数
        } elseif ($info['rate_increases_type'] == 3) {
            $data['increases_start'] = $info['rate_increases_start'];//加息天数
            $data['increases_end'] = $info['rate_increases_end'];//加息天数
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
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->interestCoupon($data);
            //存储到日志
            if ($result['result']) {
                self::addLog($data['source_id'], 3, $data['uuid'], $data['remark'], $data['user_id']);
                return true;
            }else{
                return false;
            }
        }
    }
    //直抵红包
    static function redMoney($info){
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:1',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:1',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            return false;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.account_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //直抵红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
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
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //存储到日志
            if ($result['result']) {
                self::addLog($data['source_id'], 3, $data['uuid'], $data['remark'], $data['user_id']);
            }else{
                return false;
            }
        }
    }
    //百分比红包
    static function redMaxMoney($info){
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:1',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'red_max_money' => 'required|integer|min:1',
            'percentage' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:1',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            return false;
        }
        //获取出来该信息
        $data = array();
        $url = Config::get("award.account_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //百分比红包
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['project_ids'] = str_replace(";", ",", $info['product_id']);//产品id
        $data['project_type'] = $info['project_type'];//项目类型
        $data['project_duration_type'] = $info['project_duration_type'];//项目期限类型
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
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->redpacket($data);
            //存储到日志
            if ($result['result']) {
                self::addLog($data['source_id'], 3, $data['uuid'], $data['remark'], $data['user_id']);
            }else{
                return false;
            }
        }
    }
    //体验金
    static function experience($info){
        //验证必填
        $validator = Validator::make($info, [
            'user_id' => 'required|integer|min:1',
            'source_id' => 'required|integer|min:1',
            'name' => 'required|min:2|max:255',
            'source_name' => 'required|min:2|max:255',
            'experience_amount_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
        ]);
        $validator->sometimes('effective_time_day', 'required|integer', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            return false;
        }
        $data = array();
        $url = Config::get("award.account_http_url");
        $client = new JsonRpcClient($url);
        $uuid = self::create_guid();
        //体验金
        $data['user_id'] = $info['user_id'];
        $data['uuid'] = $uuid;
        $data['source_id'] = $info['source_id'];
        $data['name'] = $info['name'];
        //体验金额
        $data['amount'] = $info['experience_amount_money'];
        if ($info['effective_time_type'] == 1) {
            $data['effective_start'] = date("Y-m-d H:i:s");
            $data['effective_end'] = date("Y-m-d H:i:s", strtotime("+" . $info['effective_time_day'] . " days"));
        } elseif ($info['effective_time_type'] == 2) {
            $data['effective_start'] = $info['effective_time_start'];
            $data['effective_end'] = $info['effective_time_end'];
        }
        $data['source_name'] = $info['source_name'];
        $data['platform'] = $info['platform_type'];
        $data['limit_desc'] = $info['limit_desc'];
        $data['remark'] = '';
        if (!empty($data) && !empty($url)) {
            //发送接口
            $result = $client->experience($data);
            //存储到日志
            if ($result['result']) {
                self::addLog($data['source_id'], 4, $data['uuid'], $data['remark'], $data['user_id']);
            }else{
                return false;
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
                return new Award2;
            } elseif ($awardType == 4) {
                return new Award3;
            } elseif ($awardType == 5) {
                return new Award4;
            } elseif ($awardType == 6) {
                return new Award5;
            } elseif ($awardType == 7){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 添加到日志
     * @param $source_id
     * @param $award_type
     * @param $uuid
     * @param $remark
     * @return mixed
     */
    static function addLog($source_id,$award_type,$uuid,$remark,$userID){
        $SendRewardLog = new SendRewardLog;
        $data['user_id'] = $userID;
        $data['activity_id'] = $source_id;
        $data['source_id'] = $source_id;
        $data['award_type'] = $award_type;
        $data['uuid'] = $uuid;
        $data['remark'] = $remark;
        $data['created_at'] = date("Y-m-d H:i:s");
        $insertID = $SendRewardLog->insertGetId($data);
        return $insertID;
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
}