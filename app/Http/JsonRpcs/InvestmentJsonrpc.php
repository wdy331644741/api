<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use Lib\JsonRpcClient;
use Redis;
use Config;
class InvestmentJsonrpc extends JsonRpc {

    /**
     *  双11活动投资列表
     *
     * @JsonRpcMethod
     */
    public function doubleEleven() {
        //redis的key
        $redisKey = "double-eleven";
        //redis过期时间
        $s = 600;
        $redis = new Redis();
        $redis->connect(env("REDIS_HOST"), env("REDIS_PORT"));
        //判断redis是否存在该数据
        $exist = $redis->exists($redisKey);
        if(!$exist){
            //调用刘奇接口获取数据
            $param = array();
            $param['startTime'] = Config::get("activity.double_eleven_start_time");
            $param['endTime'] = Config::get("activity.double_eleven_end_time");
            $param['num'] = 10;
            $url = env('TRADE_HTTP_URL');
            $client = new JsonRpcClient($url);
            $data = $client->investMentTopData($param);
            if(isset($data['result']) && !empty($data['result'])){
                $returnData = array();
                $returnData['totalAmount'] = isset($data['result']['totalAmount']) ? $data['result']['totalAmount'] : 0;
                $returnData['totalUsers'] = isset($data['result']['totalUsers']) ? $data['result']['totalUsers'] : 0;
                if(isset($data['result']['list'])){
                    $i = 0;
                    foreach($data['result']['list'] as $item){
                        $returnData['list'][$i]['user_id'] = $item['user_id'];
                        $returnData['list'][$i]['phone'] = $item['display_name'];
                        $returnData['list'][$i]['sumAmount'] = $item['sumAmount'];
                        $returnData['list'][$i]['create_time'] = $item['create_time'];
                        $i++;
                    }
                }
                //插入到redis同时返回数据
                $redis->set($redisKey,json_encode($returnData),$s);
                return array(
                    'code' => 0,
                    'message' => 'success',
                    'data' => $returnData,
                );
            }
            //没有数据
            throw new OmgException(OmgException::NO_DATA);
        }
        //获取redis数据直接返回
        $data = json_decode($redis->get($redisKey),1);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }
}