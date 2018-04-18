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
                    $rank = $this->changeHcounts($item['vote'],$voteData);
                }else{
                    $rank = $item['rank'];
                }
                $update = ActivityVote::where(['user_id' => $userId])->update(['vote' => $voteData] , ['rank' => $rank]);
                return [
                    'code' => $update,
                    'message' => '投票成功',
                    'data' => $voteData,
                    'rank' => $rank
                ];
            }
            
        }else{//插入投票数据
            $rank = $this->addHcounts($voteData);
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
                    'rank' => $rank
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
        //活动倒计时
        $diffTime = strtotime($activityTime['end_at']) - strtotime('now');

        //获取两个平台的播放量
        //固定死格式
        $moveData = explode(',', $activityTime['des']);
        $mangguoTV = explode(':', $moveData[0]);
        $kuaileTV = explode(':', $moveData[1]);

        //今天是否 投票
        $isTodayVote = '';
        if($isLogin){
            $dayBegin = date('Y-m-d')." 00:00:00";
            // $dayEnd = date('Y-m-d')." 24:00:00";
            $isTodayVote = ActivityVote::where('updated_at', '>', $dayBegin)->where(['user_id'=> $userId])->first();
            $isTodayVote = ($isTodayVote)?true:false;
        }
        $planA = Redis::get('planA_vote_counts');
        $planB = Redis::get('planB_vote_counts');

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
                    'isLogin' => true,
                    'planA' => $planA,
                    'planB' => $planB,
                    'todayVote' => $isTodayVote,
                    'lastTiming'=> $diffTime,
                    'mangguoTV'=> $mangguoTV[1],
                    'kuaileTV'=> $kuaileTV[1],
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

}

