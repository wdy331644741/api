<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\UserAttribute;
use App\Models\SendRewardLog;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use App\Service\SendMessage;
use Illuminate\Support\Facades\Redis;
use Lib\JsonRpcClient;
use Config, Request, DB, Cache;

class CatchDollJsonRpc extends JsonRpc
{
    //每天两次机会
    protected $chance_day = 2;
    /**
     *  球列表
     */
    protected $doll_list = [
        'China'       =>0,
        'Japan'       =>0,
        'England'     =>0,
        'Australia'   =>0,
        'Argentina'   =>0,
        'Netherlands' =>0,
    ];

    protected $attr_key = 'catch_doll_game';//储存在用户属性表中的key && 活动名称

    /**
     *  老用户中奖概率
     */
    protected $old_award = [
        ['alias_name' =>'catch_doll_8888_ex', 'desp' => '8888元体验金', 'size' => 8888, 'pro' => 20],
        ['alias_name' =>'catch_doll_5_ca', 'desp' => '5元现金', 'size' => 5, 'pro' => 80],
    ];
    /**
     *  新用户中奖概率
     */
    protected $new_award = [
        ['alias_name' =>'catch_doll_8888_ex', 'desp' => '8888元体验金', 'size' => 8888, 'pro' => 50],
        ['alias_name' =>'catch_doll_5_ca', 'desp' => '5元现金', 'size' => 5, 'pro' => 50],
    ];
    /**
     * 娃娃机 首页数据、状态
     *
     * @JsonRpcMethod
     */
    public function gameDollInfo() {
        global $userId;

        $res = [
            'is_login'    => false,
            'cards'       => $this->doll_list,
            'chance'      => 0,
            'is_exchange' => false,
            'list'        => [],
            ];
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($this->attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //登陆状态
        if($res['is_login'] == true){
            //获取卡片信息
            $attr = UserAttribute::where(['key'=>$this->attr_key,'user_id'=>$userId])->first();
            if(!$attr){
                //初始化用户 抓娃娃机会
                $res['chance'] = $this->initChance($userId);
            }else{
                $res['cards'] = empty($attr['string'])?$this->doll_list:json_decode($attr['string'],1);
                $res['chance'] = $this->getChanceCounts($userId);
            }
            $res['is_exchange'] = $this->isExchangehan($res['cards']);
            $res['list'] = $this->getUserAwards($userId);
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => $res,
        ];
    }


    /**
     * 抓中娃娃机 请求接口
     * 返回 国家队
     * @JsonRpcMethod
     */
    public function gameDollDraw() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        // 是否触发间隔限制 3秒
        if($this->isTooOften($userId, 3)) {
            throw new OmgException(OmgException::API_BUSY);
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($this->attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //获取的国家队球
        $_doll = null;
        //事务开始
        DB::beginTransaction();
        $attr = UserAttribute::where(['key'=>$this->attr_key,'user_id'=>$userId])->lockForUpdate()->first();

        if( (isset($attr)?$attr['number']:0 )< 1){
            DB::rollBack();//回滚  
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $cards = isset($attr['string']) ? json_decode($attr['string'],1) : [];
        /*属性 及 抽奖国家队*/
        $_string = null;
        $_doll   = null;
        if(empty($cards)){//用户没有获得任何卡片
            //从5个 国家对中 随机给一个
            $list_key_arr = array_keys($this->doll_list);
            $bin = rand(0,5);
            $this->doll_list[$list_key_arr[$bin]]++;
            //减少 抓娃娃机会
            // Attributes::increment($userId ,$this->attr_key ,-1 ,json_encode($this->doll_list));
            $_string = json_encode($this->doll_list);
            $_doll = $list_key_arr[$bin];
        }else{
            $_arrInfo = json_decode($attr['string'] ,1);

            $getCountry = call_user_func_array(function($c){
                $c_values = array_count_values($c);

                /*用户全有球，按照最少的球的给*/
                if(!isset($c_values[0]) ){
                    return array_search(min($c) ,$c);
                }
                /*判断用户是否拥有5个不同的球，如果不是，则夹中的球开出需与拥有的球不同，若已拥有5个不同的球，最后一个随机给出（每个球1/6概率）*/
                else if($c_values[0] > 1 && $c_values[0] < 6){
                    foreach ($c as $key => & $value) {
                        if($value > 0)//去掉有数的国家队
                            unset($c[$key]);
                    }
                    return array_rand($c,1);
                }else{//所有球1/6概率
                    return array_rand($this->doll_list ,1);
                }
                /***************************/
            } , [$_arrInfo]);

            $_arrInfo[$getCountry]++;
            $_string = json_encode($_arrInfo);
            $_doll = $getCountry;
            // return $_arrInfo;
        }

        $attr->string = $_string;
        $attr->timestamps = false;//更改用户属性时  不更新时间戳。
        $attr->decrement('number', 1);
        $attr->save();
        DB::commit();
        return [
            'code'    => 0,
            'message' => 'success',
            'data'    => $_doll
        ];

    }

    /**
     * 兑换
     * @JsonRpcMethod
     */
    public function exchangeDoll() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias($this->attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //事务开始
        DB::beginTransaction();
        $attr = UserAttribute::where(['key'=>$this->attr_key,'user_id'=>$userId])->lockForUpdate()->first();
        $ready = isset($attr->string)?$this->isExchangehan($attr->string) : false;
        if(!$ready ){
            DB::rollBack();//回滚 
            throw new OmgException(OmgException::INTEGRAL_REMOVE_FAIL);
        }

        // $ss = '{"China":3,"Japan":2,"England":1,"Australia":4,"Argentina":2,"Netherlands":1}';
        $_str = json_decode($attr->string,1);
        foreach ( $_str  as &$value) {
            $value--;
        }
        // foreach ( $_str = json_decode($attr->string,1) as &$value) {
        //     $value--;
        // }

        //1 新用户 2 老用户
        $data = ["user_id" => $userId];
        $result = self::jsonRpcApiCall((object)$data, 'isNewOrOldUser', env("ACCOUNT_HTTP_URL"));
        $item = $result['result']['status'] == 1?$this->new_award:$this->old_award;

        //按活动  发奖
        $award = $this->getAward($item);
        // 根据别名发活动奖品
        $aliasName = $award['alias_name'];
        $awards = SendAward::ActiveSendAward($userId, $aliasName);
        if(isset($awards[0]['award_name']) && $awards[0]['status']){
            $attr->string = json_encode($_str);
            $attr->timestamps = false;//更改用户属性时  不更新时间戳。
            $attr->save();

            DB::commit();
        }else{
            DB::rollBack();
            throw new OmgException(OmgException::API_FAILED);
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $awards[0]['award_name']
        ];

    }

    /**
     * 是否集齐
     *
     */
    private function isExchangehan($cards){
        if(!is_array($cards)){
            $cards = json_decode($cards,1);
        }
        foreach ($cards as $value) {
            if($value == 0){
                return false;
            }
        }
        return true;
    }

    /**
     * 用户剩余抽奖次数(当天)
     *
     */
    private function getChanceCounts($userId){
        $userAtt = UserAttribute::where(array('user_id' => $userId, 'key' => $this->attr_key))->first();

        if(isset($userAtt->updated_at) ){
            if($userAtt->updated_at < date('Y-m-d')){
                //继承用户之前的属性
                $this->initChance($userId ,$userAtt->string);
                return $this->chance_day;
            }else{
                return $userAtt->number;
            }
        }else{
            //初始化数据
            $this->initChance($userId);
            return $this->chance_day;
        }

    }

    /**
     * 初始化用户 (当天)
     *
     */
    private function initChance($userId ,$str = null){
        return Attributes::incrementItemByDay($userId , $this->attr_key ,$this->chance_day ,$str);
    }

    /**
     * 抽奖间隔验证
     *
     * @param $userId
     * @param $spacing
     * @return bool
     */
    private function isTooOften($userId, $spacing) {
        $key = "rich_lottery_system_{$userId}";
        $value = Cache::pull($key);
        Cache::put($key, time(), 3);
        if($value && time()-$value < $spacing) {
            return true;
        }
        return false;
    }

    /**
     * 获取奖品总数
     *
     * @param $item
     * @return int
     */
    private function getTotalNum($item) {
        $number = 0;
        foreach($item as $award) {
            $number += $award['pro'];
        }
        return $number;
    }

    /**
     * 获取奖品
     *
     * @param $item
     * @return mixed
     * @throws OmgException
     */
    private function getAward($item) {
        $number = $this->getTotalNum($item);

        $target = rand(1, $number);
        foreach($item as $award) {
            $target = $target - $award['pro'];
            if($target <= 0) {
                $globalKey = Config::get('octlottery.alias_name') . '_' . date('Ymd');
                Cache::increment($globalKey, 1);
                return $award;
            }
        }

        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }


    /**
     * 用户已获得奖品
     *
     * @param $userId
     * @return mixed
     * @throws OmgException
     */
    private function getUserAwards($userId) {
        $resArr = array();
        array_push($resArr, ActivityService::GetActivityInfoByAlias('catch_doll_8888_ex')->id);
        array_push($resArr, ActivityService::GetActivityInfoByAlias('catch_doll_5_ca')->id);
        $awardsArr = SendRewardLog::where('user_id',$userId)->where('status','>=',1)->whereIn('activity_id',$resArr)->get()->toArray();
        $newArr = array();
        foreach ($awardsArr as $key => $value) {
            $remarkTmp = json_decode($value['remark'] ,1);
            $newArr[$key]['name'] = $remarkTmp['award_name'];
            $newArr[$key]['date'] = $value['created_at'];
        }
        return $newArr;
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
        //self::debugTrace($data, $method, $result);

        if(isset($result['error'])){
            throw new OmgException(OmgException::API_FAILED);
        }

        return $result;
    }

}

