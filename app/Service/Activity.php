<?php
namespace App\Service;

use App\Models\Activity as ActivityModal;

class Activity
{
    /**
     * 活动是否存在
     *
     * @param $aliasName
     * @return boolean
     */
    static function isExistByAlias($aliasName) {
        $res = ActivityModal::where(
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
}
