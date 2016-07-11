<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Service\SendAward;

class ActivityJsonRpc extends JsonRpc {
    
    /**
     * 签到
     *
     * @JsonRpcMethod
     */
    public function signin($params) {
        global $userId;
        $aliasName = 'signin';
        $activity = Activity::where('alias_name', $aliasName)->with('rules')->with('awards')->first();
        $awards = $activity['awards'];
        $priority = 0;
        foreach($awards as $award) {
            $priority += $award['priority'];
        }
        $target = rand(1, $priority);
        foreach($awards as $award) {
            $target = $target - $award['priority'];    
            if($target <= 0) {
                break;        
            }
        }
        $res = SendAward::sendDataRole($userId, $award['award_type'], $award['award_id'], $activity['id'] );
        
         
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        );
    }
}