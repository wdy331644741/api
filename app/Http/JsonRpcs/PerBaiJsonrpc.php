<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use App\Service\PerBaiService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\Paginator;

use Config, Request, Cache,DB;
use Illuminate\Support\Facades\Redis;

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
        $config = Config::get('perbai');
        $result = [
            'login' => false,
            'available' => 0,
            'countdown_status' => 0,//倒计时是否开始
            'countdown' => 0,//倒计时
            'start'=>'',
//            'remain_number'=> 0,//剩余抽奖号码个数
            'alert_status'=>0,//弹框状态
            'draw_number'=> null,//我的最新的抽奖号码
            'draw_number_list'=> [],//我的的抽奖号码列表
        ];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        $activityInfo = Activity::where(['enable' => 1, 'alias_name' => $config['alias_name']])->first();
        if ($activityInfo) {
            $activityConfig = HdPerHundredConfig::where(['status' => 1])->orderBy('id','desc')->first();
            if ($activityConfig) {
                //活动开始时间
                $result['start'] = $activityConfig->start_time;
                if (time() > strtotime($activityConfig->start_time)) {
                    //活动正在进行
                    $result['available'] = 1;
                }

            }
        }
        //活动参与人数
        //活动倒计时
        $countdownInfo = Activity::where(['enable' => 1, 'alias_name' => $config['countdown']])->first();
        if ($countdownInfo) {
            //活动倒计时开始
            if(time() > strtotime($countdownInfo->start_at)){
                $result['countdown_status'] = 1;
                $result['countdown'] = strtotime($activityInfo['start_at']) - strtotime('now');
            }
        }
        //深证成指收盘   爬虫
        //我的抽奖号码  显示最新获得，  登陆显示此字段
        if ($result['login']) {
            $result['draw_number'] = self::getNewDrawNum($userId);
            //我的抽奖号码列表
            $result['draw_number_list'] = self::getDrawNumList($userId);

            //是否有中奖的号码
            $award = HdPerbai::select('draw_number','award_name')->where(['status'=>2])->orderBy('id', 'desc')->first();
            if ($award) {
                $result['alert_status'] = 1;
                $result['alert_number'] = PerBaiService::format($award['draw_number']);
                $result['alert_name'] = $award['award_name'];
            }
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 获取我的奖品列表
     *
     * @JsonRpcMethod
     */
    public function perbaiMylist($params) {
        global $userId;
        $num = isset($params->num) ? $params->num : 5;
        $page = isset($params->page) ? $params->page : 1;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $data = HdPerbai::select('draw_number', 'uuid', 'period')->where(['status'=>2, 'user_id'=>$userId])->paginate($page)->toArray();
        if ($data['data']) {
            foreach ($data['data'] as $k=>$v) {
                $data['data'][$k]['draw_number'] = PerBaiService::format($v['draw_number']);
            }
        }

//        $rData['total'] = $data['total'];
        $rData['per_page'] = $data['per_page'];
        $rData['current_page'] = $data['current_page'];
        $rData['last_page'] = $data['last_page'];
//        $rData['from'] = $data['from'];
//        $rData['to'] = $data['to'];
        $rData['list'] = $data['data'];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $rData,
        ];
    }
    /**
     * 获取奖品列表
     *
     * @JsonRpcMethod
     */
    public function perbaiList($params) {
        $config = Config::get('perbai');
//        $data = Cache::remember('perbai_list', 1, function() {
            $data = HdPerbai::select('user_id','award_name', 'updated_at')->where(['status'=>2])->orderBy('id', 'desc')->get()->toArray();
            foreach ($data as &$item){
                if(!empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                }
            }
//            return $data;
//        });
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
    public function perbaiJoinNum() {

//        $join_num = Redis::get('perbai_join_num');
//        if (!$join_num) {
            $join_num = HdPerbai::where('user_id', '>', 0)->distinct('use_id')->count('user_id');
//            Redis::incr('perbai_join_num', 1);
//        }
        $data['number'] = $join_num;
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

        $key = 'perbai_award_pic';
        $data = Cache::remember($key, 5, function() {
            $fields = ['ultimate_award','ultimate_img1','ultimate_img2','first_award','first_img1','first_img2','last_award','last_img1','last_img2','sunshine_award','sunshine_img1','sunshine_img2'];
            $list = HdPerHundredConfig::select($fields)->where('status', 1)->orderBy('id', 'desc')->first();
            return $list;
        });

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    /**
     *  活动开奖状态信息
     *
     * @JsonRpcMethod
     */
    public function perbaiDrawStatus() {

        global $userId;
//        2已中奖、1未中奖，0待开奖
        $data['status'] = 0;
        $data['remain_number'] = self::getRemainNum();
        $data['shenzheng'] = null;
        $data['create_time'] = null;
//        //抽奖号码剩余个数
        if ($data['remain_number'] == 0) {
            $attr = GlobalAttributes::getItem(PerBaiService::PERBAI_VERSION_END);
            if ($attr && $attr['number'] > 0) {
                    $data['shenzheng'] = $attr['number'] / 100;
                    $data['create_time'] = $attr['string'];
                    //开奖号码
                    $draw_number = substr(strrev($attr['number']), 0, 4);
                    $draw_info = HdPerbai::where(['draw_number'=>$draw_number, 'period'=>PerBaiService::PERBAI_VERSION])->first();
                    $data['status'] = ($userId && $userId == $draw_info->user_id) ? 2 : 1;
            }
        }

        //上期数据
        $data['before_period'] = [];
        $before_version = PerBaiService::PERBAI_VERSION - 1;
        if ($before_version > 0) {
            $before_key = str_replace(PerBaiService::PERBAI_VERSION, $before_version, PerBaiService::PERBAI_VERSION_END);
            $before_attr = GlobalAttributes::getItem($before_key);
            if ($before_attr && $before_attr['number'] > 0) {
                //上期深证成指收盘价
                $before_data['shenzheng'] = $before_attr['number'] / 100;
                $before_data['create_time'] = $before_attr['string'];
                //开奖号码
                $before_data['draw_number'] = $before_number = substr(strrev($before_attr['number']), 0, 4);
                $before_info = HdPerbai::where(['draw_number'=>$before_number, 'period'=>$before_version])->first();
                $before_data['status'] = ($userId && $userId == $draw_info->user) ? 2 : 1;
                $data['before_period'][] = $before_data;
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

    public static function getRemainNum() {

        $num = HdPerbai::where(['status'=>0, 'period'=>PerBaiService::PERBAI_VERSION])->count();
        return $num;
    }

    public static function getNewDrawNum($userId) {

        $draw_num = HdPerbai::where(['user_id'=>$userId])->orderBy('id', 'desc')->first();
        if (!$draw_num) {
            return ;
        }
        return PerBaiService::format($draw_num['draw_number']);
    }

    public static function getDrawNumList($userId) {
        $list = HdPerbai::select('draw_number', 'updated_at')->where(['user_id'=>$userId])->orderBy('id', 'asc')->get();
        if ($list) {
            foreach ($list as $k=>$v) {
                $list[$k]['draw_number'] = PerBaiService::format($v['draw_number']);
            }
        }
        return $list;
    }
}

