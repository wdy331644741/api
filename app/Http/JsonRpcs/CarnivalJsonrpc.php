<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Service\Attributes;
use App\Service\ActivityService;
use Lib\JsonRpcClient;
use App\Service\Func;
use App\Models\UserAttribute;
use App\Jobs\CarnivalSendRedMoney;
use App\Jobs\CarnivalSendListRedMoney;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Validator, Config, Request, Cache, DB, Session;

class CarnivalJsonRpc extends JsonRpc
{
    use DispatchesJobs;
    /**
     * 加入战队
     *
     * @JsonRpcMethod
     */
    public function jionTeamCarnival($params) {
        global $userId;

        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $activityName = 'carnival';
        // 活动是否存在
        if(ActivityService::isExistByAlias($activityName)) {
            $game['available'] = 1;
        }
        //请求产品中心 用户是否可以加入战队
        $data['user_id'] = $userId;
        $data['o'] = 'CarnivalActivity';
        $result = self::jsonRpcApiCall((object)$data, 'userWhetherIsPaid', env("MARK_HTTP_URL"));
        if(!$result['result']){
            //不可加入战队
            return [
                'code' => 2,
                'message' => '加入战队失败',
                'data' =>  "回款未完成"
            ];
        }

        switch ($params->team) {
            case 1:
                $jionTeam = 'kuaile';
                break;
            case 2:
                $jionTeam = 'huanle';
                break;
            case 3:
                $jionTeam = 'xingfu';
                break;

            default:
                $jionTeam = '';
                break;
        }
        //加入战队出错 数据有误
        if(empty($jionTeam)){
            throw new OmgException(OmgException::DATA_ERROR);
        }
        //插入加入战队
        $item  = UserAttribute::where(['user_id' => $userId, 'key' => 'carnival' ])->first();
        if (!$item){
            $res = UserAttribute::create([
                'user_id' => $userId,
                'key' => 'carnival',
                'number' => 0,
                'string' => $jionTeam,
                'text' => json_encode([])
            ]);
            
        }else{
            //已经加入战队
            return [
                'code' => 1,
                'message' => '已经加入战队',
                'data' => $item['string']
            ];
        }

        if($res){
            return [
                'code' => 0,
                'message' => '成功',
                'data' => $jionTeam
            ];
        }else{
            return [
                'code' => -1,
                'message' => '响应超时，请重新加入',
            ];
        }
        
        
    }

    /**
     * 查询加入战队 By userid
     *
     */
    private function carnivalTeamByUser() {
        global $userId;

        $isLogin = ($userId)?true:false;
        $team = null;
        $jiontime = null;

        $item  = UserAttribute::where(['user_id' => $userId, 'key' => 'carnival' ])->first();
        if($item){
            $item = $item->toArray();
            $team = $item['string'];
            $jiontime = $item['created_at'];
        }
        return [
            'isLogin' => $isLogin,
            'team' => $team,
            'jiontime' => $jiontime
        ];
    }

    /**
     * 
     * 当前战队出借表现/活动倒计时/活动状态
     *
     * @JsonRpcMethod
     */
    public function carnivalingInfo(){
        //倒计时
        $activityName = "carnival";
        // 活动是否存在
        if(!ActivityService::isExistByAlias($activityName)) {
            throw new OmgException(OmgException::ACTIVITY_IS_END);
        }
        $userInfo = $this->carnivalTeamByUser();
        $activityTime = ActivityService::GetActivityInfoByAlias($activityName);
        //活动倒计时
        $diffTime = strtotime($activityTime['end_at']) - strtotime('now');
        
        //活动结束，请求产品中心接口  活动是否成功
        $requestData = [];
        $requestData['startTime'] = $activityTime['statt_at'];
        $requestData['endTime'] = $activityTime['end_at'];
        $requestData['o'] = 'CarnivalActivity';
        $res = self::jsonRpcApiCall((object)$requestData, 'isValid', env("MARK_HTTP_URL"));
        $activityStatus = $res['result'];
        //各战队表现
        $teamData = '';
        //当前全民出借总金额
        $allAmount = 0;
        //瓜分总金额
        $fragment = 0;
        //瓜分人数
        $fragmentPeople = 0;
        if($activityStatus == 2){
            $teamData = $this->processingDisplay();
            $allAmount = $this->getAllInvestment($activityTime);
        }elseif($activityStatus == 1 || $activityStatus == 3){//活动 成功 结束
            $teamData = $this->endDisplay();
            $cacheData = $this->endAllInvestment();
            $allAmount = $cacheData['termLendTotalAmount'];
            $fragment = $cacheData['allotAmount'];
            $fragmentPeople = $cacheData['allotTotalNum'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'allAmount' => $allAmount,//出借总金额
                'fragment' => $fragment,//瓜分金额
                'fragmentPeople' => $fragmentPeople,//瓜分人数
                'teamData'  => $teamData,//战队表现
                'timeing'   => $diffTime, //倒计时
                'end_at'    => $activityTime['end_at'],
                'status'    => $activityStatus, //1 活动结束成功 2 活动未结束 3 活动结束失败 4 用户未登陆
                'userInfo'  => $userInfo
            ]
        ];

    }

