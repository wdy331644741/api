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

    private static $share_exp = "NetClassAnswer_share_exp";

    use DispatchesJobs;
    /**
     * 状态
     *
     * @JsonRpcMethod
     */
    public function classAnswerStatus() {
        global $userId;return $userId;
        $res = [];
        $done_res = [];//已经完成的攻略，用于排序

        $status_data = $userId?Attributes::getJsonText($userId,self::$_plan):[];

        $activits = ActivityService::GetActivityInfoByGroupStatus(self::$activity_grep);

        foreach ($activits as $key => $value) {

            $award_info = Activity::where('alias_name', $value['alias_name'])->with('awards')->first()->awards;
            if($award_info->isEmpty() ) {
                 $award_data['name'] = '奖品还没有上线';
            }else{
                $award_data = SendAward::_getAwardInfo($award_info[0]['award_type'],$award_info[0]['award_id']);
            }
            // $award_data = SendAward::_getAwardInfo($award_info[0]['award_type'],$award_info[0]['award_id']);
            $plan_key = str_replace(self::$_plan,'',$value['alias_name']);


            $answer_status = isset($status_data[$plan_key])?$status_data[$plan_key]:0;
            if(empty($value['des'])){
                throw new OmgException(OmgException::PARSE_ERROR);
            }
            $plan_status = pow(2,(int)$value['des']) - 1 - $answer_status;

            //根据答题状态判断  是否完成**************
            if ($key >0) {
                //上一plan 是否完成
                $befor_str = pow(2,$activits[$key-1]['des']) - 1 - (isset($status_data[$plan_key-1])?$status_data[$plan_key-1]:0);
                $str = !$befor_str?$plan_status?1:0:2;
            }else{
                $str = $plan_status?1:0;
            }
            //************************************


            //根据活动是否上线 强转状态
            if($value['enable'] == 0){
                $str = 3;
            }
            // $res[$plan_key] = [
            //     'answer_status' => $answer_status,
            //     'award' => $award_data['name'],
            //     'plan_status' => $str
            // ];


            //已完成 0，立即学习 1，待解锁 2 ，带上线
            //排序 优先展示 立即学习
            if(!$str){//已经完成
                array_push($done_res, [
                    'answer_status' => $answer_status,
                    'award' => $award_data['name'],
                    'plan_id' => $plan_key,
                    'plan_status' => $str
                ]);
            }else{
                array_push($res, [
                    'answer_status' => $answer_status,
                    'award' => $award_data['name'],
                    'plan_id' => $plan_key,
                    'plan_status' => $str
                ]);
            }
            

        }



        //plan状态：立即学习，待解锁，已完成，待上线
        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'is_login' => $userId?true:false,
                'plan_status' => array_merge($res,$done_res)
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
        if($this->isTooOften($userId, 300 ,1)) {
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

        $act = ActivityService::GetActivityInfoByAlias(self::$_plan.$plan);
        $status = pow(2,(int)$act['des']) - 1 - $answer;

        if(!empty($user_answered) && array_key_exists($plan, $user_answered)){
            if($user_answered[$plan] == $answer && $status == 0){//如果已经答对，返回false
                return false;
            }
            $user_answered[$plan] |= $answer;//与 位运算（算出最终状态）

        }else{
            $user_answered[$plan] = $answer;
        }

        Attributes::setItem($userId,self::$_plan,0,'',json_encode($user_answered));
        $this->isTooOften($userId, 300 ,0);//删除redis lock

        //是否全部答对、主动发奖
        $act = ActivityService::GetActivityInfoByAlias(self::$_plan.$plan);

        //放到任务调度里面去
        if($status == 0){
            $this->dispatch(new ActiveSendAwardJob($userId,self::$_plan.$plan) );
            // SendAward::ActiveSendAward($userId,self::$_plan.$plan);
        }
        
        return true;

    }


    /**
     * 分享给直抵红包
     *
     * @JsonRpcMethod
     */
    public function shareNetClass(){
        global $userId;

        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //按照别名发送奖励
        $this->dispatch(new ActiveSendAwardJob($userId ,self::$share_exp) );
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