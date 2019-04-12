<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\Activity;
use Illuminate\Support\Facades\Redis;
use Validator;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\ActiveSendAwardJob;//异步主动发奖

class NetClassAnswerJsonRpc extends JsonRpc {
    
    private static $_plan = "NetClassAnswer";
    private static $activity_grep = "NetClassAnswer_group";

    use DispatchesJobs;
    /**
     * 状态
     *
     * @JsonRpcMethod
     */
    public function classAnswerStatus() {
        global $userId;
        $res = [];

        $status_data = $userId?Attributes::getJsonText($userId,self::$_plan):[];

        $activits = ActivityService::GetActivityInfoByGroup(self::$activity_grep);

        foreach ($activits as $key => $value) {

            $award_info = Activity::where('alias_name', $value['alias_name'])->with('awards')->first()->awards;
            $award_data = SendAward::_getAwardInfo($award_info[0]['award_type'],$award_info[0]['award_id']);
            $plan_key = str_replace(self::$_plan,'',$value['alias_name']);


            $answer_status = isset($status_data[$plan_key])?$status_data[$plan_key]:0;
            if(empty($value['des'])){
                throw new OmgException(OmgException::PARSE_ERROR);
            }
            $plan_status = pow(2,$value['des']) - 1 - $answer_status;

            if ($key >0) {
                //上一plan 是否完成
                $befor_str = pow(2,$activits[$key-1]['des']) - 1 - (isset($status_data[$plan_key-1])?$status_data[$plan_key-1]:0);
                $str = $befor_str?'待解锁':'立即学习';
            }else{
                $str = $plan_status?'立即学习':'已完成';
            }

            // $res[$plan_key] = [
            //     'answer_status' => $answer_status,
            //     'award' => $award_data['name'],
            //     'plan_status' => $str
            // ];
            //已完成 0，立即学习 1，待解锁 2 ，带上线
            array_push($res, [
                'answer_status' => $answer_status,
                'award' => $award_data['name'],
                'plan_id' => $plan_key,
                'plan_status' => $str
            ]);

        }

        //plan状态：立即学习，待解锁，已完成，待上线
        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'is_login' => $userId?true:false,
                'plan_status' => $res
            ]
        ];
    }

    /**
     * 提交答案
     *
     * @JsonRpcMethod
     */
    public function postClassAnswer($params) {
        global $userId;

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //攻略
        $plan = $params->plan;
        $answer = $params->answer;

        //是否触发间隔限制 500毫秒
        if($this->isTooOften($userId, 500 ,1)) {
            throw new OmgException(OmgException::API_BUSY);
        }

        //答案
        $validator = Validator::make([
            'plan' => $plan,
            'answer' => $answer,
        ], [
            'plan' => 'required|integer',
            'answer' => 'required|digits_between:1,7',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        //[$params->plan=>$params->answer];

        $user_answered = Attributes::getJsonText($userId,self::$_plan);
        //不能跳着答题
        if($plan>1){
            $befor_act = $plan-1;
            $temp = ActivityService::GetActivityInfoByAlias(self::$_plan.$befor_act);

            if(!isset($user_answered[$befor_act]) || (pow(2,$temp['des']) - 1 - $user_answered[$befor_act]) >0 ){
                return '请完成上一攻略';
            }
        }

        if(!empty($user_answered) && array_key_exists($plan, $user_answered)){
            $user_answered[$plan] |= $answer;//与 位运算（算出最终状态）

        }else{
            $user_answered[$plan] = $answer;
        }

        Attributes::setItem($userId,self::$_plan,0,'',json_encode($user_answered));
        $this->isTooOften($userId, 500 ,0);//删除redis lock

        //是否全部答对、主动发奖
        $act = ActivityService::GetActivityInfoByAlias(self::$_plan.$plan);

        //放到任务调度里面去
        if( (pow(2,$act['des']) - 1 - $answer) ==0){
            $this->dispatch(new ActiveSendAwardJob($userId,self::$_plan.$plan) );
            // SendAward::ActiveSendAward($userId,self::$_plan.$plan);
        }
        
        return true;

    }


    //haomiao  锁毫秒
    //status 1加锁、0解锁
    private function isTooOften($user_id,$haomiao,$status = 1){
        $key = $user_id."_".self::$_plan;

        if($status){
            if(!Redis::EXISTS($key)){
                Redis::set($key,"网贷课堂答题500毫秒频繁lock");
                Redis::PEXPIRE($key,$haomiao);
                return false;
            }else{
                return true;
            }
        }else{
            Redis::del($key);
            return true;
        }
        
    }
}