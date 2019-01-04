<?php

namespace App\Http\JsonRpcs;


use App\Models\Activity;
use App\Models\SendRewardLog;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\Func;
use App\Service\SendAward;
use Config,DB;

class RedEnvelopesJsonRpc extends JsonRpc {


    /**
     * 红包领取状态
     *
     * @JsonRpcMethod
     */
    public function redEnvelopesInfo() {
        global $userId;

        $config = Config::get('red_envelopes');
        $awards = isset($config['awards']) ? $config['awards'] : [];
        $key = isset($config['key']) ? $config['key'] : '';
        if(!$userId) {//未登录的时候也返回红包列表
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $awards
            ];
        }
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->first();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => json_decode($res->text)
            ];
        }
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = $key;
        $userAttr->text = json_encode($awards);
        $res = $userAttr->save();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $awards
            ];
        }
    }
    /**
     * 红包领取
     *
     * @JsonRpcMethod
     */
    public function redEnvelopesDrew($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('red_envelopes');
        $key = isset($config['key']) ? $config['key'] : '';
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $data = SendAward::ActiveSendAward($userId,$params->key);
            if(isset($data[0]['status'])){
                $redPackList = json_decode($res->text,1);
                if(isset($redPackList[$params->key]['status']) && $redPackList[$params->key]['status'] == 1){
                    DB::rollBack();
                    return [
                        'code' => -1,
                        'message' => 'fail',
                        'data' => '已领取'
                    ];
                }
                $redPackList[$params->key]['status'] = 1;
                $updatestatus = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->update(['text'=>json_encode($redPackList)]);
            }
        }
        if(isset($updatestatus)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'领取成功'
            ];
        }
        DB::rollBack();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>isset($data['msg']) ? $data['msg'] : '领取失败'
        ];
    }
    /**
     * 红包领取记录
     *
     * @JsonRpcMethod
     */
    public function redEnvelopesAwardList(){
        $res = [];
        $config = Config::get('red_envelopes');
        if(isset($config['awards']) && !empty($config['awards'])){
            $alias = [];
            foreach ($config['awards'] as $k => $v){
                $alias[] = $k;
            }
            //根据别名获取活动id
            $activityId = Activity::whereIn('alias_name',$alias)->select("id")->get()->toArray();
            //根据活动id获取发奖记录
            $data = [];
            if(!empty($activityId)){
                foreach($activityId as $acId){
                    $data[] = SendRewardLog::where("activity_id",$acId)->where("status",1)->select("id","user_id","remark")->take(30)->orderBy("id","desc")->get()->toArray();
                }
            }
            $newSort = [];
            if(!empty($data)){//格式数据
                foreach($data as $value){
                    foreach($value as $v){
                        $newSort[$v['id']] = $v;
                    }
                }
            }
            krsort($newSort);//倒序排列
            if(!empty($newSort)){
                $i = 1;
                foreach($newSort as $item){
                    if($i > 30){
                        continue;
                    }
                    if(!empty($item['remark'])){
                        $remak = json_decode($item['remark'],1);
                        $awardName = isset($remak['award_name']) ? $remak['award_name'] : "";
                        $userInfo = Func::getUserBasicInfo($item['user_id']);
                        $display_name = isset($userInfo['username']) ? substr_replace(trim($userInfo['username']), '******', 3, 6) : '';
                        $res[] = $display_name."已成功获取".$awardName;
                        $i++;
                    }
                }
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $res
        ];
    }
}