    private function endAllInvestment(){
        $key = "receiveActivityData";
        return Cache::get($key,'开奖统计发奖中');
    }
    //获取 当前全民出借总金额
    private function getAllInvestment($activityTime){
        $key = "carnivalAllInvestment";
        return Cache::remember($key,10, function() use($activityTime){
            $requestData = [];
            $requestData['startTime'] = $activityTime['statt_at'];
            $requestData['endTime'] = $activityTime['end_at'];
            $requestData['o'] = 'CarnivalActivity';

            $result = self::jsonRpcApiCall((object)$requestData, 'getLendTotalAmount', env("MARK_HTTP_URL"));
            return $result['result']['data'];
        });
    }

    //获取redis 中的活动结束信息
    private function endDisplay(){
        $key = "carnivalEndTeamDisplay";
        return Cache::rememberForever($key, function(){
            $item  = UserAttribute::where(['key' => 'carnival' ])->get();
            if($item){
                $item = $item->toArray();
            }
            $newArray['kuaile'] = [];
            $newArray['huanle'] = [];
            $newArray['xingfu'] = [];
            foreach ($item as $key => $value) {
                $newArray[$value['string']][] = $value['user_id'];
            }
            //每个战队出借总金额
            $data = $newArray;
            $data['o'] = 'CarnivalActivity';

            $result = self::jsonRpcApiCall((object)$data, 'getTermLendTotalAmount', env("MARK_HTTP_URL"));
            return $result['result']['data'];
        });
    }

    //活动进行中战队即时数据 数据每半小时更新一次
    private function processingDisplay(){
        $key = "carnivalTeamImmediate";
        return Cache::remember($key, 30, function(){
            $item  = UserAttribute::where(['key' => 'carnival' ])->get();
            if($item){
                $item = $item->toArray();
            }
            $newArray['kuaile'] = [];
            $newArray['huanle'] = [];
            $newArray['xingfu'] = [];
            foreach ($item as $key => $value) {
                $newArray[$value['string']][] = $value['user_id'];
            }
            //每个战队出借总金额
            $data = $newArray;
            $data['o'] = 'CarnivalActivity';
            // $result = $client->getTermLendTotalAmount($data);
            $result = self::jsonRpcApiCall((object)$data, 'getTermLendTotalAmount', env("MARK_HTTP_URL"));
            
            $sign = 0;
            // $result['result']['data'] = array("xingfu"=> 23455,"kuaile"=> 21174,"huanle"=>213332);
            foreach ($result['result']['data'] as $key => &$value) {
                if($value > 10000){
                    $value = "??".substr((string)$value, -3);
                    $sign++;
                }
            }
            if($sign<3){
                return [
                        "xingfu"=>"待揭晓",
                        "kuaile"=>"待揭晓",
                        "huanle"=>"待揭晓"
                ];
            }else{
                return $result['result']['data'];
            }
        });
    }

    /**
     * 给产品中心提供接口
     * 活动结束 给出所有的战队user
     *
     * @JsonRpcMethod
    */
    public function getAllTeamUser(){
        $item  = UserAttribute::select('user_id','string','created_at')->where(['key' => 'carnival' ])->get();
        if($item){
            $item = $item->toArray();
        }
        $ewsArray = [];
        // return $item;
        foreach ($item as $key => $value) {
            $resArray[$value['string']][] = $value['user_id'];
        }
        return $resArray;
    }

    /**
     * 给产品中心提供接口
     * 活动结束 各个战队中奖名单及时间
     *
     * @JsonRpcMethod
    */
    public function carnivalEndRandUserlist($params){
        //是否已经开过奖
        $isEnd = $this->isSetSelectUser();
        $key = "carnivalEndData";
        $endData = Cache::rememberForever($key, function() use($params,$isEnd){
            //if已经开过奖，并且cache丢了
            if($isEnd){
                $item = UserAttribute::select('user_id','string','text','created_at')->where(['key' => 'carnival' ])->get()->toArray();
                $databaseInfo = [];
                foreach ($item as $key => $value) {
                    if($value['text'] == '中奖'){
                        $databaseInfo[$value['string']][$value['user_id']] = $value['created_at'];
                    }
                }
                return $databaseInfo;
            }

            $item  = UserAttribute::select('user_id','string','created_at')->where(['key' => 'carnival' ])->get();
            if($item){
                    $item = $item->toArray();
                }
            $ewsArray = [];
            foreach ($item as $key => $value) {
                $resArray[$value['string']][$value['user_id']] = $value['created_at'];
            }

            $newArray = [];
            //随机抽出 三个战队中奖的人
            foreach ($resArray as $k => $v) {
                $random_keys=array_rand($v,$params->$k);//抽出的随机中奖名单
                foreach ($v as $key => $value) {
                    if(in_array($key, $random_keys)){
                        $newArray[$k][$key] = $value;
                        $this->setSelectUser($key);
                    }
                }
            }
            return $newArray;
        });

        return $endData;
    }

