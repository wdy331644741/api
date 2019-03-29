<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Models\SendPush;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use App\Service\SendMessage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\GlobalAttribute;

use Config, Request, Cache,DB;

class PerBaiJsonrpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function perbaiInfo() {
        global $userId;
        $result = [
            'login' => 0,
            'available' => 0,
            'countdown' => 0,//倒计时
            'alert_status'=>0,//弹框状态
            'node'=> 0,//提醒我
        ];
        if ( !empty($userId) ) {
            $result['login'] = 1;
        }
            // 活动是否存在
        $activity = PerBaiService::getActivityInfo();
        $time = time();
        if ( $activity && $time < strtotime($activity['start_time']) ) {
            $result['available'] = 1;
            $countdown = strtotime($activity['start_time']) - $time;
            $result['countdown'] = $countdown > 0 ? $countdown : 0;
        }
        if ( $result['login'] && $result['available'] ) {
            $where['status'] = 2;//中奖状态
            $where['user_id'] = $userId;
            $where['period'] = $activity['id'];//期数
            $perbai_model = HdPerbai::where($where)->orderBy('id', 'desc')->first();
            //弹框只显示一次
            if ($perbai_model) {
                $result['alert_status'] = empty($perbai_model['remark']) ? 1 : 0;//是否弹出奖品
                //奖品弹出列表
                //不用更新时间,只是记录弹框状态显示或不显示
                $perbai_model->timestamps = false;
                $perbai_model->remark = 'alert';//弹框只显示一次
                $perbai_model->save();
            }
            $type = PerBaiService::$nodeType . $activity['id'];
            $pushInfo = SendPush::where(['user_id'=>$userId, 'type'=>$type])->exists();
            if ($pushInfo) {
                $result['node'] = 1;
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 实时剩余号码量
     * 实时发放的下一个号码
     * @return array
     * @JsonRpcMethod
     */
    public function perbaiRemain()
    {
        $result['remain'] = PerBaiService::getRemainNum();
        $result['next'] = self::getNextNum();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }
    /**
     * 我的抽奖号码
     *
     * @JsonRpcMethod
     */
    public function perbaiMylist() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $activity = PerBaiService::getActivityInfo();
        $where = ['user_id'=>$userId, 'period'=>$activity->id];
        $data = HdPerbai::select('draw_number')->where($where)->get()->toArray();
        foreach ($data as $k=>$v) {
            $data[$k]['draw_number'] = PerBaiService::format($v['draw_number']);
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
    public function perbaiList() {
        $activity = PerBaiService::getActivityInfo();
        $where = ['status'=>2, 'period'=>$activity->id];
        $data = HdPerbai::select('user_id','award_name', 'updated_at')->where($where)->orderBy('updated_at', 'desc')->limit(30)->get()->toArray();
        foreach ($data as &$item){
            if(!empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 活动参与人数
     *
     * @JsonRpcMethod
     */
//    public function perbaiJoinNum() {
//        $global_key = 'perbai_pv';
//        $globalAttr = GlobalAttributes::getItem($global_key);
//        $data['number'] = isset($globalAttr->number) ? $globalAttr->number : 0;
//        return [
//            'code' => 0,
//            'message' => 'success',
//            'data' => $data,
//        ];
//    }

    /**
     * 活动 PV 记录
     *
     * @JsonRpcMethod
     */
//    public function perbaiPv() {
//        $global_key = 'perbai_pv';
//        GlobalAttributes::increment($global_key);
//        return [
//            'code' => 0,
//            'message' => 'success',
//        ];
//    }

    /**
     * 奖品图片
     *
     * @JsonRpcMethod
     */
    public function perbaiAwardInfo() {
        $activity = PerBaiService::getActivityInfo();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $activity,
        ];
    }

    /**
     *  活动开奖状态信息
     *
     * @JsonRpcMethod
     */
    public function perbaiDrawStatus() {
        global $userId;
        //明天gao
        $perbaiService = new PerBaiService();
//        2已中奖、1未中奖，0待开奖
        $data['status'] = 0;
        $data['period'] = $perbaiService::$perbai_version;
        $data['remain_number'] = PerBaiService::getRemainNum();
        $data['shenzheng'] = null;
        $data['create_time'] = null;
//        //抽奖号码剩余个数
        if ($data['remain_number'] == 0) {
            $attr = GlobalAttributes::getItem($perbaiService::$perbai_version_end);
            if ($attr && $attr['number'] > 0) {
                    $data['shenzheng'] = sprintf("%.2f",$attr['number'] / 100);
                    $data['create_time'] = $attr['string'];
                    //开奖号码
                    $draw_number = substr(strrev($attr['number']), 0, 4);
                    $draw_info = HdPerbai::where(['draw_number'=>$draw_number, 'period'=>$perbaiService::$perbai_version])->first();
                    $data['status'] = ($userId && $userId == $draw_info->user_id) ? 2 : 1;
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * curl
     *
     * @JsonRpcMethod
     */
    public function perbaiCurl() {
        $data = PerBaiService::curlSina();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }
    /**
     * curl
     *
     * @JsonRpcMethod
     */
    public function perbaiBossAward()
    {
        $perbaiService = new PerBaiService();
        $key = $perbaiService::$perbai_version_end;
        $attr = GlobalAttribute::where(array('key' => $key))->first();
        if (!$attr || $attr['number'] == 0) {
            return [
                'code' => -1,
                'message' => 'fail',
            ];
        }
        //次日开奖
        $today = date('Ymd', time());
        $oldday = date('Ymd', strtotime($attr['created_at']));
        if ($oldday >= $today) {
            return [
                'code' => -1,
                'message' => 'fail',
            ];
        }
        //开奖号码
        $draw_number = substr(strrev($attr['number']), 0, 4);
        $config = Config::get('perbai');
        $awards = $config['awards']['zhongjidajiang'];
        //
        $awardsName  = HdPerHundredConfig::where('status', 1)->value('ultimate_award');
        $awards['name'] = $awardsName;
        $update['award_name'] = $awards['name'];
        $update['alias_name'] = $awards['alias_name'];
        $update['uuid'] = 'wlb' . date('Ymd') . rand(1000, 9999);
        $update['status'] = 2;
        $where = [
            'draw_number'=>$draw_number,
            'period'=>$perbaiService::$perbai_version
        ];
        $perbai_model = HdPerbai::where($where)->first();
        $res = HdPerbai::where($where)->update($update);
        if(!$res) {
            return [
                'code' => -1,
                'message' => 'fail',
            ];
        }
        $sendData = [
            'user_id'=>$perbai_model->user_id,
            'awardname'=>$awards['name'],
            'aliasname'=>$awards['award_name'],
            'code'=>$update['uuid']
        ];
        PerBaiService::sendMessage(array($sendData));
        return [
            'code' => 0,
            'message' => 'success',
        ];
    }

    /**
     * 往期
     *
     * @JsonRpcMethod
     */
    /*
    public function perbaiAgo()
    {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $key = 'perbai_end_';
        $cache_key = $key . $userId;
        $data = Cache::remember($cache_key, 10, function() use($userId, $key) {
            $data = GlobalAttribute::select('key', 'number', 'string')->where('key','like', "{$key}%" )->get();
            $perbaiService = new PerBaiService();
            $period = $perbaiService::$perbai_version;
            $return = [];
            foreach ($data as $k=>$v){
                $old_period = intval(str_replace($key, '', $v['key']));
                if ($old_period == $period) {
                    continue;
                }
                $return[$k]['period'] = $old_period;
                $draw_number = substr(strrev($v['number']), 0, 4);
                $award = HdPerbai::where(['user_id'=>$userId, 'period'=>$old_period, 'draw_number'=>$draw_number])->first();
                $return[$k]['award'] = isset($award) ? $award['award_name'] : '未中奖';
                $return[$k]['number'] = sprintf("%.2f",$v['number'] / 100);
                $return[$k]['date'] = $v['string'];
            }
            return $return;
        });
        return $data;
    }
    */

    /**
     * 提醒我
     *
     * @JsonRpcMethod
     */
    public  function perbaiRemind()
    {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $acvitity = PerBaiService::getActivityInfo();
        if (!$acvitity) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $type = PerBaiService::$nodeType . $acvitity['id'];
        $where['user_id'] = $userId;
        $where['type'] = $type;
        $data = SendPush::where($where)->first();
        if ($data) {
            SendPush::where($where)->delete();
        } else {
            SendPush::create($where);
        }
        return [
            'code' => 0,
            'message' => 'success'
        ];
    }

    /**
     * 测试push
     *
     * @JsonRpcMethod
     */
    public function perbaiPush($params)
    {
        $type = isset($params->type) ? $params->type : true;
        try {
            $ret = $this->testPush($type);
        } catch (\Exception $e) {
            $ret = $e->getMessage();
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $ret,
        ];
    }

    //下个发放的号码
    public static function getNextNum() {

        $activity = PerBaiService::getActivityInfo();
        $perbai = HdPerbai::where(['user_id'=>0, 'status'=>0, 'period'=>$activity->id])->first();
        if (!$perbai) {
            return $activity->numbers;
        }
        return PerBaiService::format($perbai['draw_number']);
    }

    public function testPush($type=true)
    {
        //
        $activityConfig = HdPerHundredConfig::where(['status' => 1])->first();
        if ($activityConfig || $type) {
            $beforeTen = strtotime('-10 minute', strtotime($activityConfig->start_time));
            if (time() > $beforeTen || $type) {
                $node = Config::get('perbai.node');
                $where = ['status'=>0, 'type'=> $node];
                $count = \App\Models\SendPush::where($where)->count();
                $perPage = 100;
                $num = ceil($count / $perPage);
                $id = \App\Models\SendPush::where($where)->value('id');
                for ($i=0; $i<$num; $i++) {
                    $data = \App\Models\SendPush::select('id','user_id')->where('id', '>=', $id)->where($where)->limit($perPage)->get()->toArray();
                    $userIds = [];
                    foreach ($data as $v) {
                        $userIds[] = $v['user_id'];
                    }
                    if ($userIds) {
                        $last = array_pop($data);
                        $id = $last['id'];
                        \App\Models\SendPush::where($where)->where('id', '<=', $id)->where($where)->update(['status'=>1]);
                        $ret = SendMessage::sendPush($userIds, 'activity_remind');
                        return $ret;
                    }
                }
                throw new \Exception('没有push用户');
            } else{
                throw new \Exception('活动开始前十分钟推送push');
            }
        }else {
            throw new \Exception('活动不存在');
        }
    }
}

