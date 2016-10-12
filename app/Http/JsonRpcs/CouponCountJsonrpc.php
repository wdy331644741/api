<?php
namespace App\Http\JsonRpcs;
use App\Models\SendRewardLog;
use App\Models\Coupon;
use App\Exceptions\OmgException as OmgException;

class CouponCountJsonRpc extends JsonRpc {
    
    /**
     *  获取得到的金锤数
     *
     * @JsonRpcMethod
     */
    public function getCouponCount($params) {
        $user_id = intval($params->user_id);
        if(empty($user_id)){
            throw new OmgException(OmgException::VALID_USERID_FAIL);
        }
        $award_ids = Coupon::where('name','LIKE','%金锤%')->where('is_del',0)->select('id')->get()->toArray();
        if(empty($award_ids)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $id = array_column($award_ids, 'id');
        $count = SendRewardLog::where('user_id',$user_id)->where('award_type',6)->whereIn('award_id',$id)->where('status','>=',1)->count();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => array('count'=>$count)
        );
    }
}
