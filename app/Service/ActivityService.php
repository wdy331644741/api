<?php
namespace App\Service;

use App\Models\Activity;
use App\Models\ActivityJoin;
use Lib\JsonRpcClient;

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
    static function getTradeAndRepamentTimes($userId){
        if($userId <= 0){
            return false;
        }
        $client = new JsonRpcClient(env('TRADE_HTTP_URL'));
        $secret = hash('sha256',$userId.'3d07dd21b5712a1c221207bf2f46e4ft');
        $res =  $client->getTradeAndRepamentTimes(array('user_id'=>$userId,'secret'=>$secret));
        return $res;
    }
}
