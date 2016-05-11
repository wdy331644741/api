<?php

namespace App\Jobs;

use App\Models\Award;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\Coupon;
use App\Models\SendRewardLog;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Lib\JsonRpcClient;

class SendReward extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    private $activityID;
    private $userID;
    private $triggerName;
    private $money;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($activityID,$userID,$triggerName)
    {
        $this->activityID = intval($activityID);
        $this->userID = intval($userID);
        $this->triggerName = $triggerName;
        $this->money = 1000;//投资金额以后会用触发
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //判断规则是否符合
        $rule = true;

        //查找奖品发送
        if ($rule) {
            //符合
            //查询全部奖品
            $awardList = $this->awardList($this->activityID);
            //是否存在奖品
            if($awardList && !empty($awardList)){
                //是否有匹配的奖品
                $awardArray = array();
                if(count($awardList) >= 1){
                    foreach($awardList as $item){
                        if($item['name'] === $this->triggerName){
                            $awardArray[] = $item['info'];
                        }
                    }
                    //调用发送奖励接口
                    if(!empty($awardList) && count($awardList) >= 1){
                        //刘奇接口
                        $this->sendDataRole($awardList);
                    }
                }
            }
        }
    }
    /**
     * 获取奖品映射关系列表
     * @param $activityID
     * @return \Illuminate\Http\JsonResponse
     */
    function awardList($activityID){
        //活动ID
        $where['activity_id'] = intval($activityID);
        if(empty($where['activity_id'])){
            return '';
        }
        $list = Award::where($where)->orderBy('updated_at','desc')->get()->toArray();
        foreach($list as &$item){
            $table = $this->_getAwardTable($item['award_type']);
            $info = $table::where('id',$item['award_id'])->select()->get()->toArray();
            if(count($info) >= 1 && isset($info[0]['name'])){
                $item['name'] = $info[0]['name'];
                $item['info'] = $info[0];
                $item['info']['award_type'] = $item['award_type'];
            }else{
                $item['name'] = '';
                $item['info'] = array();
                $item['info']['award_type'] = 0;
            }
        }
        return $list;
    }
    function sendDataRole($info){
        if(!empty($info)){
            foreach($info as $k=>$v){
                $data = array();
                $url = "http://liuqi.wlpassport.dev.wanglibao.com/service.php?c=reward";
                $client = new JsonRpcClient($url);
                $uuid = $this->create_guid();
                if($v['info']['award_type'] == 1){
                    //加息券
                    $data['user_id'] = $this->userID;
                    $data['uuid'] = $uuid;
                    $data['source_id'] = $v['info']['id'];
                    $data['project_ids'] = str_replace(";",",",$v['info']['product_id']);//产品id
                    $data['project_type'] = $v['info']['project_type'];//项目类型
                    $data['project_duration_type'] = $v['info']['project_duration_type'];//项目期限类型
                    $data['name'] = $v['info']['name'];//奖品名称
                    $data['type'] = $v['info']['rate_increases_type'];//直抵红包
                    $data['rate'] = $v['info']['rate_increases'];//加息值
                    if($v['info']['rate_increases_type'] == 2){
                        $data['continuous_days'] = $v['info']['rate_increases_day'];//加息天数
                    }elseif($v['info']['rate_increases_type'] == 3){
                        $data['increases_start'] = date("Y-m-d",$v['info']['rate_increases_start']);//加息天数
                        $data['increases_end'] = date("Y-m-d",$v['info']['rate_increases_end']);//加息天数
                    }
                    if($v['info']['effective_time_type'] == 1){
                        $data['effective_start'] = date("Y-m-d");
                        $data['effective_end'] = date("Y-m-d",strtotime("+".$v['info']['effective_time_day']." days"));
                    }elseif($v['info']['effective_time_type'] == 2){
                        $data['effective_start'] = date("Y-m-d",$v['info']['effective_time_start']);
                        $data['effective_end'] = date("Y-m-d",$v['info']['effective_time_end']);
                    }
                    $data['investment_threshold'] = $v['info']['investment_threshold'];
                    $data['platform'] = $v['info']['platform_type'];
                    $data['limit_desc'] = $v['info']['limit_desc'];
                    $data['remark'] = '';
                    if(!empty($data) && !empty($url)){
                        //发送接口
                        $result = $client->interestCoupon($data);
                        //存储到日志
                        if($result['result']['err_code'] == 0){
                            $this->addLog($data['source_id'],3,$data['uuid'],$data['remark']);
                        }
                    }
                }elseif($v['info']['award_type'] == 2){
                    //直抵红包
                    $data['user_id'] = $this->userID;
                    $data['uuid'] = $uuid;
                    $data['source_id'] = $v['info']['id'];
                    $data['project_ids'] = str_replace(";",",",$v['info']['product_id']);//产品id
                    $data['project_type'] = $v['info']['project_type'];//项目类型
                    $data['project_duration_type'] = $v['info']['project_duration_type'];//项目期限类型
                    $data['name'] = $v['info']['name'];//奖品名称
                    $data['type'] = 1;//直抵红包
                    $data['amount'] = $v['info']['red_money'];//红包金额
                    if($v['info']['effective_time_type'] == 1){
                        $data['effective_start'] = date("Y-m-d");
                        $data['effective_end'] = date("Y-m-d",strtotime("+".$v['info']['effective_time_day']." days"));
                    }elseif($v['info']['effective_time_type'] == 2){
                        $data['effective_start'] = date("Y-m-d",$v['info']['effective_time_start']);
                        $data['effective_end'] = date("Y-m-d",$v['info']['effective_time_end']);
                    }
                    $data['investment_threshold'] = $v['info']['investment_threshold'];
                    $data['platform'] = $v['info']['platform_type'];
                    $data['limit_desc'] = $v['info']['limit_desc'];
                    $data['remark'] = '';
                    if(!empty($data) && !empty($url)){
                        //发送接口
                        $result = $client->redpacket($data);
                        //存储到日志
                        if($result['result']['err_code'] == 0){
                            $this->addLog($data['source_id'],3,$data['uuid'],$data['remark']);
                        }
                    }
                }elseif($v['info']['award_type'] == 3){
                    //百分比红包
                    $data['user_id'] = $this->userID;
                    $data['uuid'] = $uuid;
                    $data['source_id'] = $v['info']['id'];
                    $data['project_ids'] = str_replace(";",",",$v['info']['product_id']);//产品id
                    $data['project_type'] = $v['info']['project_type'];//项目类型
                    $data['project_duration_type'] = $v['info']['project_duration_type'];//项目期限类型
                    $data['name'] = $v['info']['name'];//奖品名称
                    $data['type'] = 2;//百分比红包
                    $data['max_amount'] = $v['info']['red_money'];//红包最高金额
                    $data['percentage'] = $v['info']['percentage'];//红包百分比
                    if($v['info']['effective_time_type'] == 1){
                        $data['effective_start'] = date("Y-m-d");
                        $data['effective_end'] = date("Y-m-d",strtotime("+".$v['info']['effective_time_day']." days"));
                    }elseif($v['info']['effective_time_type'] == 2){
                        $data['effective_start'] = date("Y-m-d",$v['info']['effective_time_start']);
                        $data['effective_end'] = date("Y-m-d",$v['info']['effective_time_end']);
                    }
                    $data['investment_threshold'] = $v['info']['investment_threshold'];
                    $data['platform'] = $v['info']['platform_type'];
                    $data['limit_desc'] = $v['info']['limit_desc'];
                    $data['remark'] = '';
                    if(!empty($data) && !empty($url)){
                        //发送接口
                        $result = $client->redpacket($data);
                        //存储到日志
                        if($result['result']['err_code'] == 0){
                            $this->addLog($data['source_id'],3,$data['uuid'],$data['remark']);
                        }
                    }
                }elseif($v['info']['award_type'] == 4){
                    //体验金
                    $data['user_id'] = $this->userID;
                    $data['uuid'] = $uuid;
                    $data['source_id'] = $v['info']['id'];
                    $data['name'] = $v['info']['name'];
                    if($v['info']['experience_amount_type'] == 1){
                        //固定
                        $data['amount'] = $v['info']['experience_amount_money'];
                    }elseif($v['info']['experience_amount_type'] == 2){
                        //倍数
                        $data['amount'] = $this->money * $v['info']['experience_amount_multiple'];
                    }
                    if($v['info']['effective_time_type'] == 1){
                        $data['effective_start'] = date("Ymd");
                        $data['effective_end'] = date("Ymd",strtotime("+".$v['info']['effective_time_day']." days"));
                    }elseif($v['info']['effective_time_type'] == 2){
                        $data['effective_start'] = date("Ymd",$v['info']['effective_time_start']);
                        $data['effective_end'] = date("Ymd",$v['info']['effective_time_end']);
                    }
                    $data['platform'] = $v['info']['platform_type'];
                    $data['limit_desc'] = $v['info']['limit_desc'];
                    $data['remark'] = '';
                    if(!empty($data) && !empty($url)){
                        //发送接口
                        $result = $client->experience($data);
                        //存储到日志
                        if($result['result']['err_code'] == 0){
                            $this->addLog($data['source_id'],4,$data['uuid'],$data['remark']);
                        }
                        var_dump($result['result']['err_code']);exit;
                    }
                }

            }
        }
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    function _getAwardTable($awardType){
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
    function addLog($source_id,$award_type,$uuid,$remark){
        $SendRewardLog = new SendRewardLog;
        $data['user_id'] = $this->userID;
        $data['activity_id'] = $this->activityID;
        $data['source_id'] = $source_id;
        $data['award_type'] = $award_type;
        $data['uuid'] = $uuid;
        $data['remark'] = $remark;
        $insertID = $SendRewardLog->insertGetId($data);
        return $insertID;
    }
    //生成Guid
    function create_guid()
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
