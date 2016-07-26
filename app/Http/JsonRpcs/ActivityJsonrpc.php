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
    public function signinShare($params) {
        global $userId;
        
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $signinName = 'signin';
        $signinActivity = Activity::where('alias_name', $signinName)->first();
        
        $sharedName = 'signinShared';
        $sharedActivity = Activity::where('alias_name', $sharedName)->first();
        $today = date('Y-m-d');
        
        
        //是否签到
        $signinRes = SendRewardLog::where(['user_id' => $userId, 'activity_id' => $signinActivity['id']])->whereRaw("date(created_at) = '{$today}'")->first();
        if(!$signinRes) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        $shared = $this->isShared($userId);
        //是否分享
        if($shared) {
            $award = SendAward::getAward($signinRes['award_type'], $signinRes['award_id']);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isShared' => true,
                    'award' => [$award['name']],
                ),
            );
        }

        //给用户发奖
        $res = SendAward::sendDataRole($userId, $signinRes['award_type'], $signinRes['award_id'], $sharedActivity['id']);
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isShared' => false,
                'award' => [$res],
            ),
        );


    }

    /**
     * 领取连续签到奖励
     * 
     * @JsonRpcMethod
     */
    public function signinDay($params) {
        global $userId;
        $today = date('Y-m-d', time());
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
        
        $signin = Activity::where('alias_name', 'signin')->first();
        
        $where = array(
            'user_id' => $userId,
            'activity_id' => $signin['id'],
        );

        $signinRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();

        if(!$signinRes) {
            throw new OmgException(OmgException::NOT_SIGNIN);
        }

        $remark = json_decode($signinRes['remark'], true);
        //获取连续签到天数
        $continue = intval($remark['continue']);

        if($day > $continue) {
            throw new OmgException(OmgException::DAYS_NOT_ENOUGH);
        }
        $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));

        $awardRes = SendRewardLog::where(array(
            'user_id'  => $userId,
            'activity_id' => $activity['id'],
        ))->whereRaw("created_at >= '{$before}'")->first();

        if($awardRes) {
            $award = SendAward::getAward($awardRes['award_type'], $awardRes['award_id']);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isAward' => true,
                    'awards' => [$award['name']],
                ),
            ); 
        }

        $res = SendAward::addAwardByActivity($userId, $activity['id']);
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isAward' => false,
                'awards' => $res,
            ),
        );

    }

    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin($params) {
        global $userId;
        $aliasName = 'signin';
        $days = array(7, 14, 21, 28);
        $daysLength = count($days);
        $last = $days[$daysLength-1];

        //是否登录
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $today = date('Y-m-d', time());
        $yesterday = date('Y-m-d', time() - 3600*24);
        $continue = 1;

        $activity = Activity::where('alias_name', $aliasName)->with('rules')->with('awards')->first();
        if(!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);   
        }
        
        // 今日是否签到
        $where = array(
            'user_id' => $userId,
            'activity_id' => $activity['id'],
        );
        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        if($todayRes) {
            $remark = json_decode($todayRes['remark'], true);
            $award = SendAward::getAward($todayRes['award_type'], $todayRes['award_id']);
            $continue = intval($remark['continue']);
            
            
            // 是否分享
            $shared = $this->isShared($userId);
            
            //获取额外奖励记录
            $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));

            $extra = $this->getExtraAwards($userId, $before, $continue);
            foreach($days as $key => $day) {
                if($continue <= $day){
                    if($key == 0) {
                        $start = 1;
                    }else{
                        $start = $days[$key-1] + 1;
                    }
                    $end = $day;
                    break;
                }
            }
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isSignin' => true,
                    'current' => $continue,
                    'start' => $start,
                    'end' => $end,
                    'extra' => $extra,
                    'shared' => false,
                    'last' => $last,
                    'award' => [$award['name']],
                ),
            );
            
            throw new OmgException(OmgException::ALREADY_SIGNIN, $data);
        }
        
        // 发奖
        $res = SendAward::addAwardByActivity($userId, $activity['id']);

        // 连续登陆
        $yesterdayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$yesterday}'")->first();
        if(!empty($yesterdayRes)){
            $remark = json_decode($yesterdayRes['remark'], true);
            $continue = $remark['continue'] + 1;
            if($continue > 28) {
                $continue = 1;
            }
        }
        
        // 强制修改连续登陆天数
        if(!empty($params->continue)) {
            $continue = $params->continue;
        }

        // 更新连续登陆天数
        $todayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$today}'")->first();
        $remark = json_decode($todayRes['remark'], true) ;
        $remark['continue'] = $continue;
        $todayRes->remark = json_encode($remark);
        $todayRes->save();
        
        
        foreach($days as $key => $day) {
            if($continue <= $day){
                if($key == 0) {
                    $start = 1;
                }else{
                    $start = $days[$key-1] + 1;
                }
                $end = $day;
                break;
            }            
        }

        // 获取额外奖励领取记录
        $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));
        $extra = $this->getExtraAwards($userId, $before, $continue);

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isSignin' => false,
                'current' => $continue,
                'start' => $start,
                'end' => $end,
                'extra' => $extra,
                'shared' => false,
                'award' => $res,
                'last' => $last,
            ),
        );
    }

    // 获取额外奖励领取记录
    private function getExtraAwards($userId, $before, $day) {
        $activity = Activity::where('alias_name', "signinDay_{$day}")->first();
        if($activity) {
            $awardRes = SendRewardLog::where(array(
                'user_id'  => $userId,
                'activity_id' => $activity['id'],
            ))->whereRaw("created_at >= '{$before}'")->first();
            if($awardRes){
                return true;
            }else{
                return false;
            }
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
    }
    
    // 今天是否分享
    private function isShared($userId) {
        $sharedName = 'signinShared';
        $sharedActivity = Activity::where('alias_name', $sharedName)->first();
        $today = date('Y-m-d', time());
        
        $sharedRes = SendRewardLog::where(['user_id' => $userId, 'activity_id' => $sharedActivity['id']])->whereRaw("date(created_at) = '{$today}'")->first();
        if($sharedRes) {
            return true;
        }
        return false;
    }

}