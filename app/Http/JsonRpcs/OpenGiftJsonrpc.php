<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\RichLottery;
use App\Models\UserAttribute;
use App\Models\SendRewardLog;
use App\Service\Attributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use App\Service\SendMessage;
use Illuminate\Support\Facades\Redis;
use Config, Request, DB, Cache;

class OpenGiftJsonRpc extends JsonRpc
{


    protected static $attr_key = 'open_gift';//储存在用户属性表中的key && 活动名称(时间控制)
    protected static $cativity_share = 'open_gift_share';//分享成功送10积分



    /**
     *  老用户中奖概率
     */
    protected $award_list = [
        ['alias_name' =>'cash',      'type' => 'cash'  ,'desp' => '688.88现金',  'size' => 688.88, 'pro' => 1],
        ['alias_name' =>'cash',      'type' => 'cash'  ,'desp' => '68.88现金',   'size' => 68.88, 'pro' => 10],
        ['alias_name' =>'cash',      'type' => 'cash'  ,'desp' => '1.88现金',    'size' => 1.88, 'pro' => 477],
        ['alias_name' =>'gift',      'type' => 'jd100' ,'desp' => '100京东卡',   'size' => 100, 'pro' => 7],
        ['alias_name' =>'gift',      'type' => 'jd50'  ,'desp' => '50京东卡',    'size' => 50, 'pro' => 15],
        ['alias_name' =>'gift',      'type' => 'iqy'   ,'desp' => '爱奇艺会员卡', 'size' => 0, 'pro' => 40],
        ['alias_name' =>'increases', 'type' => 'in'    ,'desp' => '1%加息券',    'size' => 0.01, 'pro' => 150],
        ['alias_name' =>'increases', 'type' => 'in'    ,'desp' => '2%加息券',    'size' => 0.02, 'pro' => 130],
        ['alias_name' =>'redMoney',  'type' => 'red'   ,'desp' => '108元红包',   'size' => 108, 'pro' => 170],
    ];


