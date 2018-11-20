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
use App\Http\Controllers\AwardCommonController;
use Config;
use DB;

class IntegralMallJsonRpc extends JsonRpc {


    /**
     * 积分兑换接口
     * @JsonRpcMethod
     */
    public function prizeExchange($params) {
        global $userId;
        $userId = 70999;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mallId = intval($params->mallId);
        if(empty($mallId)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $num = isset($params->num) ? intval($params->num) : 1;
        $isReal = isset($params->isReal) ? intval($params->isReal) : 0;

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
        $data = InPrize::where($where)->lockForUpdate()->first()->toArray();
        $jifen = $data['kill_price'] ? $data['kill_price'] : $data['price'];
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
     *  商品列表(不取秒杀商品)
     *
     * @JsonRpcMethod
     */
    public function mallList($params) {
        $alias_name = isset($params->alias_name) ? $params->alias_name : "all";
        $num = isset($params->num) ? intval($params->num) : 6;
        if($alias_name == "all"){
            $data = InPrizetype::where('alias_name','<>','second_kill')->where('is_online',1)
                ->with(['prizes'=>function ($query)use($num) {
                    $query->where('is_online',1)->orderByRaw('id + sort desc')->paginate($num);
                }])->get();
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }
        $prizeId = InPrizetype::where('alias_name',$alias_name)->where('is_online',1)->value('id');
        $where = ['type_id'=>$prizeId,'is_online'=>1];
        $data = InPrize::where($where)->orderByRaw('id + sort desc')->get()->toArray();
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
        $num = isset($params->num) ? intval($params->num) : 3;
        $isTop = isset($params->istop) ? intval($params->istop) : 0;
        $prizeId = InPrizetype::where('alias_name','second_kill')->value('id');
        $where = ['type_id'=>$prizeId,'is_online'=>1];
        if($isTop){
            $where['istop'] = 1;
        }
        if($prizeId){
            $data = InPrize::where($where)->orderByRaw('id + sort desc')->paginate($num);
            $data['now_time'] = date('Y-m-d H:i:s');
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }
        return array(
            'code' => -1,
            'message' => 'fail'
        );
    }

    /**
     *  兑换记录
     *
     * @JsonRpcMethod
     */
    public function exChangeLogList($params){
        global $userId;
        $userId = 70999;
        $num = isset($params->num) ? intval($params->num) : 10;
        $isReal = isset($params->isreal) ? intval($params->isreal) : 0;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $where = ['user_id'=>$userId];
        if($isReal){
            $where['is_real'] = $isReal;
            $data = InExchangeLog::where($where)->with('prizes')->get()->toArray();
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            );
        }

        $data = InExchangeLog::where($where)
            ->Where(function($query){
            $query->where('is_real',1)
                ->orWhere(['is_real'=>0,'status'=>1]);
        })->orderBy('id','desc')->get()->toArray();
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
