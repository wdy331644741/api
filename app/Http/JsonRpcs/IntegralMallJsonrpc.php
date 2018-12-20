<?php

namespace App\Http\JsonRpcs;

use App\Models\InPrize;
use App\Models\InExchangeLog;
use App\Models\InPrizetype;
use App\Models\IntegralMall;
use App\Models\IntegralMallExchange;
use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use App\Service\SendAward;
use Illuminate\Pagination\Paginator;
use Config;
use DB;

class IntegralMallJsonRpc extends JsonRpc {


    /**
     * 积分兑换接口
     * @JsonRpcMethod
     */
    public function prizeExchange($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mallId = intval($params->mallId);
        if(empty($mallId)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $num = isset($params->num) ? intval($params->num) : 1;
        //获取用户的积分额
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $userBase = $client->userBasicInfo(array('userId' =>$userId));
        $integralTotal = isset($userBase['result']['data']['score']) ? $userBase['result']['data']['score'] : 0;
        //判断积分值够不够买该奖品
        DB::beginTransaction();
        $where = array();
        $where['id'] = $mallId;
        $where['is_online'] = 1;
        $data = InPrize::where($where)
            ->where(function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            })
            ->where(function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            })
            ->lockForUpdate()->first()->toArray();
        $jifen = $data['kill_price'] ? $data['kill_price'] : $data['price'];

        $dataType = InPrizetype::where('id',$data['type_id'])->first();
        $nowHours = date("H");
        if(intval($dataType['start_time']) > $nowHours && $dataType['end_time'] <= $nowHours){
            throw new OmgException(OmgException::TODAY_ACTIVITY_IS_END);
        }
        //判断数据是否存在
        if(empty($data)){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }

        //判断值是否有效
        if($jifen < 1){
            throw new OmgException(OmgException::INTEGRAL_FAIL);
        }
        //判断是否兑换完
        if($data['stock'] <1){
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }

        if($num > $data['stock']){
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }

        //如果花费大于于拥有的总积分
        if(($jifen * $num) > $integralTotal) {
            throw new OmgException(OmgException::INTEGRAL_LACK_FAIL);
        }
        $isReal = 0;
        if($data['award_type'] == 5 && $data['award_id'] == 0){
            $isReal = 1;
        }
        //交易日志数据
        $insert = array();
        $insert['user_id'] = $userId;
        $insert['pid'] = $mallId;
        $insert['pname'] = $data['name'];
        $insert['number'] = $num;
        $insert['status'] = 0;
        $insert['type_id'] = $data['type_id'];
        $insert['phone'] = isset($userBase['result']['data']['phone']) ? $userBase['result']['data']['phone'] : null;
        $insert['realname'] = isset($userBase['result']['data']['realname']) ? $userBase['result']['data']['realname'] : null;

        //虚拟奖品，调用孙峰接口减去积分
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //用户积分
        $iData['user_id'] = $userId;
        $iData['uuid'] = SendAward::create_guid();
        if(!$isReal){
            //获取奖品名
            $awardInfo = SendAward::_getAwardInfo($data['award_type'],$data['award_id']);
            if(empty($awardInfo)){
                throw new OmgException(OmgException::MALL_NOT_EXIST);
            }
        }
        $iData['source_id'] = 0;
        $iData['source_name'] = $isReal ? "兑换".$data['name'] : "兑换".$awardInfo['name'];
        $iData['integral'] = intval($jifen) * $num;
        $iData['remark'] = $isReal ? $data['name']." * ".$num : $awardInfo['name']." * ".$num;

        //发送接口
        $result = $client->integralUsageRecord($iData);
        //发送消息&存储到日志
        if (isset($result['result']) && $result['result']) {//成功
            if($isReal){//实物奖品不发奖，减库存
                InPrize::where($where)->decrement('stock');
                $insert['is_real'] = 1;
                $insert['status'] = 1;
            }else{
                //发送奖品
                $return = SendAward::sendDataRole($userId,$data['award_type'],$data['award_id'],0,'积分兑换');
                if($return['status'] === true){
                    //修改发送成功人数+1
                    InPrize::where($where)->decrement('stock');
                    $insert['status'] = 1;
                }
            }
        }else{
            //积分扣除失败
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }

