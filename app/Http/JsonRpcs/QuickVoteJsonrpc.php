<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Attributes;
use App\Service\ActivityService;
use Lib\JsonRpcClient;
use App\Service\Func;
use App\Models\UserAttribute;
use App\Models\ActivityVote;
use App\Jobs\VoteSendAwardIng;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Redis;
use App\Service\SendMessage;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Models\ActivityJoin;
use Validator, Config, Request, Cache, DB, Session;

class QuickVoteJsonRpc extends JsonRpc
{
    use DispatchesJobs;

    const VERSION = '8.0'; //1.0是 会出错  2.0是2.0
    const ACT_NAME = 'vote_time8.0';//vote_time
    //v2.0 分享送积分
    private $_integral = [
        "id" => 0,
        "name" => "100积分",
        "integral" => 100,
        "message" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "mail" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "limit_desc" => null,
        "created_at" => "2017-08-14 14:53:19",
        "updated_at" => "2017-08-14 14:53:19",
        "source_id" => 0,
        "source_name" => "",
        "trigger" => 4,
        "user_id" => ''
    ];

    //红包
    private $_redpack = [
        "id" => 0,
        "name" => "50元直抵红包",
        "red_type" => 1,
        "red_money" => 50,
        "percentage" => 0.0,
        "effective_time_type" => 1,
        "effective_time_day" => 10,
        "effective_time_start" => null,
        "effective_time_end" => null,
        "investment_threshold" => 10000,
        "project_duration_type" => 3,
        "project_type" => 0,
        "product_id" => "",
        "platform_type" => 0,
        "limit_desc" => "10000元起投，限6月及以上标",
        "created_at" => "",
        "updated_at" => "",
        "project_duration_time" => 6,
        "message" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "mail" => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
        "source_id" => "",
        "source_name" => "",
        "trigger" => 4,
        "user_id" => ""
    ];
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

