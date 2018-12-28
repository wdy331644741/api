<?php

namespace App\Http\JsonRpcs;


use App\Exceptions\OmgException;
use App\Models\SendRewardLog;
use App\Service\ActivityService;
use App\Service\Func;

class ChannelCibnJsonrpc extends JsonRpc
{

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function cibnList() {
        $aliasName = 'channel_cibn';
        $activity = ActivityService::GetActivityedInfoByAlias($aliasName);
        if (!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $data = SendRewardLog::select('user_id')->where(['activity_id'=>$activity->id, 'status'=>1])->orderBy('id', 'desc')->limit(20)->get();
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                unset($item['user_id']);
            }
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function hstvList() {
        $aliasName = 'channel_hstvbkshy';
        $activity = ActivityService::GetActivityedInfoByAlias($aliasName);
        if (!$activity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $data = SendRewardLog::select('user_id')->where(['activity_id'=>$activity->id, 'status'=>1])->orderBy('id', 'desc')->limit(20)->get();
        foreach ($data as &$item){
            if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                unset($item['user_id']);
            }
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

}

