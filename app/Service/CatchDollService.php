<?php
namespace App\Service;

use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use Config;
use DB;

class CatchDollService
{
    //SendAward.php 邀请注册送 2次机会
    // 被邀请者 送1次机会
    static function registerGiveChange($userId,$inc = 2) {
        
        $actInfo = ActivityService::GetActivityedInfoByAlias(self::$attr_key);
        if(isset($actInfo) ){
            if(!empty($actInfo->start_at) && $reference_date < $actInfo->start_at){
                return "活动未开始";
            }else if(!empty($actInfo->end_at) && $reference_date >= $actInfo->end_at){
                return "活动已结束";
            }
        }
        // $attr = $this->getChanceCounts($userId);//起更新作用
        DB::beginTransaction();
        $attr = UserAttribute::where(array('user_id' => $userId, 'key' => self::$attr_key))
                        // ->whereDate('updated_at','=',date("Y-m-d"))
                        ->lockForUpdate()
                        ->first();
        if(isset($attr->updated_at) ){
            if($attr->updated_at < date('Y-m-d')){
                $attr->number = 2+$inc;//加上初始化的2次机会
            }else{
                $attr->number += $inc;//
            }

            $attr->save();
        }else{
            UserAttribute::create([
                'user_id' => $userId,
                'number'  => 2+$inc ,//加上初始化的2次机会,
                'key'     => self::$attr_key,
            ]);

        }
        DB::commit();
        return 0;

    }
}
