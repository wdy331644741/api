<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\SendRewardLog;
use App\Service\SendAward;
use App\Models\UserAttribute;
use Validator;


class ActivityJsonRpc extends JsonRpc {
    
    /**
     * 领取分享奖励
     *      
     * @JsonRpcMethod
     */
    public function signinShare() {
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
        $awardName = $res['award_name'];
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isShared' => false,
                'award' => [$awardName],
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
        if(isset($remark['continue'])) {
            $continue = intval($remark['continue']);
        }else {
            $continue = 0; 
        }

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
        $awardName = $res[0]['award_name'];
        
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'isAward' => false,
                'awards' => [$awardName],
            ),
        );

    }

    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin() {
        global $userId;
        return $this->innerSignin($userId);
    }

    public function innerSignin($userId) {
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
            if(isset($remark['continue'])) {
                $continue = intval($remark['continue']);
            }else {
                $continue = 0;
            }

            // 是否分享
            $shared = $this->isShared($userId);

            //获取额外奖励记录
            $before = date('Y-m-d 00:00:00', time() - 3600*24*($continue-1));

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
            $extra = $this->getExtraAwards($userId, $before, $end);
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => array(
                    'isSignin' => true,
                    'current' => $continue,
                    'start' => $start,
                    'end' => $end,
                    'extra' => $extra,
                    'shared' => $shared,
                    'last' => $last,
                    'award' => [$award['name']],
                ),
            );
         }
        
        // 发奖
        $res = SendAward::addAwardByActivity($userId, $activity['id']);
        $awardName = $res[0]['award_name'];

        // 连续登陆
        $yesterdayRes = SendRewardLog::where($where)->whereRaw("date(created_at) = '{$yesterday}'")->first();
        if(!empty($yesterdayRes)){
            $remark = json_decode($yesterdayRes['remark'], true);
            if(isset($remark['continue'])) {
                $continue = intval($remark['continue']);
            }else {
                $continue = 0;
            }
            $continue += 1;
            if($continue > 28) {
                $continue = 1;
            }
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
        $extra = $this->getExtraAwards($userId, $before, $end);

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
                'award' => [$awardName],
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
        }
        throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
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


    /**
     * 获取闯关状态
     *
     * @JsonRpcMethod
     */
    public function getStatus($params) {
        global $userId;
        if (empty($params->key)) {
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $json = UserAttribute::where(['user_id'=>$userId,'key'=>$params->key])->value('text');
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => json_decode($json)
        );
    }

}