<?php
namespace App\Service;

use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\ActivityGroup;
use App\Models\HdAssistance;
use DB,Cache;

class ActivityService
{
    /**
     * 活动是否存在
     *
     * @param $aliasName
     * @return boolean
     */
    static function isExistByAlias($aliasName) {
        $res = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where(['enable' => 1, 'alias_name' => $aliasName])->first();
        return !!$res;
    }
    /**
     * 根据活动别名获取活动id
     *
     * @param $aliasName
     * @return boolean
     */
    static function GetActivityInfoByAlias($aliasName) {
        $res = Activity::where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where(['enable' => 1, 'alias_name' => $aliasName])->first();
        return $res;
    }
    /**
     * 根据活动别名获取活动id-/去掉时间限制
     *
     * @param $aliasName
     * @return boolean
     */
    static function GetActivityedInfoByAlias($aliasName) {
        $res = Activity::where(['enable' => 1, 'alias_name' => $aliasName])->first();
        return $res;
    }
    /**
     * 活动是否参与过
     *
     * @param $aliasName
     * @return boolean
     */
    static function isExistByAliasUserID($aliasName,$userId) {
        $activityID = self::GetActivityInfoByAlias($aliasName);
        $activityID = isset($activityID['id']) && !empty($activityID['id']) ? $activityID['id'] : '';
        if(empty($activityID)){
            return false;
        }
        $where = array();
        $where['user_id'] = $userId;
        $where['activity_id'] = $activityID;
        $where['status'] = 3;
        $count = ActivityJoin::where($where)->get()->count();
        return $count;
    }

    /**
     * 获取分组内活动id
     *
     * @param $aliasName
     * @return array
     */
    static function GetActivityInfoByGroup($alias) {
        // $res = DB::table('')
        //根据别名 查出id
        $act_group_id = ActivityGroup::where('name',$alias)->first();
        //连接模型 查出分组下的 活动id
        $res = ActivityGroup::find($act_group_id['id'])->activities()
        ->where(
            function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            }
        )->where(
            function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            }
        )->where(['enable' => 1])->get();
        return $res;
    }

    /**
     *
     */
    static function AssistanceRealName($userId){
        if($userId <= 0 ){
            return false;
        }
        //查询邀请人id
        $info = Func::getUserBasicInfo($userId,1);
        if(isset($info['from_user_id']) && $info['from_user_id'] > 0){
            //事物开始
            DB::beginTransaction();
            //判断是否参与助力活动
            $userInfo = HdAssistance::where("group_user_id",$info['from_user_id'])->where("user_id",$userId)->where("status",0)->lockForUpdate()->first();
            if(!isset($userInfo['pid'])){
                DB::rollBack();//回滚事物
                return false;
            }
            //判断助力的团是否已满
            $groupInfo = HdAssistance::where("id",$userInfo['pid'])->where("status",0)->lockForUpdate()->first();
            if(isset($groupInfo['group_num']) && $groupInfo['group_num'] < 3){
                //用户实名状态修改
                $userInfo->status = 1;
                $userInfo->save();
                //团人员加1
                $groupInfo->increment("group_num",1);
                $groupInfo->save();
                //判断是否满团
                if($groupInfo->group_num == 3){
                    //添加满团时间
                    $groupInfo->status = 1;
                    $groupInfo->complete_time = date("Y-m-d H:i:s");
                    $groupInfo->save();
                }
                //提交事物
                DB::commit();
                //发送短信和push
                $msg = "您的助力团已成功，可以去填写收货地址等待接收礼品啦！";
                SendMessage::Mail($groupInfo['group_user_id'],$msg);
                SendMessage::Mail($userId,$msg);
                //删除我的团缓存
                Cache::forget("user_assistance_".$groupInfo['group_user_id']);
                Cache::forget("user_assistance_".$userId);
                return true;
            }else{
                DB::rollBack();//回滚事物
                return false;
            }
        }
        return false;
    }
}