    /**
     * 设置中奖人记录
     *
    */
    private function setSelectUser($userId){
        $text = "中奖";
        $attribute = Attributes::setText($userId,'carnival',$text);
    }

    /**
     * 是否开过奖
     *
    */
    private function isSetSelectUser(){
        $item  = UserAttribute::where(['text' => '中奖', 'key' => 'carnival' ])->first();
        if($item)
            return true;
        else
            return false;
    }


    /**
     * 接收产品中心生成 活动最后的数据
     * @JsonRpcMethod
    */
    public function receiveActivityData($params){
        $temp = json_encode($params);
        $params = json_decode($temp,true);
        //保存到redis
        $key = "receiveActivityData";
        Cache::rememberForever($key, function() use($params){
            $dataForFe = [];
            $dataForFe['allotAmount'] = $params['allotAmount'];
            $dataForFe['allotTotalNum'] = $params['allotTotalNum'];
            $dataForFe['termLendTotalAmount'] = $params['termLendTotalAmount'];
            return $dataForFe;
        });
        return 1;
    }

    /**
     * 投资排行榜发奖
     * @JsonRpcMethod
    */
    public function runRandListAward($params){
        $temp = json_encode($params);
        $params = json_decode($temp,true);
        $this->dispatch(new CarnivalSendListRedMoney($params));
        // $this->dispatch((new CarnivalSendRedMoney($value['allot_amount'],$value['user_id']))->onQueue('lazy'));
        return true;
    }

    /**
     * 瓜分红包发奖
     * @JsonRpcMethod
    */
    public function runActivityAward($params){
        //循环发奖
        $temp = json_encode($params);
        $params = json_decode($temp,true);
        //保存到redis
        $key = "runActivityAwardData";
        Cache::rememberForever($key, function() use($params){
            return $params;
        });
        $this->sendDivideRedpack($params);
        return true;
    }

    //瓜分红包发奖
    private function sendDivideRedpack($data){
        foreach ($data as $value) {
            //放入队列
            // yield $value;
            if($value['allot_amount'] > 0){
                // $this->dispatch(new CarnivalSendRedMoney($value['allot_amount'],$value['user_id']));
                $this->dispatch((new CarnivalSendRedMoney($value['allot_amount'],$value['user_id']))->onQueue('lazy'));
            }
        }
    }
    //记录日志
    public static function debugTrace($data, $method, $result)
    {
        //记录日志
        $debugMsg = "接口{$method}请求结果：" . PHP_EOL;
        $debugMsg .= "请求参数：" . PHP_EOL . var_export($data, true) . PHP_EOL;
        $debugMsg .= "响应结果：" . PHP_EOL . var_export($result, true);


        self::logs($debugMsg, $method);

        return true;
    }

    public static function logs($arg, $logName = 'debug')
    {

        $logName = $logName ? $logName : 'wanglibao';//日志名称
        $fp = fopen(storage_path('logs/carnival_'.$logName.date('Y-m-d').'.log'), 'a');//缤纷嘉年华活动相关日志。活动过后日志可删除

        $traces = debug_backtrace();
        $logMsg = 'FILE:' . basename($traces[0]['file']) . PHP_EOL;
        $logMsg .= 'FUNC:' . $traces[1]['function'] . PHP_EOL;
        $logMsg .= 'LINE:' . $traces[0]['line'] . PHP_EOL;

        if (is_string($arg)) {
            $logMsg .= 'ARGS:' . $arg . PHP_EOL;
        } else {
            $logMsg .= 'ARGS:' . var_export($arg, true) . PHP_EOL;
        }
        $logMsg .= 'DATETIME:' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

        fwrite($fp, $logMsg);
        fclose($fp);
    }

    public static function jsonRpcApiCall(
        $data, $method, $url, $debug = true, $config = array('timeout' => 40)
    )
    {
        $rpcClient = new JsonRpcClient($url, $config);
        if (is_array($data)) {
            $result = call_user_func_array(array($rpcClient, $method), $data);
        } else {
            $result = call_user_func(array($rpcClient, $method), $data);
        }

        //记录日志
        self::debugTrace($data, $method, $result);

        if(isset($result['error'])){
            throw new OmgException(OmgException::API_FAILED);
        }

        return $result;
    }


}

