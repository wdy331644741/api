<?php
namespace App\Service;

use App\Models\DaZhuanPan;
use Request;
use App\Models\Activity;
use App\Service\SendAward;

class RobRateCouponService
{
    /**
     * 发奖
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    static function sendAward($amount, $awardName, $userId, $activity) {
            //*****活动参与人数加1*****
            Activity::where('id',$activity->id)->increment('join_num');
            $info['id'] = $activity->id;
            $info['name'] = $awardName;
            $info['rate_increases'] = $amount / 100;//加息值
            $info['rate_increases_type'] = 1; //1 全周期  2 加息天数
            $info['effective_time_type'] = 1;//有效期类型 1有效天数
            $info['effective_time_day'] = 7;
            $info['investment_threshold'] = 0;//投资门槛
            $info['project_duration_type'] = 1;//项目期限类型 1不限
            $info['project_type'] = 0;//项目类型
            $info['platform_type'] = 0;
            $info['created_at'] = date("Y-m-d HH:ii:ss");
            $info['updated_at'] = date("Y-m-d HH:ii:ss");;
            $info['limit_desc'] = "";
            $info['product_id'] = "";
            //$info['rate_increases_start '] = ;
            //$info['rate_increases_end'] = ;
            //$info['effective_time_start'] = ;
            //$info['effective_time_end'] = ;
            //$info['rate_increases_time'] = ;
            //$info['project_duration_time'] = ;
            //$info['message'] = ;
            $info['mail'] = '恭喜你在"{{sourcename}}"活动中获得了"'.$info['name'].'全周期加息券"奖励,请在我的奖励中查看。';//邀请好友抢4%加息券
            $info['source_id'] = $activity->id;////来源id
            $info['source_name'] = isset($activity->name) ? $activity->name : '';//来源名称
            //触发类型
            $info['trigger'] = isset($activity->trigger_type) ? $activity->trigger_type : -1;
            //用户id
            $info['user_id'] = $userId;
            $info['award_type'] = 1;
            $info['uuid'] = null;
            $info['status'] = 0;
            $result = SendAward::increases($info);
            //添加到活动参与表 1频次验证不通过2规则不通过3发奖成功
            return SendAward::addJoins($userId, $activity, 3, json_encode($result));
    }

}
