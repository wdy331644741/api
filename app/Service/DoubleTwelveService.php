<?php
namespace App\Service;

use App\Models\HdTwelve;
use Config, Cache,DB;
use qcloudcos\Conf;


class DoubleTwelveService
{

    //用户随机中奖号码发放
//    public  static function addDrawNum($userId, $key)
//    {
//        $aliasName = Config::get('doubletwelve.alias_name');
//        $numDay = Attributes::getNumberByDay($userId, $key);
//        if ($numDay >=2) {
//            return false;
//        }
//        Attributes::incrementByDay($userId, $key);
//        Attributes::incrementByDay($userId, $aliasName);
//    }

    static function sendAward($userId, $award){
        $params['user_id'] = $userId;
        $params['source_id'] = 999999;
        $params['investment_threshold'] = $award['amount'];
        $params['project_duration_time'] = $award['period'];
        $params['limit_desc'] = "{$award['amount']}元起投，限{$award['period']}月及以上标";
        if ($award['period'] == 1) {
            $params['project_duration_time'] = 30;
            $params['project_duration_type'] = 6;
            $params['limit_desc'] = "{$award['amount']}元起投，限30日及以上标";

        }
        //有效期
        $params['effective_time_start'] = date("Y-m-d H:i:s");
        $params['effective_time_end'] = date("Y-m-d 23:59:59", strtotime("+" . ($award['effective_time_day'] -1 ) . " day"));
        switch ($award['type']) {
            case 'hongbao':
                $awardConfig = self::getRedParams();
                $awardConfig = array_merge($awardConfig, $params);
                $awardConfig['red_money'] = $award['awardName'];
                $awardConfig['name'] = '定制'.$award['name']."直抵红包";
                $result = SendAward::redMoney($awardConfig);
                break;
            case 'jiaxi':
                $awardConfig = self::getRateParams();
                $awardConfig = array_merge($awardConfig, $params);
                $awardConfig['rate_increases'] = $award['awardName'];
                $awardConfig['name'] = '定制'.$award['name'] . "加息券";
                $result = SendAward::increases($awardConfig);
                break;
            default :
                return false;
        }
        return HdTwelve::create([
            'user_id' => $userId,
            'award_name' => $awardConfig['name'],
            'status' => $result['status'] ? 1 : 0,
            'type' => $award['type'],
            'remark' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);
    }

    static function getRedParams()
    {
         return [
                'id'=>0,//无奖品配置
                'red_type'=>1,
                'red_money'=>null,//红包金额
                'percentage'=>0,
                'effective_time_type'=>2,//红包有效天数
//                'effective_time_day'=>null,
//                'effective_time_start'=>null,
//                'effective_time_end'=>null,
                'investment_threshold'=>0,
                'project_duration_type'=>3,
                'project_type'=>null,
                'product_id'=>0,
                'platform_type'=>0,
                'limit_desc'=>null,
                'project_duration_time'=>0,
                'mail'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
//                'message'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
                'message'=>'',
                //**********以上是红包配置
                //**********以下是用户中心发奖参数
                'source_id'=>'',
                'name'=>'',
                'source_name'=>'福利券定制',
                'trigger'=>null,////////////////////////
                'user_id'=>''
        ];
    }

    static function getRateParams()
    {
        return [
            'id'=>0,//无奖品配置
            'rate_increases'=>'',
            'rate_increases_type'=>1,
            'rate_increases_start'=>null,
            'rate_increases_end'=>null,
            'effective_time_type'=>2,//红包有效天数
//            'effective_time_day'=>null,
//            'effective_time_start'=>null,
//            'effective_time_end'=>null,
            'investment_threshold'=>0,
            'project_duration_type'=>3,
            'project_type'=>null,
            'product_id'=>0,
            'platform_type'=>0,
            'limit_desc'=>null,
            'project_duration_time'=>0,
            'mail'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
//                'message'=>"恭喜您在'{{sourcename}}'活动中获得{{awardname}}",
            'message'=>'',
            //**********以上是红包配置
            //**********以下是用户中心发奖参数
            'source_id'=>'',
            'name'=>'',
            'source_name'=>'福利券定制',
            'trigger'=>null,////////////////////////
            'user_id'=>'',
        ];
    }
}