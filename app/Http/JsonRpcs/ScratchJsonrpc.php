<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Jobs\ScratchBatch;
use App\Models\HdScratch;
use App\Models\UserAttribute;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\Func;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Config, Request, Cache,DB;

class ScratchJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 查询当前状态
     *
     * @JsonRpcMethod
     */
    public function scratchInfo($params) {
        global $userId;

        $result = ['login' => false, 'number' => [],'award_list' => []];

        // 用户是否登录
        if(!empty($userId)) {
            $result['login'] = true;
        }
        $config = Config::get('scratch');
        //获取抽奖次数
        $type = isset($params->type) ? $params->type : '';
        if(empty($type)){
            $result['award_list'] = $config['copper']['awards'];
        }else{
            $result['award_list'] = isset($config[$type]['awards']) ? $config[$type]['awards'] : [];
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        // 剩余抽奖次数
        if($result['login']) {
            $number = $this->getUserNum($userId,$config);
            $result['number'] = $number;
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
    public function scratchList() {
        $list = Cache::remember('scratch_list', 2, function() {
            $data = HdScratch::select('user_id', 'award_name')->where('status',1)->orderBy('id', 'desc')->take(100)->get();
            $newData = [];
            foreach ($data as &$item){
                if(!empty($item) && isset($item['user_id']) && !empty($item['user_id'])){
                    $phone = Func::getUserPhone($item['user_id']);
                    $item['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    $newData[] = "恭喜".$item['phone']."获得".$item['award_name'];
                }
            }
            return $newData;
        });

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ];
    }
    /**
     * 抽奖
     *
     * @JsonRpcMethod
     */
    public function scratchDraw($params) {
        global $userId;

        // 是否登录
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //获取抽奖次数
        $num = isset($params->num) ? $params->num : 0;
        //获取类型
        $type = isset($params->type) ? $params->type : '';
        if(($num != 1 && $num != 10) || empty($type)){
            throw new OmgException(OmgException::PARAMS_ERROR);
        }
        $config = Config::get('scratch');
        // 活动是否存在
        if(!ActivityService::isExistByAlias($config['alias_name'])) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        UserAttribute::where('user_id',$userId)->where('key',$config['drew_total_key'])->lockForUpdate()->get();

        $number = $this->getUserNum($userId,$config,$type);
        if($number <= 0) {
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        if($num > $number){
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        // 循环获取奖品
        $awardData = isset($config[$type]['awards']) ? $config[$type]['awards'] : [];
        $awardArr = [];
        for($i = 1;$i <= $num; $i++){
            if(!empty($awardData)){
                $award = $this->getAward($awardData);
                $awardArr[] = $award;
            }
        }
        //放入队列
        $this->dispatch(new ScratchBatch($userId,$awardArr));
        //格式化后返回
        foreach($awardArr  as &$item){
            unset($item['weight']);
        }
        //减少用户抽奖次数
        $this->reduceUserNum($userId,$config,count($awardArr),$type);

        //事务提交结束
        DB::commit();
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $awardArr,
        ];
    }

    //获取奖品
    private function getAward($awardList) {
        // 获取权重总值
//        $weight = 0;
//        foreach($awardList as $award) {
//            $weight += $award['weight'];
//        }
//        $target = rand(1, $weight);
//        foreach($awardList as $award) {
//            $target = $target - $award['weight'];
//            if($target <= 0) {
//                return $award;
//            }
//        }
        //三个奖品随机
        $round = mt_rand(0,2);
        return $awardList[$round];
    }

    //获取用户的剩余次数
    private function getUserNum($userId,$config,$type = ''){
        $return = [];
        $return['copperNum'] = Attributes::getNumber($userId, $config['copper']['key']);
        $return['silverNum'] = Attributes::getNumber($userId, $config['silver']['key']);
        $return['goldNum'] = Attributes::getNumber($userId, $config['gold']['key']);
        $return['diamondsNum'] = Attributes::getNumber($userId, $config['diamonds']['key']);
        if(!empty($type)){
            return isset($return[$type."Num"]) ? $return[$type."Num"] : 0;
        }
        return $return;
    }

    //减少用户次数
    private function reduceUserNum($userId,$config,$num,$type){
        if($num <= 0 && isset($config[$type]['key']) && !empty($config[$type]['key'])){
            return false;
        }
        //将总共的抽奖次数累加
        Attributes::increment($userId,$config['drew_total_key'],$num);
        //减少用户抽奖次数
        Attributes::decrement($userId,$config[$type]['key'],$num);
        return true;
    }

}

