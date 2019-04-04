<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Models\HdPertenStock;
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
            'alert_list'=>[],//弹框列表
            'node'=> 0,//提醒我
        ];
        if ( !empty($userId) ) {
            $result['login'] = 1;
        }
        $time = time();
        // 活动配置信息
        $activity = PerBaiService::getActivityInfo();
        $period = isset($activity['id']) ? $activity['id'] : 0;
        if ( $activity && $time > strtotime($activity['start_time']) ) {
            $result['available'] = 1;
            //倒计时 秒数
            $countdown = strtotime($activity['start_time']) - $time;
            $result['countdown'] = $countdown > 0 ? $countdown : 0;
        }
        if ( $result['login'] && $result['available'] ) {
            $where['status'] = 2;//中奖状态
            $where['user_id'] = $userId;
            $where['period'] = $period;//期数
            $perbai_model = HdPerbai::where($where)->orderBy('id', 'desc')->first();
            //弹框只显示一次
            if ($perbai_model) {
                if ( empty($perbai_model['remark']) ) {
                    $result['alert_status'] = 1;//弹框
                    //不用更新时间,只是记录弹框状态显示或不显示
                    $perbai_model->timestamps = false;
                    $perbai_model->remark = 'alert';//弹框只显示一次
                    $perbai_model->save();
                    //奖品弹出列表
                    $mylist = HdPerbai::select(['award_name', 'draw_number', 'created_at'])->where(['user_id'=>$userId, 'period'=>$period, 'status'=>2])->get()->toArray();
                    foreach ($mylist as $k=>$v) {
                        $mylist[$k]['draw_number'] = PerBaiService::format($v['draw_number']);
                        $mylist[$k]['created_at'] = date('m-d', strtotime($v['created_at']));
                    }
                    $result['alert_list'] = $mylist;
                }
            }
            //是否提醒
            $type = PerBaiService::$nodeType . $period;
            $pushInfo = SendPush::where(['user_id'=>$userId, 'type'=>$type])->exists();
            if ($pushInfo) {
                $result['node'] = 1;
            }
        }
        //首投是否已发放
        $first_user = Cache::remember('fristAward', 30, function() use ($period){
            $first = HdPerbai::where(['draw_number'=> 0, 'period'=>$period])->value('user_id');
            return $first;
        });
        //首投文案显示
        if ($first_user) {
            $result['first_award'] = 1;//首投奖品  0未发，1已发
            $phone = Func::getUserPhone($first_user);
            $result['first_text'] = substr_replace($phone, '******', 3, 6);
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
     * 奖品图片
     *
     * @JsonRpcMethod
     */
    public function perbaiAwardInfo() {
        $activity = PerBaiService::getActivityInfo();
        $data = [
            "ultimate_award"=>$activity['ultimate_award'],
            //"ultimate_img1"=>$activity['ultimate_img1'],
            //"ultimate_img2"=>$activity['ultimate_img2'],
            "first_award"=>$activity['first_award'],
            "first_img1"=>$activity['first_img1'],
            "first_img2"=>$activity['first_img2'],
            "sunshine_award"=>$activity['sunshine_award'],
            "sunshine_img1"=>$activity['sunshine_img1'],
            "sunshine_img2"=>$activity['sunshine_img2'],
            "start_time"=>$activity['start_time'],
           // "ultimate_pc1"=>$activity['ultimate_pc1'],
           // "ultimate_pc2"=>$activity['ultimate_pc2'],
            "first_pc1"=>$activity['first_pc1'],
            "first_pc2"=>$activity['first_pc2'],
            "sunshine_pc1"=>$activity['sunshine_pc1'],
            "sunshine_pc2"=>$activity['sunshine_pc2'],
           // "award_text"=>$activity['award_text'],
           // "ultimate_rule"=>$activity['ultimate_rule'],
           // "first_rule"=>$activity['first_rule'],
          //  "sunshine_rule"=>$activity['sunshine_rule'],
            "activity_rule"=>$activity['activity_rule'],
            "first_price"=>$activity['first_price'],
            "sunshine_price"=>$activity['sunshine_price'],
            "guess_award"=>$activity['guess_award'],
        ];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     * 股指信息
     *
     * @JsonRpcMethod
     */
    public function perbaiStock() {
        $acvitity = PerBaiService::getActivityInfo();
        $date = date('Y-m-d');
        $return = [
            'time' => $date,
            'stock' => '',//未开盘为空
            'draw_number' => '',//未开盘为空
            'status' => 1,// 1未开盘
        ];
        $w = date('w');
        $h = date('Hi');
        if ( in_array($date, PerBaiService::getStockClose()) || $w == 6 || $w == 0 ) {
            $stock = HdPertenStock::where(['period'=>$acvitity['id']])->orderBy('id', 'desc')->first();
            $return['time'] = $stock['curr_time'];
            $return['stock'] = $stock['stock'];
            $return['draw_number'] = PerBaiService::format($stock['draw_number']);
            $return['status'] = 3;//3收盘后：显示”已开奖
        } else if ( $h < 930) { //小于九点半 未开盘
        } else {
            $flag = true;
            if ($h > 1530) {
                $stock = HdPertenStock::where(['curr_time'=>$date, 'period'=>$acvitity['id']])->first();
                if ($stock) {
                    $return['time'] = $stock['curr_time'];
                    $return['stock'] = $stock['stock'];
                    $return['draw_number'] = PerBaiService::format($stock['draw_number']);
                    $return['status'] = 3;//3收盘后：显示”已开奖
                    $flag = false;
                }
            }
            if ($flag) {
                $stock = PerBaiService::getStockPrice();
                $return['stock'] = round($stock[0], 2);
                $draw_number = intval(substr(strrev($return['stock'] * 100), 0, 4));
                $return['draw_number'] = PerBaiService::format($draw_number);
                $return['status'] = 2;//2开盘 显示”待开奖
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $return,
        ];
    }
    /**
     * 股指开奖记录
     *
     * @JsonRpcMethod
     */
    public function perbaiStockLog() {
        $activity = PerBaiService::getActivityInfo();
        $data = HdPertenStock::select(['curr_time', 'stock', 'draw_number', 'open_status'])->where(['period'=>$activity['id']])->where('open_status','<>', 2)->get()->toArray();
        foreach ($data as $k=>$v) {
            $data[$k]['phone'] = '';
            $data[$k]['curr_time'] = date('Y-m-d', strtotime($v['curr_time']));
            if ($v['open_status'] == 1) {
                $userId = HdPerbai::where(['period'=>$activity['id'], 'draw_number'=>$v['draw_number']])->value('user_id');
                $phone = Func::getUserPhone($userId);
                $data[$k]['phone'] = substr_replace($phone, '******', 3, 6);
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

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
        $activityConfig = PerBaiService::getActivityInfo();
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
}