    /**
     * 拆礼物 首页
     *
     * @JsonRpcMethod
     */
    public function openGiftInfo() {
        global $userId;
        $res = [
            'is_login'      => false,
            'num'           => 0,
            'list'          => [],
            'all_user_list' => [],
        ];
        // 活动是否存在
        if(!ActivityService::isExistByAlias(self::$attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
            //查取用户数据
            $res['num'] = Attributes::getNumber($userId, self::$attr_key,0);
            $_act = ActivityService::GetActivityInfoByAlias(self::$attr_key);//获取活动id
            $_award = RichLottery::select('award_name','created_at')->where('user_id',$userId)->where('status','>=',1)->where('uuid',self::$attr_key)->orderBy('created_at','DESC')->get()->toArray();
            $res['list'] = $_award;
        }
        $res['all_user_list'] = $this->allUserList();
        

        return $res;
    }


    /**
     * 点击拆礼物
     *
     * @JsonRpcMethod
     */
    public function openGiftDraw() {
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        // 活动是否存在
        if(!ActivityService::isExistByAlias(self::$attr_key )) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //事务开始
        DB::beginTransaction();
        $attr = UserAttribute::where(['key'=>self::$attr_key,'user_id'=>$userId])->lockForUpdate()->first();
        if(!$attr->number){
            DB::rollBack();//回滚 
            throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
        }
        $award = $this->getAward($this->award_list);//概率 抽取奖品
        $info  = $this->buildInfo($userId,$award);

        //如果只是实物奖品。只发送站内信
        if($award['alias_name'] == 'gift'){
            $res['status'] = SendMessage::Mail($userId,$info['mail']);//站内信是否发送成功
        }else{
            $res = call_user_func_array(array("App\Service\SendAward",$award['alias_name']), [$info]);
        }
        
        if($res['status']){
            Attributes::decrement($userId ,self::$attr_key);//机会减一
            RichLottery::create([
                'user_id' => $userId,
                'amount' => $award['size'],
                'award_name' => isset($res['award_name'])?$res['award_name']:$award['desp'],
                'uuid' => self::$attr_key,//区分活动
                'ip' => Request::getClientIp(),
                'user_agent' => Request::header('User-Agent'),
                'status' => 1,
                'type' => isset($res['award_type'])?:0,//记录奖品类型
                'remark' => ''
            ]);
            DB::commit();
            return [
                'code' => 0,
                'message' => '兑换成功',
                'data' => [
                    'name' => isset($res['award_name'])?$res['award_name']:$award['desp'],
                    'size' => $award['size'],
                    'award_type' => $award['type'],
                ]
                
            ]; 
        }else{
            DB::rollBack();//回滚 
            return [
                'code' => 12345,
                'message' => '兑换失败',
            ];
        }
    }


    /**
     * 分享送积分
     *
     * @JsonRpcMethod
     */
    public function openGiftShare() {
        global $userId;
        // 根据别名发活动奖品
        if($userId > 0){
            $awards = SendAward::ActiveSendAward($userId, self::$cativity_share);
        }
        return [
            'code' => 0,
            'message' => '分享成功',
        ];
    }

    private function allUserList() {
        $key = self::$attr_key."_allUserAwardList";
        return Cache::remember($key,10, function() {
            $_act = ActivityService::GetActivityInfoByAlias(self::$attr_key);//获取活动id
            $tmp = RichLottery::select('user_id','award_name')->where('status','>=',1)->where('uuid',self::$attr_key)->orderBy('created_at','DESC')->get()->toArray();
            $newArr = [];
            if(!empty($tmp)){
                foreach ($tmp as $key => $value) {
                    $phone = protectPhone(Func::getUserPhone($value['user_id']) );
                    $newArr[$key]['user'] = $phone;
                    $newArr[$key]['award'] = $value['award_name'];
                }
                
            }
            return $newArr;
        });
    }
    /**
     * 转换json数据
     *
     * @param $award
     * @return int
     */
    private function transformJson($arr) {
        $newArr = [];
        if(!empty($arr)){
            foreach ($arr as $key => $value) {
                $tmp = json_decode($value['remark'],1);
                $newArr[$key]['name'] = $tmp['award_name'];
                if(isset($value['created_at'])){
                    $newArr[$key]['date'] = $value['created_at'];
                }
            }
        }
        return $newArr;
    }


    /**
     * 合成发奖info
     *
     * @param $award
     * @return int
     */
    private function buildInfo($userId ,$award) {
        //获取活动id和名称
        $_act = ActivityService::GetActivityInfoByAlias(self::$attr_key);
        //return $_act;
        switch ($award['alias_name']) {
            case 'gift':
                return [
                    "mail"        => "恭喜您在'{$_act->name}'活动中获得'{$award['desp']}'奖励。",
                    "message"     => "",
                ];
            case 'cash':
                return [
                    "id"          => 999,
                    "name"        => $award['desp'],
                    "money"       => $award['size'],
                    "type"        => "688.88",//用户中心定义
                    "mail"        => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
                    "message"     => "",
                    "created_at"  => "2018-11-13 19:51:34",
                    "updated_at"  => "2018-11-13 19:51:34",
                    "source_id"   => $_act->id,
                    "source_name" => $_act->name,
                    "trigger"     => 4,
                    "user_id"     => $userId,
                ];
            case 'increases':
                return [
                    "id"                    => 0,
                    "name"                  => $award['desp'],
                    "rate_increases"        => $award['size'],
                    "rate_increases_type"   => 1,
                    "rate_increases_start"  => null,
                    "rate_increases_end"    => null,
                    "effective_time_type"   => 1,
                    "effective_time_day"    => 3,
                    "effective_time_start"  => null,
                    "effective_time_end"    => null,
                    "investment_threshold"  => 10000,
                    "project_duration_type" => 3,
                    "project_type"          => 0,
                    "product_id"            => "",
                    "platform_type"         => 0,
                    "limit_desc"            => "10000元起投，限3月及以上标",
                    "created_at"            => "2018-08-03 11:32:56",
                    "updated_at"            => "2018-08-03 11:32:56",
                    "rate_increases_time"   => 0,
                    "project_duration_time" => 3,
                    "message"               => "",
                    "mail"                  => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
                    "source_id"             => $_act->id,
                    "source_name"           => $_act->name,
                    "trigger"               => 4,
                    "user_id"               => $userId,
                ];
            case 'redMoney':
                return [
                    "id"                    => 0,
                    "name"                  => $award['desp'],
                    "red_type"              => 1,
                    "red_money"             => $award['size'],
                    "percentage"            => 0.0,
                    "effective_time_type"   => 2,
                    "effective_time_day"    => 0,
                    "effective_time_start"  => "2018-10-16 00:00:00",
                    "effective_time_end"    => "2018-10-18 00:00:00",
                    "investment_threshold"  => 1000,
                    "project_duration_type" => 3,
                    "project_type"          => 0,
                    "product_id"            => "",
                    "platform_type"         => 0,
                    "limit_desc"            => "1000元起投，限3月及以上标",
                    "created_at"            => "2018-08-03 11:29:24",
                    "updated_at"            => "2018-10-16 14:46:12",
                    "project_duration_time" => 3,
                    "message"               => "",
                    "mail"                  => "恭喜您在'{{sourcename}}'活动中获得'{{awardname}}'奖励。",
                    "source_id"             => $_act->id,
                    "source_name"           => $_act->name,
                    "trigger"               => 4,
                    "user_id"               => $userId,
                ];
            default:
                # code...
                break;
        }
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
                // $globalKey = self::$attr_key . '_' . date('Ymd');
                // Cache::increment($globalKey, 1);
                return $award;
            }
        }

        throw new OmgException(OmgException::NUMBER_IS_NULL);
    }



}

