<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\ActivityService;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Validator, Config, Request;

class YearEndJsonRpc extends JsonRpc
{
	/**
     *  年终状态
     *
     * @JsonRpcMethod
     */
    public function userYearEndStatus(){
    	global $userId;
    	// 活动是否存在
    	$activityKey = "yearEndBill";
        if(ActivityService::isExistByAlias($activityKey)) {
            $activity['available'] = 1;
        }else{
        	throw new OmgException(OmgException::ACTIVITY_IS_END);
        }
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if($userId) {
            $userInfo = ActivityService::isExistByAliasUserID($activityKey,$userId);
        }
        return array(
	            'message' => 'success',
	            'data' => $userInfo
	        );
    }


    /**
     *  兑换加息券
     *
     * @JsonRpcMethod
     */
    public function getYearBillsCoupon(){
    	global $userId;
    	// 活动是否存在
    	$activityKey = "yearEndBill";

        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if($userId) {
        	$res = SendAward::ActiveSendAward($userId,$activityKey);
        }

        if(isset($res[0]['status']) && $res[0]['status'] == true){
        	return array(
	            'code' => 0,
	            'message' => 'success',
	            'data' => $res
	        );
        }else{
        	return array(
	            'code' => -1,
	            'message' => 'failed',
	            'data' => $res
	        );
        }
        
    }
}