<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\SendRewardLog;
use App\Service\SendAward;
use Validator;


class ActivityJsonRpc extends JsonRpc {
    
    /**
     * 发奖
     *
     * @JsonRpcMethod
     */
    public function sendAward($params) {

        $res = SendAward::sendDataRole($params->userId, $params->awardType, $params->awardId, 6);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        );
    }
    
    /**
     * 领取分享奖励
     *      
     * @JsonRpcMethod
     */
    public function signinShared() {
        global $userId;
        $userId = 1313;

        $aliasName = 'signin';
        $day = 3600*24 * 9;
        $today = date('Y-m-d', time() + $day);
        $where = [
            'user_id' => $userId,
            'alias_name' => $aliasName,
        ];

        $activityJoin = new ActivityJoin;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
    }

    /**
     * 领取连续签到奖励
     * 
     * @JsonRpcMethod
     */
    public function signinDay($params) {

        global $userId;
        $userId = 1313;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $validator = Validator::make((array)$params, [
            'day' => 'required|integer',
        ]);
        if($validator->fails()){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }

        $day = $params->day;
        $aliasName = "signinDay_{$day}";
        $activity = Activity::where('alias_name', $aliasName)->first();
        
        if(!$activity) {
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        
        $where = [
            'user_id' => $userId,
            'alias_name' => $aliasName,
        ];       
         
        
        $today = date('Y-m-d', time() + $day);


        $activityJoin = new ActivityJoin;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
    }

    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin($params) {
        global $userId;
        $userId = 1313;
        $aliasName = 'signin';

        //是否登录
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $day = 3600*24 * 0;
        $today = date('Y-m-d', time() + $day);
        $yesterday = date('Y-m-d', time() - 3600*24 + $day);
        $continue = 1;

        $activity = Activity::where('alias_name', $aliasName)->with('rules')->with('awards')->first();
        if(!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);   
        }
        
        $where = array(
            'user_id' => $userId,
            'activity_id' => $activity['id'],
        );
        
        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        if($todayRes) {
            $remark = json_decode($todayRes['remark'], true) ;
            $continue = $remark['continue'];
            $award = SendAward::getAward($todayRes['award_type'], $todayRes['award_id']);
            $data = array(
                'continue' => $continue,
                'award' => $award['name'],
            );
            throw new OmgException(OmgException::ALREADY_SIGNIN, $data);
        }
        
        $res = SendAward::addAwardByActivity($userId, $activity['id']);

        $yesterdayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$yesterday}'")->first();

        if(!empty($yesterdayRes)){
            $remark = json_decode($yesterdayRes['remark'], true);
            $continue = $remark['continue'] + 1;
            if($continue > 28) {
                $continue = 1;
            }
        }

        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        $remark = json_decode($todayRes['remark'], true) ;
        $remark['continue'] = $continue;
        $todayRes->remark = json_encode($remark);
        $todayRes->save();

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'continue' => $continue,    
                'award' => $res,
            ),
        );
    }

}