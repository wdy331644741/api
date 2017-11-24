<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\Activity;
use App\Models\NetworkDramaDzp;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use App\Service\GlobalAttributes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\NetworkDramaDzpBatch;
use Illuminate\Pagination\Paginator;

use Config, Request, Cache,DB;

class NetworkDramaDzpJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function networkDazhuanpanInfo() {
        global $userId;
        global $requestIP;

        //统计浏览量
        $channel = 'liechang_tv';
        @Func::statistics($channel, $requestIP);

        $config = Config::get('networkdramadzp');
        $result = ['login'=>false, 'available' => 0, 'share_flag'=>false, 'number' => 0];
//         用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }

        // 活动是否存在
        $activityInfo = Activity::where(['enable' => 1, 'alias_name' => $config['alias_name']])->first();
        if(isset($activityInfo->id) && $activityInfo->id > 0) {
            $startTime = isset($activityInfo->start_at) && !empty($activityInfo->start_at) ? strtotime($activityInfo->start_at) : 0;
            $endTime = isset($activityInfo->end_at) && !empty($activityInfo->end_at) ? strtotime($activityInfo->end_at) : 0;
            //活动正在进行
            if(empty($startTime) && empty($endTime)){
                $result['available'] = 1;
            }
            if(empty($startTime) && !empty($endTime)){
                if(time() > $endTime){
                    //活动结束
                    $result['available'] = 2;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            }
            if(!empty($startTime) && empty($endTime)){
                //活动未开始
                if(time() < $startTime){
                    $result['available'] = 0;
                }else{
                    //活动正在进行
                    $result['available'] = 1;
                }
            }
            if(!empty($startTime) && !empty($endTime)){
                if(time() > $startTime){
                    //活动正在进行
                    $result['available'] = 1;
                }
                if(time() > $endTime){
                    $result['available'] = 2;
                }
            }
        }

        if($result['available'] && $result['login']){
                $number = $this->getUserNum($userId, $config);
                $result['number'] = $number < 0 ? 0 : $number;
        }

        $totalNum = intval(Attributes::getNumber($userId, $config['drew_total_key']));
        if( ($totalNum + $result['number']) >= $config['draw_max_number']){
            $result['share_flag'] = true;
        }
        $result['drew_total'] = $totalNum;
        $result['awards_list'] = $config['awards'];
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $result,
        ];
    }

    /**
     * 发奖
     * $params array  中奖标识 award_flag
     * @JsonRpcMethod
     */
    public function networkDazhuanpanDraw($params) {
        global $userId;
        $awardFlag = isset($params->award_flag)? $params->award_flag: '';
        if(empty($awardFlag)){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('networkdramadzp');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //start 初始化次数
        $defaultNum = 1;
        //没有登陆, 可以抽奖一次，表里不保存抽奖次数，相当于假次数
            //判断是否首次进入活动页面
        $firstEntered = Attributes::getItem($userId, $config['drew_user_key']);
        if(!$firstEntered){
            $this->getUserNum($userId, $config, $defaultNum);
        }
        //end
        //奖品标识验证
        $awardList = $config['awards'];
        $awardArr = array();
        foreach($awardList as $item) {
            if($item['alias_name'] == $awardFlag){
                $awardArr = $item;
                break;
            }
        }
        if(empty($awardArr)) {
            throw new OmgException(OmgException::AWARD_NOT_EXIST);
        }

        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_total_key'])->lockForUpdate()->first();

        $number = $this->getUserNum($userId,$config);
        if($number <= 0) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        //验证用户总领奖次数是否<=2
        $totalNum = intval(Attributes::getNumber($userId, $config['drew_total_key']));
        //用户最多抽两次奖; 剩余次数 + 已领次数 < 2, 才满足加次数; =2 说明已分享过了
        if( ($number + $totalNum) > $config['draw_max_number'] ){
            throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
        }
        //奖品
        //放入队列
        $this->dispatch(new NetworkDramaDzpBatch($userId,$config,$awardArr));
        //格式化后返回
//        foreach($awardArr  as &$item){
//            unset($item['num']);
//            unset($item['weight']);
//        }
        //减少用户抽奖次数
        $this->reduceUserNum($userId,$config,1);

        //事务提交结束
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $awardArr,
        ];
    }

    /**
     * 获取我的奖品列表
     *
     * @JsonRpcMethod
     */
    public function networkDazhuanpanMyList($params) {
        global $userId;
        $num = isset($params->num) ? $params->num : 10;
        $page = isset($params->page) ? $params->page : 1;
        if($num <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        if($page <= 0){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $data = NetworkDramaDzp::select('user_id', 'type', 'award_name', 'alias_name', 'created_at')
            ->where('type', '!=', 'empty')
            ->where('user_id',$userId)
            ->orderBy('id', 'desc')->paginate($num)->toArray();
        foreach ($data as &$item){
            if(isset($item['user_id']) && !empty($item['user_id'])){
                $phone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
            }
        }
        $rData['total'] = $data['total'];
        $rData['per_page'] = $data['per_page'];
        $rData['current_page'] = $data['current_page'];
        $rData['last_page'] = $data['last_page'];
        $rData['from'] = $data['from'];
        $rData['to'] = $data['to'];
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
    public function networkDazhuanpanList() {
        $list = Cache::remember('network_dazhuanpan_list', 2, function() {
            $data = NetworkDramaDzp::select('user_id', 'award_name')->where('type', '!=', 'empty')->orderBy('id', 'desc')->take(20)->get();
            foreach ($data as &$item){
                if(isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                }
            }
            return $data;
        });

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }

    /**
     * 分享加次数
     *
     * @JsonRpcMethod
     */
    public function networkdramaShare() {
        global $userId;
        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('networkdramadzp');
        //活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $config = Config::get('networkdramadzp');
        $userNum = $this->getUserNum($userId, $config);
        $totalNum = intval(Attributes::getNumber($userId, $config['drew_total_key']));

        $data['shareflag'] = true;
        //用户最多抽两次奖; 剩余次数 + 已领次数 < 2, 才满足加次数; =2 说明已分享过了
        if( ($userNum + $totalNum) >= $config['draw_max_number'] ){
            $data['shareflag'] = false;
        }
        if($data['shareflag']){
            DB::beginTransaction();
            //加次数
            Attributes::increment($userId,$config['drew_user_key']);
            DB::commit();
        }
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ];
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config, $default=null){
        $userNum = Attributes::getNumber($userId, $config['drew_user_key'], $default);
        if($userNum > 0){
            return $userNum;
        }
        return 0;
    }

    //减少用户次数
    private function reduceUserNum($userId,$config,$num){
        if($num <= 0){
            return false;
        }
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config['drew_user_key'],$num);
        return true;
    }

}

