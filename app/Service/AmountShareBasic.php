<?php
namespace App\Service;
use App\Models\AmountShare;
use App\Models\Activity;
use App\Models\UserAttribute;
use App\Service\Func;
use Lib\JsonRpcClient;
use Config;
use Validator;
use DB;

class AmountShareBasic
{
    /**
     *  生成现金分享数据
     *
     */
    static function amountShareCreate($triggerData) {
        $multiple = 0;
        if(isset($triggerData['user_id']) && isset($triggerData['Investment_amount']) && isset($triggerData['scatter_type']) && isset($triggerData['period'])){
            $countKey = '';
            if(($triggerData['scatter_type'] == 1 && $triggerData['period'] == 30) || ($triggerData['scatter_type'] == 2 && $triggerData['period'] == 1)){
                $multiple = 0.001;
                $countKey = "amount_share_1";
            }
            if($triggerData['scatter_type'] == 2 && $triggerData['period'] == 3){
                $multiple = 0.002;
                $countKey = "amount_share_3";
            }
            if($triggerData['scatter_type'] == 2 && $triggerData['period'] >= 6){
                $multiple = 0.003;
                $countKey = "amount_share_6";
            }
            if($triggerData['user_id'] <= 0 || $triggerData['Investment_amount'] < 100 || $multiple == 0){
                return 'params error';
            }
        }else{
            return 'params error';
        }

        //生成的现金红包金额
        $amountShare = $triggerData['Investment_amount']*$multiple;
        if($amountShare < 0.1){
           return 'amount error';
        }

        //统计各个标的的参与人数
        Attributes::increment($triggerData['user_id'],$countKey,1);

        //根据别名查询该活动是否开启
        $where['alias_name'] = "amount_share";
        $where['enable'] = 1;
        $isExist = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where($where)->count();

        if(!$isExist){
            return 'activity is end';
        }

        //添加到红包分享表
        $param['user_id'] = $triggerData['user_id'];
        $param['money'] = $amountShare;
        $param['total_num'] = 10;
        $param['min'] = 0.01;
        return self::addAmountShare($param);
    }
    /**
     * 添加到现金分享表中
     * @param $param
     * @return bool
     */
    static function addAmountShare($param){
        if($param['user_id'] <= 0 || $param['money'] <= 0){
            return false;
        }
        $endTime = ActivityService::GetActivityInfoByAlias('amount_share');
        $endTime = isset($endTime['end_at']) && !empty($endTime['end_at']) ? $endTime['end_at'] : "";
        //用户ID
        $data['user_id'] = $param['user_id'];
        //总金额
        $data['total_money'] = $param['money'];
        //总数量
        $data['total_num'] = $param['total_num'];
        //最小值
        $data['min'] = $param['min'];
        //最大值
        $data['max'] = 0;
        //红包标示
        $data['identify'] = "amount_share_".Func::randomStr(15);
        //开始时间
        $data['start_time'] = date("Y-m-d H:i:s");
        //结束时间
        $data['end_time'] = $endTime;
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        //生成的uri
        $data['uri'] = self::getAmountShareURI($data['identify']);
        $id = AmountShare::insertGetId($data);
        return array('id'=>$id,'result'=>$data);
    }
    static function getAmountShareURI($identify){
        $callbackURI = urlencode(env("APP_URL")."/active/share/share.html?k=".$identify);
        return env("MONEY_SHARE_WECHAT_URL").$callbackURI;
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

}