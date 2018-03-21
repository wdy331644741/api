<?php
namespace App\Service;

use Request;
use App\Models\Activity;
use App\Service\SendAward;
use App\Service\ActivityService;

class CarnivalRedMoneyService
{
    private $awardConfig = [
        'id'=>0,//无奖品配置
        'red_type'=>1,
        'red_money'=>null,//红包金额
        'percentage'=>0,
        'effective_time_type'=>1,//红包有效天数
        'effective_time_day'=>7,
        'effective_time_start'=>null,
        'effective_time_end'=>null,
        'investment_threshold'=>0,
        'project_duration_type'=>1,
        'project_type'=>null,
        'product_id'=>0,
        'platform_type'=>0,
        'limit_desc'=>null,
        'project_duration_time'=>0,
        'mail'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
        'message'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
        //**********以上是红包配置
        //**********以下是用户中心发奖参数
        'source_id'=>'',
        'name'=>'',
        'source_name'=>'',
        'trigger'=>null,////////////////////////
        'user_id'=>''
    ];
    /**
     * 瓜分发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($amount, $userId,$activity){
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 1, json_encode(array('err_msg' => 'pass frequency')));
        }
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');
        //直抵红包相关参数
        $this->awardConfig['red_money'] = $amount;
        $this->awardConfig['source_id'] = $activity['id'];
        $this->awardConfig['name'] = $amount."元直抵红包";
        $this->awardConfig['source_name'] = $activity['name'];
        $this->awardConfig['user_id'] = $userId;

        $result = SendAward::redMoney($this->awardConfig);
        //添加活动参与记录
        if($result['status']){
            SendAward::addJoins($userId,$activity,3);
        }
    }

    /**
     * 排名20榜
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendListAward($userId,$activity){
        foreach ($userId as $key => $value) {
            // $this->sendAward();
        }
        if (!SendAward::frequency($userId, $activity)) {//不通过
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 1, json_encode(array('err_msg' => 'pass frequency')));
        }
        //*****活动参与人数加1*****
        Activity::where('id',$activity['id'])->increment('join_num');
    }
}
