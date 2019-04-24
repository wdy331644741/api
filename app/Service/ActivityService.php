<?php
namespace App\Service;

use App\Models\Activity;
use App\Models\ActivityJoin;
use App\Models\ActivityGroup;

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
     * 获取分组内活动id 去掉时间限制
     *
     * @param $aliasName
     * @return array
     */
    static function GetActivityInfoByGroupStatus($alias) {
        // $res = DB::table('')
        //根据别名 查出id
        $act_group_id = ActivityGroup::where('name',$alias)->first();
        //连接模型 查出分组下的 活动id
        $res = ActivityGroup::find($act_group_id['id'])->activities()
        ->get();
        return $res;
    }
}
