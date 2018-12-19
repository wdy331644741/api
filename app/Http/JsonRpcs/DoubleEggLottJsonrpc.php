<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\RichLottery;
use App\Models\UserAttribute;
use App\Models\SendRewardLog;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\ActivityService;
use App\Service\SignInSystemBasic;
use App\Service\Func;
use App\Service\SendAward;
use App\Service\SendMessage;
use App\Service\LotteryService;
use Lib\JsonRpcClient;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Config, Request, DB, Cache;

class DoubleEggLottJsonRpc extends JsonRpc
{

    protected static $attr_key = 'double_egg_lott';//储存在用户属性表中的key && 活动名称(时间控制)
    protected static $gold_egg = 'double_egg_gold';//金蛋
    protected static $color_egg = 'double_egg_color';//彩蛋
    protected $_result = [
            'awardName' => '',
            'awardType' => 0,
            'amount' => 0,
            'awardSigni' => '',
        ];

    /**
     * 拆礼物 info
     *
     * @JsonRpcMethod
     */
    public function doubleEggLottInfo() {
        global $userId;
        $res = [
            'is_login'      => false,
            'num'           => 0,
            'list'          => [],
            'all_user_list' => [],
        ];
        
        $act_object = ActivityService::GetActivityInfoByGroup('double_egg');
        // 活动是否存在
        if($act_object->isEmpty() ) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $activityIDs = array_map(function($c){
            return $c['id'];
        }, $act_object->toArray());

        // $act_gold = ActivityService::GetActivityInfoByAlias(self::$gold_egg);
        // $act_color = ActivityService::GetActivityInfoByAlias(self::$color_egg);
        // if($act_gold && $act_color){
        //     $activityIDs = array($act_gold['id'] ,$act_color['id']);
        // }else{
        //     throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        // }
        // return $activityIDs;
        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
            //查取用户数据
            $res['num'] = Attributes::getNumber($userId, self::$attr_key ,0);
            
            $_award = SendRewardLog::whereIn('activity_id',$activityIDs)->where('status','>=',1)->where('user_id',$userId)->select('remark' ,'created_at')->orderBy('id', 'desc')->get()
                ->map(function ($item, $key) {
                    $tmp = json_decode($item['remark'],1);
                    return ['name'=> $tmp['award_name'],
                            'created_at' => Carbon::parse($item['created_at'])->toDateTimeString()
                            ];
                });
            // $_ww = $_award

            $res['list'] = $_award;
        }
        $res['all_user_list'] = $this->getAllawards($activityIDs);

        return $res;
    }
    /**
     * 砸彩蛋 egg对应活动名称
     *
     * @JsonRpcMethod
     */
    public function doubleEggDraw($params) {
        if(empty($params->egg) && in_array($params->egg, ['double_egg_color','double_egg_gold']) ){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        //是否触发间隔限制
        if(LotteryService::isTooOften($userId, array('alias_name'=> $params->egg ,'interval'=> 3) )) {
            throw new OmgException(OmgException::API_BUSY);
        }

        //查询是否 剩余抽奖次数
        $beforeCounts = $this->isDraw($userId,$params->egg);
        if(!$beforeCounts){
            return [
                'code' => -1,
                'message' => 'failed',
                'data' => '次数不足',
            ];
        }
        $_userInfo = call_user_func(array("App\Service\Func","getUserBasicInfo"),$userId);
        
        //根据会员等级  （活动别名发奖）
        $actAlias = call_user_func(function($c) use($params){
            if($c < 2){
                return $params->egg."_01";
            }else if($c < 4){
                return $params->egg."_23";
            }else {
                return $params->egg."_45";
            }
        } ,max($_userInfo['level'],0));
        // return $actAlias;
        // 活动是否存在
        if(!ActivityService::isExistByAlias($actAlias)) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        //事务开始
        DB::beginTransaction();
        // //forupdate
        Attributes::getItemLock($userId ,self::$attr_key);
        // 根据别名发活动奖品
        $res = SendAward::ActiveSendAward($userId ,$actAlias);
        //消耗用户次数
        if(isset($res) && $res[0]['status']){
            $userConsume = $params->egg == 'double_egg_color'?9:1;
            Attributes::decrement($userId , self::$attr_key ,$userConsume);
        }else{
            DB::rollBack();//回滚 
            throw new OmgException(OmgException::API_FAILED);
        }

        DB::commit();
        return [
                'code' => 0,
                'message' => '抽奖成功',
                'data' => [
                    'name' => $res[0]['award_name'],
                    'type' => $res[0]['award_type'],
                ]
                
        ];
        

    }


    private function isDraw($userId ,$act) {
        $haveCounts = Attributes::getNumber($userId, self::$attr_key);
        $consume = $act == 'double_egg_color'?10:1;
        return $haveCounts >= $consume;
        
    }


    private function getAllawards($IDs) {
        $key = self::$attr_key."_allUserAwardList";
        return Cache::remember($key,5, function() use ($IDs){
            $_award = SendRewardLog::whereIn('activity_id',$IDs)->where('status','>=',1)->select('user_id','remark')->orderBy('id', 'desc')->get()
                    ->map(function ($item, $key) {
                        $tmp = json_decode($item['remark'],1);
                        $phone = protectPhone(Func::getUserPhone($item['user_id']) );
                        return ['user'=>$phone ,'name'=> $tmp['award_name'],];
                    });
            return $_award;
        });
    }
}

