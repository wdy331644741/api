<?php
namespace App\Http\JsonRpcs;
use App\Models\SendRewardLog;
use Lib\JsonRpcClient;
use App\Models\Coupon;
use App\Exceptions\OmgException as OmgException;

class CouponCountJsonRpc extends JsonRpc {
    
    /**
     *  获取改用户得到的金锤数
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
    /**
     *  获取最新得到的金锤数
     *
     * @JsonRpcMethod
     */
    public function getNewUserCouponCount() {
        $award_ids = Coupon::where('name','LIKE','%金锤%')->where('is_del',0)->select('id')->get()->toArray();
        if(empty($award_ids)){
            throw new OmgException(OmgException::NO_DATA);
        }

        $id = array_column($award_ids, 'id');
        //获取最新收到金锤的用户id
        $user_id = SendRewardLog::where('award_type',6)->whereIn('award_id',$id)->where('status','>=',1)->select('user_id')->orderBy('id', 'desc')->take(50)->get()->toArray();
        if(empty($user_id)){
            throw new OmgException(OmgException::NO_DATA);
        }
        $user_id = array_unique(array_column($user_id, 'user_id'));
        //获取该用户收到多少金锤
        foreach($user_id as $uid){
            $count = SendRewardLog::where('award_type',6)->whereIn('award_id',$id)->where('status','>=',1)->where('user_id',$uid)->count();
            //根据用户ID获取真实姓名
            $url = env('INSIDE_HTTP_URL');
            $client = new JsonRpcClient($url);
            if(empty($uid)){
                throw new OmgException(OmgException::NO_DATA);
            }
            $userBase = $client->userBasicInfo(array('userId'=>$uid));
            $phone = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : '';
            if(!empty($phone)){
                $phone = substr_replace($phone, '*****', 3, 5);
            }
            $data[] = $phone." 获得金锤码".$count."个！";
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data
        );
    }
}
