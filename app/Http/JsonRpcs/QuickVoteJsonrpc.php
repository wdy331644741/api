<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Attributes;
use App\Service\ActivityService;
use Lib\JsonRpcClient;
use App\Service\Func;
use App\Models\UserAttribute;
use App\Models\ActivityVote;
use App\Jobs\CarnivalSendRedMoney;
use App\Jobs\CarnivalSendListRedMoney;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Redis;
use App\Service\SendMessage;
use App\Service\GlobalAttributes;
use Validator, Config, Request, Cache, DB, Session;

class QuickVoteJsonRpc extends JsonRpc
{

    //use DispatchesJobs;
    /**
     * 参加投票
     *
     * @JsonRpcMethod
     */
    public function takeVote($params) {
        global $userId;

        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $activityName = 'vote_time';
        // 活动是否存在
        if(!ActivityService::isExistByAlias($activityName)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        switch ($params->data) {
            case 1:
                $voteData = 'planA';
                break;
            case 2:
                $voteData = 'planB';
                break;

            default:
                $voteData = '';
                break;
        }
        //投票 数据有误
        if(empty($voteData)){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        //用户是否已经参加过投票
        $item  = ActivityVote::where(['user_id' => $userId])->first();
        if($item){
            //已经参与过投票，判断当天是否投过票
            if(date('Y-m-d') == substr($item['updated_at'], 0,10)){
                return [
                    'code' => 4,
                    'message' => '今天已经投过票'
                ];
            }else{
                if($item['vote'] != $voteData){//变换投票
                    // $rank = $this->changeHcounts($item['vote'],$voteData);
                    $this->insertRedisSorted($voteData,$userId,$this->msectime());
                    $this->removeRedisSorted($item['vote'],$userId);
                }
                $rank = $this->getRankRedisSorted($voteData,$userId);
                $update = ActivityVote::where(['user_id' => $userId])->update(['vote' => $voteData,'rank' => $rank]);
                return [
                    'code' => $update,
                    'message' => '投票成功',
                    'data' => $voteData,
                    'rank' => $this->getPRdate($rank)
                ];
            }
            
        }else{//插入投票数据
            /** 放入有序集合*/
            $this->insertRedisSorted($voteData,$userId,$this->msectime());
            // $rank = $this->addHcounts($voteData);
            $rank = $this->getRankRedisSorted($voteData,$userId);
            /***************/
            $res = ActivityVote::create([
                'user_id' => $userId,
                'vote' => $voteData,
                'rank' => $rank,
            ]);
            
            if($res){
                return [
                    'code' => 0,
                    'message' => '投票成功',
                    'data' => $voteData,
                    'rank' => $this->getPRdate($rank)
                ];
            }else{
                return [
                    'code' => -1,
                    'message' => '响应超时，请重新投票',
                ];
            }

        }

    }

    /**
     * 用户是否参与过投票、数量等信息
     *
     * @JsonRpcMethod
     */
    public function voteInfo() {
        global $userId;
        //是否登录
        $isLogin = ($userId)?true:false;

        $activityName = "vote_time";
        $activityTime = ActivityService::GetActivityedInfoByAlias($activityName);
        if(empty($activityTime)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //活动倒计时
        $diffTime = strtotime($activityTime['end_at']) - strtotime('now');

        //获取两个平台的播放量
        //固定死格式
        $moveData = explode(',', $activityTime['des']);
        $mangguoTV = explode(':', $moveData[0]);
        $kuaileTV = explode(':', $moveData[1]);

        //今天是否 投票
        $isTodayVote = '';
        $lastVote = '';
        //最后一次投票排名
        $lastRank = '';
        if($isLogin){
            $dayBegin = date('Y-m-d')." 00:00:00";
            // $dayEnd = date('Y-m-d')." 24:00:00";
            $isTodayVote = ActivityVote::where('updated_at', '>', $dayBegin)->where(['user_id'=> $userId])->first();
            $lastVote = $isTodayVote['vote'];
            $lastRank = $isTodayVote['rank'];
            $isTodayVote = ($isTodayVote)?true:false;

            
        }
        $planA = Redis::zCard('planA_list');
        $planB = Redis::zCard('planB_list');

        if(!$planA){
            $planA = ActivityVote::where(['vote'=> 'planA'])->count();
        }
        if(!$planB){
            $planB = ActivityVote::where(['vote'=> 'planB'])->count();
        }
        return [
                'code' => 1,
                'message' => '成功',
                'data' => [
                    'isLogin' => $isLogin,
                    'planA' => $planA,
                    'planB' => $planB,
                    'todayVote' => $isTodayVote,
                    'lastVote' => $lastVote,
                    'rank' => $this->getPRdate($lastRank),
                    'lastTiming'=> $diffTime,
                    'mangguoTV'=> $mangguoTV[1],
                    'kuaileTV'=> $kuaileTV[1],
                    'victoryData' => $this->victory($diffTime)
                ]
            ];
    }

    //增加人头并返回 数目。
    private function addHcounts($type){
        $key = $type.'_vote_counts';
        if(Redis::exists($key)){
            $countNow = Redis::incr($key);
        }else{
            //第一个人
            // Redis::set($key,1);
            // $countNow = 1;
            $item = ActivityVote::where(['vote'=> $type])->count();
            Redis::set($key,$item+1);
            $countNow = $item+1;
        }
        return $countNow;
    }

    //从vote1   变换到 vote2
    private function changeHcounts($vote1,$vote2){
        $key1 = $vote1.'_vote_counts';
        $key2 = $vote2.'_vote_counts';
        if(Redis::exists($key1)  && Redis::exists($key2)){
            $countNow = Redis::incr($key2);
            Redis::decr($key1);
        }else{
            // $item1 = ActivityVote::where(['vote'=> $vote1])->orderBy('id', 'desc')->groupBy('vote')->get();
            $item1 = ActivityVote::where(['vote'=> $vote1])->count();
            Redis::set($key1,$item1-1);//<0 ?
            $item2 = ActivityVote::where(['vote'=> $vote2])->count();
            Redis::set($key2,$item2+1);
            $countNow = $item2+1;
        }
        return $countNow;
    }

    //方便排名  增加到redis有序集合
    private function insertRedisSorted($vote,$userId,$score){
        $key = $vote."_list";
        return Redis::zAdd($key, $score, $userId);
    }
    private function removeRedisSorted($vote,$userId){
        $key = $vote."_list";
        return Redis::zRem($key, $userId);
    }
    //获取redis有序集合中的排名
    private function getRankRedisSorted($vote ,$userId){
        $key = $vote."_list";
        return Redis::zRank($key, $userId);
    }

    //活动结束  生产数据
    private function victory($time){
        if($time <= 0){
            $planA = Redis::zCard('planA_list');
            $planB = Redis::zCard('planB_list');

            if(!$planA){
                $planA = ActivityVote::where(['vote'=> 'planA'])->count();
            }
            if(!$planB){
                $planB = ActivityVote::where(['vote'=> 'planB'])->count();
            }
            $victoryOption = ($planA>$planB)?'planA':'planB';
            $list = Redis::zRange($victoryOption."_list" , 0 ,-1);
            return [
                'victoryOption' => $victoryOption,
                'victoryPeople' =>$this->getUserName($list),
            ];
        }
        return null;
    }

    //获取用户信息
    private function getUserName($array,$num = 10){
        if(empty($array))
            return false;
        if(count($array) < $num){
            $num = count($array);
        }
        $resList = [];
        $rand_keys = array_rand($array, $num);
        if(!is_array($rand_keys)){
            $userInfo = Func::getUserBasicInfo($array[$rand_keys]);//获取用户基本信息
            array_push($resList, $userInfo);
            return $array[$rand_keys];
        }
        foreach ($rand_keys as $value) {
            $userInfo = Func::getUserBasicInfo($array[$value]);//获取用户基本信息
            array_push($resList, $userInfo['display_name']);
        }
        return $resList;
    }

    private function msectime() {
       list($msec, $sec) = explode(' ', microtime());
       $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
       return $msectime;
    }

    /**
     * 公关数据
     *
     * @JsonRpcMethod
     */
    private function getPRdate($real = 0){
        $key = 'LeiJiHuoYue';
        $dateHours = date('Y-m-d H:00:00');//当前小时
        $beforeHours = date('Y-m-d H:00:00',strtotime("-1 hours"));//上一个小时
        // $dateHours = '2018-04-19 18:00:00';
        // $beforeHours = '2018-04-19 17:00:00';
        $item = GlobalAttributes::getItem($dateHours);

        if(!$item['string']){
            $stat = Func::getStatSport();
            //上一个小时的活跃量
            $beforeItem = GlobalAttributes::getItem($beforeHours);
            $beforeStat = !empty($beforeItem['number'])?$beforeItem['number']:0;
            GlobalAttributes::setItem($dateHours,$stat+$beforeStat,$key,$dateHours."活跃量：".$stat);
            $add =  $stat+$beforeStat;
        }else{
            $add = $item['number'];
        }

        // $res = ($real+$add)*0.3;
        $res = $real+$add*0.3;
        //（真实数据+累计日活量）*0.3
        return round($res);

    }

}