        //判断是否成功
        $insert['created_at'] = date('Y-m-d H:i:s');
        $id = InExchangeLog::insertGetId($insert);
        if($id && $insert['status'] == 1){
            DB::commit();
            return array(
                'code' => 0,
                'message' => 'success'
            );
        }
        DB::rollback();
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }


    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function mallList($params) {
        $alias_name = isset($params->alias_name) ? $params->alias_name : "all";
        $num = isset($params->num) ? intval($params->num) : 6;
        $where = ['is_online'=>1];
        if($alias_name != "all"){
            $where['alias_name'] =$alias_name;
        }
        $data = InPrizetype::where($where)
            ->with(['prizes'=>function ($query)use($num) {
                $query->where('is_online',1)->where('stock','>',0)
                    ->where(function($query) {
                        $query->whereNull('start_at')->orWhereRaw('start_at < now()');
                    })
                    ->where(function($query) {
                        $query->whereNull('end_at')->orWhereRaw('end_at > now()');
                    })
                    ->orderByRaw('id + sort desc')->paginate($num);
            }])->orderByRaw('id + sort desc')->get()->toArray();
        $nowHours = date("H");
        $newData = [];
        foreach ($data as $value){
            if(intval($value['start_time']) <= $nowHours && $value['end_time'] > $nowHours ){
                $value['is_rob'] =1;
            }else{
                $value['is_rob'] = 0;
            }
            if(!empty($value['prizes'])){
                $newData[] = $value;
            }
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $newData,
        );
    }

    /**
     *  商品详情
     *
     * @JsonRpcMethod
     */
    public function prizeDeatil($params){
        $mallId = intval($params->mallId);
        if(empty($mallId)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $data = InPrize::where(['id'=>$mallId,'is_online'=>1])
            ->where(function($query) {
                $query->whereNull('start_at')->orWhereRaw('start_at < now()');
            })
            ->where(function($query) {
                $query->whereNull('end_at')->orWhereRaw('end_at > now()');
            })->first()->toArray();
        $data['is_rob'] = 0;
        $dataType = InPrizetype::where('id',$data['type_id'])->first();
        $nowHours = date("H");
        if(intval($dataType['start_time']) <= $nowHours && $dataType['end_time'] > $nowHours){
            $data['is_rob'] = 1;
        }
        $data['alias_name'] = $dataType['alias_name'];
        $data['rob_time'] = date('Y-m-d')." ".$dataType['start_time'].":00:00";
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }

    /**
     *  限时秒杀商品列表
     *
     * @JsonRpcMethod
     */
    public function secondKillList($params){
        $num = isset($params->num) ? intval($params->num) : 6;
        $where = ['is_online'=>1,'alias_name'=>'second_kill'];
        $data = InPrizetype::where($where)
            ->with(['prizes'=>function ($query)use($num) {
                $query->where('is_online',1)->where('stock','>',0)
                    ->where(function($query) {
                        $query->whereNull('start_at')->orWhereRaw('start_at < now()');
                    })
                    ->where(function($query) {
                        $query->whereNull('end_at')->orWhereRaw('end_at > now()');
                    })
                    ->orderByRaw('id + sort desc')->paginate($num);
            }])->orderByRaw('id + sort desc')->get()->toArray();

        $prizeNum = count($data[0]['prizes']);
        if($prizeNum < 6){
            $pageNum = 6-$prizeNum;
            $prizeData = InPrize::where(['is_online'=>1,'stock'=>0])
                ->where(function($query) {
                    $query->whereNull('start_at')->orWhereRaw('start_at < now()');
                })
                ->where(function($query) {
                    $query->whereNull('end_at')->orWhereRaw('end_at > now()');
                })
                ->orderByRaw('id + sort desc')->paginate($pageNum)->toArray();
            $mergeArr = array_merge($data[0]['prizes'],$prizeData['data']);
            $data[0]['prizes'] = $mergeArr;
        }
        
        $nowHours = date("H");
        if(intval($data[0]['start_time']) <= $nowHours && $data[0]['end_time'] > $nowHours ){
            $data[0]['is_rob'] =1;
        }else{
            $data[0]['is_rob'] = 0;
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );

    }

    /**
     *  兑换记录
     *
     * @JsonRpcMethod
     */
    public function exChangeLogList($params){
        global $userId;
        $num = isset($params->num) ? intval($params->num) : 10;
        $isReal = isset($params->isreal) ? intval($params->isreal) : 0;
        $page = isset($params->page) ? $params->page : 1;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $where = ['user_id'=>$userId,'status'=>1];
        if($isReal){
            $where['is_real'] = $isReal;
            $data = InExchangeLog::where($where)->with('prizes')->orderBy('id','desc')->paginate($num)->toArray();
            $newData = [];
            foreach($data['data'] as $value){
                $date = date('Y-m-d',strtotime($value['created_at']));
                $value['created_at'] = $date;
                $newData[] = $value;
            }
            $data['data'] = $newData;
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }

        $data = InExchangeLog::where($where)->with('prizes')->orderBy('id','desc')->paginate($num)->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );

    }


    /**
     *  最新兑换记录
     *
     * @JsonRpcMethod
     */
    public function newExChangeLogList($params){
        $num = isset($params->num) ? intval($params->num) : 10;

        $data = InExchangeLog::select('id','realname','pname')->where(['status'=>1])->orderBy('id','desc')->paginate($num)->toArray();
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );

    }


    /**
     * 原积分兑换接口
     * @JsonRpcMethod
     */
    public function integralExchange($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mallId = intval($params->mallId);
        if(empty($mallId)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //获取用户的积分额
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $userBase = $client->userBasicInfo(array('userId' =>$userId));
        $integralTotal = isset($userBase['result']['data']['score']) ? $userBase['result']['data']['score'] : 0;
        //判断积分值够不够买该奖品
        DB::beginTransaction();
        $where = array();
        $where['id'] = $mallId;
        $where['status'] = 1;
        $data = IntegralMall::where($where)
            ->where(function($query) {
                $query->whereNull('start_time')->orWhereRaw('start_time < now()');
            })
            ->where(function($query) {
                $query->whereNull('end_time')->orWhereRaw('end_time > now()');
            })
            ->lockForUpdate()->first();
        //判断数据是否存在
        if(empty($data)){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        //判断值是否有效
        if($data['integral'] < 1){
            throw new OmgException(OmgException::INTEGRAL_FAIL);
        }
        //判断是否兑换完
        if($data['total_quantity'] > 0 && $data['send_quantity'] >= $data['total_quantity']){
            throw new OmgException(OmgException::EXCEED_NUM_FAIL);
        }
        //判断该用户是否超过了购买
        $whereEX = array();
        $whereEX['user_id'] = $userId;
        $whereEX['mall_id'] = $mallId;
        $whereEX['send_status'] = 1;
        $count = IntegralMallExchange::where($whereEX)->count();
        if($data['user_quantity'] > 0 && $count >= $data['user_quantity']){
            throw new OmgException(OmgException::EXCEED_FAIL);
        }
        //如果花费大于于拥有的总积分
        if($data['integral'] > $integralTotal) {
            throw new OmgException(OmgException::INTEGRAL_LACK_FAIL);
        }
        //交易日志数据
        $insert = array();
        $insert['user_id'] = $userId;
        $insert['mall_id'] = $mallId;
        $insert['snapshot'] = json_encode($data);
        $insert['send_status'] = 0;
        //调用孙峰接口减去积分
        $url = Config::get("award.reward_http_url");
        $client = new JsonRpcClient($url);
        //用户积分
        $iData['user_id'] = $userId;
        $iData['uuid'] = SendAward::create_guid();
        //获取奖品名
        $awardInfo = SendAward::_getAwardInfo($data['award_type'],$data['award_id']);
        if(empty($awardInfo)){
            throw new OmgException(OmgException::MALL_NOT_EXIST);
        }
        $iData['source_id'] = 0;
        $iData['source_name'] = "兑换".$awardInfo['name'];
        $iData['integral'] = $data['integral'];
        $iData['remark'] = $awardInfo['name']." * 1";
        //发送接口
        $result = $client->integralUsageRecord($iData);
        //发送消息&存储到日志
        if (isset($result['result']) && $result['result']) {//成功
            //发送奖品
            $return = SendAward::sendDataRole($userId,$data['award_type'],$data['award_id'],0,'积分兑换');
            if($return['status'] === true){
                //修改发送成功人数+1
                IntegralMall::where($where)->increment('send_quantity');
                $insert['send_status'] = 1;
            }
        }else{
            //积分扣除失败
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }
        //判断是否成功
        $id = IntegralMallExchange::insertGetId($insert);
        DB::commit();
        if($id && $insert['send_status'] == 1){
            return array(
                'code' => 0,
                'message' => 'success'
            );
        }
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }
}