        // 活动是否存在
        if(!ActivityService::isExistByAlias(self::ACT_NAME)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        switch ($params->data) {
            case 1:
                $voteData = 'planA'.self::VERSION;
                break;
            case 2:
                $voteData = 'planB'.self::VERSION;
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
        $item  = ActivityVote::where(['user_id' => $userId , 'status' => self::VERSION])->first();
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
                    $rank = $this->getRankRedisSorted($voteData,$userId);
                    $add_rank = $this->getPRdateTow($rank,substr($voteData,0,5));
                    $update = ActivityVote::where(['user_id' => $userId, 'vote' => $item['vote'] ] )->update(['vote' => $voteData,'rank' => $rank ,'rank_add'=>$add_rank] );//更换投票时   更新 新的排名
                }else{
                    $rank = $this->getRankRedisSorted($voteData,$userId);
                    $update = ActivityVote::where(['user_id' => $userId, 'vote' => $voteData] )->update(['vote' => $voteData,'rank' => $rank]);
                    //第二天 不更换投票时   继续返回第一次投票排名
                    $add_rank = $item['rank_add'];
                }
                
                return [
                    'code' => $update,
                    'message' => '投票成功',
                    'data' => substr($voteData,0,5),
                    'rank' => $add_rank,
                ];
            }
            
        }else{//插入投票数据
            /** 放入有序集合*/
            $this->insertRedisSorted($voteData,$userId,$this->msectime());
            // $rank = $this->addHcounts($voteData);
            $rank = $this->getRankRedisSorted($voteData,$userId);
            $add_rank = $this->getPRdateTow($rank,substr($voteData,0,5));
            /***************/
            $res = ActivityVote::create([
                'user_id' => $userId,
                'vote' => $voteData,
                'rank' => $rank,
                'rank_add' => $add_rank,
                'status' => self::VERSION
            ]);
            
            if($res){
                return [
                    'code' => 0,
                    'message' => '投票成功',
                    'data' => substr($voteData,0,5),
                    'rank' => $add_rank,
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

        $activityTime = ActivityService::GetActivityedInfoByAlias(self::ACT_NAME);
        if(empty($activityTime)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //活动倒计时
        if($activityTime['end_at'] == null || $activityTime['start_at'] == null){
            return "活动开始、结束时间不能为空";
        }
        $diffTime = strtotime($activityTime['end_at']) - strtotime('now');
        //活动距离开始时间
        $startTime =  strtotime($activityTime['start_at']) - strtotime('now');
        //获取两个平台的播放量
        //固定死格式
        $moveData = explode(',', $activityTime['des']);
        if(count($moveData ) != 2){
            return "播放量未设置";
        }
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
            $isTodayVote = ActivityVote::where(['user_id'=> $userId, 'status' => self::VERSION])->first();
            $lastVote = $isTodayVote['vote'];
            $lastRank = $isTodayVote['rank_add'];
            $isTodayVote = (substr($isTodayVote['updated_at'], 0,10) == date('Y-m-d'))?true:false;

            
        }
        $planA = Redis::zCard('planA'.self::VERSION.'_list');
        $planB = Redis::zCard('planB'.self::VERSION.'_list');

        if(!$planA){
            $planA = ActivityVote::where(['vote'=> 'planA'.self::VERSION])->count();
        }
        if(!$planB){
            $planB = ActivityVote::where(['vote'=> 'planB'.self::VERSION])->count();
        }
        return [
                'code' => 1,
                'message' => '成功',
                'data' => [
                    'isLogin' => $isLogin,
                    'planA' => $this->getPRdateTow($planA,'planA'),
                    'planB' => $this->getPRdateTow($planB,'planB'),
                    'todayVote' => $isTodayVote,
                    'lastVote' => substr($lastVote,0,5),
                    'rank' => $lastRank,
                    'lastTiming'=> $diffTime,
                    'startTiming'=> $startTime,
                    'mangguoTV'=> $mangguoTV[1],
                    'kuaileTV'=> $kuaileTV[1],
                    'victoryData' => $this->victory($diffTime,$mangguoTV[1],$kuaileTV[1])   //v1.0
                ]
            ];
    }

    /**
     * v2.0  分享送积分
     *
     * @JsonRpcMethod
     */
    public function addVoteIntegral(){
        global $userId;

        //是否登录
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $this->_integral['user_id'] = $userId;

        // 活动是否存在
        if(!ActivityService::isExistByAlias(self::ACT_NAME)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $activity = ActivityService::GetActivityedInfoByAlias(self::ACT_NAME);
        $this->_integral['source_name'] = $activity['name'];
        $this->_integral['source_id'] = $activity['id'];
        //验证频次   一天5次
        $where = array();
        $where['user_id'] = $userId;
        $where['activity_id'] = $activity['id'];
        $where['status'] = 3;
        $date = date('Y-m-d');
        $countInfo = ActivityJoin::where($where)->whereRaw("date(created_at) = '{$date}'")->get()->toArray();
        
        $dayIntegral = 0;
        $dayRedpack  = 0;
        foreach ($countInfo as $v) {
            if(empty($v['remark'])){
                $dayIntegral++;
            }else{
                $dayRedpack++;
            }
        }
        if($dayIntegral >= 4){
            // $this->dispatch(new VoteSendAwardIng($userId));
            if($dayRedpack >= 1){
                return "一天最多分享5次，一天一次红包";
            }
            //第五次时 同时发放积分和红包
            $this->sendRedpack($userId,$activity);
            $result = SendAward::integral($this->_integral ,array());
            //添加活动参与记录
            if($result['status']){
                SendAward::addJoins($userId,$activity,3);
                return 1;
            }else{
                return $result;
            }
            //return "一天最多分享5次";
        }else{
            //第三次的时候
            $result = SendAward::integral($this->_integral ,array());
            //添加活动参与记录
            if($result['status']){
                SendAward::addJoins($userId,$activity,3);
                return 1;
            }else{
                return $result;
            }
        }

        
    }

    /**
     * 获取 线上 单双数标  投资数据
     * data 1:快乐大本营 单数  0:极限挑战 双数
     * @JsonRpcMethod
     */
    public function getInvestmentMark($params){

        $voteSendMoney_bk = 'voteSendMoney'.self::VERSION.'_bk';
        $voteSendMoney    = 'voteSendMoney'.self::VERSION;

        //如果 已经过滤过一遍  redis列表 奖品已经处理
        $arr_bk = Redis::hGetAll($voteSendMoney_bk );
        if(!empty( $arr_bk) ){
            $totalCach = array_sum($arr_bk);
            return [$arr_bk , $totalCach];
        }

        $url = 'http://stat.wanglibao.com:10000/aso_user/get_transaction_list';
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        $untime = time();

        $data = [
            'code'=>hash('sha256',$untime.'Tli70uaAa4soY6d86hjv'),
            'timestamp'=>$untime,
            's_date'=> $params->s_date,
            'n_date'=> $params->n_date,
            'data_type'=>$params->data
        ];
        $route = ($params->data == 0)?0.012:0.02 ;//快乐大本营  奇数标 返现*0.02    极限挑战  偶数标  返现*0.012

        if($data != null){
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $info = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno:'.curl_getinfo($curl);//捕抓异常
            dump(curl_getinfo($curl));
        }
        $data = json_decode($info,true);
        
        if(!empty($data['data'])){
            //test 数据***************************************
            // array_push($data['data'], array(
            //     'user_id' => '5100881',
            //     'source_amount' => '130112.00',
            //     'period' => '6',
            //     ));
            // array_push($data['data'], array(
            //     'user_id' => '5100881',
            //     'source_amount' => '4894.00',
            //     'period' => '12',
            //     ));
            // array_push($data['data'], array(
            //     'user_id' => '5100881',
            //     'source_amount' => '101.00',
            //     'period' => '12',
            //     ));
            //***********************************************
            // return $data['data'];

            $newArray = [];
            foreach ($data['data'] as $key => $value) {
                if(array_key_exists($value['user_id'] ,$newArray)){
                    $hasAmount = $newArray[$value['user_id']];
                    $newArray[$value['user_id']] = $hasAmount + round($value['source_amount']/12*$value['period']*$route ,2);
                    // Redis::hSetNx('voteSendMoney',$value['user_id'],$hasAmount + $value['source_amount']/12*$value['period']*0.012);
                }else{
                    $newArray[$value['user_id']] = round($value['source_amount']/12*$value['period']*$route , 2);
                    // Redis::hSetNx('voteSendMoney',$value['user_id'],$value['source_amount']/12*$value['period']*0.012);
                }
                    
            }
            //写入哈希表
            foreach ($newArray as $key => $value) {
                Redis::hSetNx($voteSendMoney,$key,$value);
                Redis::hSetNx($voteSendMoney_bk,$key,$value);
            }
            return [$newArray , array_sum($newArray) ];
        }
        return false;
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
        return Redis::zRank($key, $userId)+1;
    }

    //活动结束  生产数据
    private function victory($time ,$planA ,$planB){
        if($time <= 0){

            if(mb_substr($planA, -1 ,1,"utf-8") == '万'){
                $planAview = floatval($planA)*10000;
            }else if (mb_substr($planA, -1 ,1,"utf-8") == '亿'){
                $planAview = floatval($planA)*100000000;
            }else{
                $planAview = (int)$planA;
            }

            if(mb_substr($planB, -1 ,1 ,"utf-8") == '万'){
                $planBview = floatval($planB)*10000;
            }else if (mb_substr($planB, -1 ,1,"utf-8") == '亿'){
                $planBview = floatval($planB)*100000000;
            }else{
                $planBview = (int)$planB;
            }

            $victoryOption = ($planAview>$planBview)?'planA':'planB';
            // $list = Redis::zRange($victoryOption."_list" , 0 ,-1); //v1.0
            // $victoryTowData = Redis::hKeys('voteSendMoney_bk' );//取所有的key //2.0
            //在活动结束后的三个小时 的空档期 展示假数据
            // if(empty($victoryTowData)){
            //     return [
            //         'victoryOption' => $victoryOption,
            //         'victoryPeople' => array("186******01","181******27","131******67","150******77","151******34","187******95","120******51","184******85","143******50","132******24")
            //     ];
            // }

            return [
                'victoryOption' => $victoryOption,
                // 'victoryPeople' =>$this->getUserName($victoryTowData),
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
        if(!is_array($rand_keys)){//测试环境  如果只有一个用户。stat
            $userInfo = Func::getUserBasicInfo($array[$rand_keys]);//获取用户基本信息
            array_push($resList, $userInfo['display_name']);
            return $resList;
        }
        foreach ($rand_keys as $value) {
            $userInfo = Func::getUserBasicInfo($array[$value]);//获取用户基本信息
            if(empty( $userInfo['display_name'] )){
                array_push($resList, '123****00');//测试环境 用户数据不完善
            }else{
                array_push($resList, $userInfo['display_name']);
            }
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
        $PRconf = config('prdate');
        //获取活动开始时间  取真实数据
        $activityTime = ActivityService::GetActivityedInfoByAlias('vote_time');
        $timeDiff = time() - strtotime($activityTime['start_at']);
        if($timeDiff <= $PRconf['afterAdd'] * 60){
            return $real;
        }

        $dateHours = date($PRconf['dateFormat']);//当前小时
        $beforeHours = date($PRconf['dateFormat'],strtotime($PRconf['split']));//上一个小时
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

        $res = $real+$add*1.3;
        //（真实数据+累计日活量）*0.3
        return round($res);

    }
    /**
     * 公关数据 v2
     *
     */
    private function getPRdateTow($real = 0,$type){
        //前一个小时  取真实数据
        $PRconf = config('prdate');
        //获取活动开始时间  取真实数据
        $activityTime = ActivityService::GetActivityedInfoByAlias(self::ACT_NAME);
        $timeDiff = time() - strtotime($activityTime['start_at']);
        if($timeDiff <= $PRconf['afterAdd'] * 60){
            return $real;
        }
        
        if($type == 'planA'){
            return $real*97;
        }else if($type == 'planB'){
            return $real*93;
        }
        return 666;
    }

    //积分满5次 发放红包-50元的直抵红包
    private function sendRedpack($userId,$activity){

        $this->_redpack['user_id'] = $userId;
        $this->_redpack['source_name'] = $activity['name'];
        $this->_redpack['source_id'] = $activity['id'];
        $result = SendAward::redMoney($this->_redpack );

        //添加活动参与记录
        if($result['status']){
            SendAward::addJoins($userId,$activity,3,50);
            return 1;
        }
        throw new OmgException(4500,'红包发送失败');

    }

}

