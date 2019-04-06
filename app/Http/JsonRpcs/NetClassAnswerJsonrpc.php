<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SendAward;
use App\Models\Activity;
use Illuminate\Support\Facades\Redis;
use Validator;

class NetClassAnswerJsonRpc extends JsonRpc {
    
    private static $_plan = "NetClassAnswer";
    private static $activity_grep = "NetClassAnswer_group";

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
            $res[$plan_key] = [
                'award' => $award_data['name'],
            ];
        }
        return $res;
        //plan状态：立即学习，待解锁，已完成，待上线
        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                'is_login' => $userId?true:false,
                'plan_status' => [
                    1=>0,
                    2=>-1,
                    3=>1,
                ]
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
            'plan' => $params->plan,
            'answer' => $params->answer,
        ], [
            'plan' => 'required|integer',
            'answer' => 'required|digits_between:1,7',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $plan_status = [$params->plan=>$params->answer];

        $user_answered = Attributes::getJsonText($userId,self::$_plan);

        if(!empty($user_answered) && array_key_exists($plan, $user_answered)){
            $user_answered[$plan] |= $answer;//与 位运算（算出最终状态）

        }else{
            $user_answered[$plan] = $answer;
        }

        Attributes::setItem($userId,self::$_plan,0,'',json_encode($user_answered));
        $this->isTooOften($userId, 500 ,0);//删除redis lock
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