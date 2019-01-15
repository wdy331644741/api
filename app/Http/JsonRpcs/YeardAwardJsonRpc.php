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

class YeardAwardJsonRpc extends JsonRpc
{

    protected static $attr_key = 'yeard_award';//储存在用户属性表中的key && 活动名称(时间控制)

    protected $_result = [
            'awardName'  => '',
            'awardType'  => 0,
            'amount'     => 0,
            'awardSigni' => '',
        ];

    /**
     *  info
     *
     * @JsonRpcMethod
     */
    public function yeardAwardInfo() {
        global $userId;
        $res = [
            'is_login'      => false,
            'score'       => 0,
            'is_play'       => 0,
            'list'          => [],
            'all_user_list' => [],
        ];
        
        $act_object = ActivityService::GetActivityInfoByGroup(self::$attr_key);
        // 活动是否存在
        if($act_object->isEmpty() ) {
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        $activityIDs = array_map(function($c){
            return $c['id'];
        }, $act_object->toArray());

        //登陆状态
        if($userId > 0){
            $res['is_login'] = true;
            //查取用户数据 不取缓存里面的数据
            $_userInfo = call_user_func_array(array("App\Service\Func","getUserBasicInfo"),[$userId,true]);
            $res['score'] = $_userInfo['score'];
            $res['is_play'] = $this->isPlayy($userId);
            
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
     * 
     *
     * @JsonRpcMethod
     */
    public function yeardAwardDraw() {
        // if(empty($params->egg) && in_array($params->egg, ['double_egg_color','double_egg_gold']) ){
        //     throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        // }
        global $userId;
        if(!$userId){
            throw new OmgException(OmgException::NO_LOGIN);
        }

        //是否触发间隔限制
        if(LotteryService::isTooOften($userId, array('alias_name'=> self::$attr_key ,'interval'=> 3) )) {
            throw new OmgException(OmgException::API_LIMIT);
        }

        //查询是否 剩余抽奖次数
        $beforeCounts = $this->isPlayy($userId);
        if(!$beforeCounts){
            return [
                'code' => -1,
                'message' => 'failed',
                'data' => '次数不足',
            ];
        }
        $_userInfo = call_user_func_array(array("App\Service\Func","getUserBasicInfo"),[$userId,true]);
        
        //积分是否够
        
        if($_userInfo['score'] <= 0){
            throw new OmgException(OmgException::INTEGRAL_LACK_FAIL);
        }
        //根据会员等级  （活动别名发奖）
        $actAlias = call_user_func(function($c){
            if($c < 2){
                return self::$attr_key."_01";
            }else if($c < 4){
                return self::$attr_key."_23";
            }else {
                return self::$attr_key."_45";
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
            Attributes::increment($userId , self::$attr_key ,1);//记录次数+1
            //抽奖完成，减去1积分
            $sub = Func::subIntegralByUser($userId,1,'年终奖翻倍抽奖减积分');
            if($sub['result']['code'] != 0){//扣积分 没有成功
                DB::rollBack();//回滚 
                throw new OmgException(OmgException::VALID_AMOUNT_ERROR);
            }
        }else{
            DB::rollBack();//回滚 
            throw new OmgException(OmgException::API_FAILED);
        }

        DB::commit();

        //获取奖品值
        $awardInfo = SendAward::getAward($res[0]['award_type'] ,$res[0]['award_id']);
        switch ($res[0]['award_type']) {
            case 7:
                $amount = $awardInfo['money'];
                break;
            case 1:
                $amount = $awardInfo['rate_increases']*100;
                break;
            case 2:
                $amount = $awardInfo['red_money'];
                break;
            case 3:
                $amount = $awardInfo['experience_amount_money'];
                break;
            default:
                # code...
                break;
        }
        return [
                'code' => 0,
                'message' => '抽奖成功',
                'data' => [
                    'name' => $res[0]['award_name'],
                    'type' => $res[0]['award_type'],
                    'amount' => $amount,
                ]
                
        ];
        

    }


    private function isPlayy($userId) {
        $haveCounts = Attributes::incrementByDay($userId, self::$attr_key ,0);

        return $haveCounts >= 2?false:true;
        
    }


    private function getAllawards($IDs) {
        $key = self::$attr_key."_allUserAwardList";
        return Cache::remember($key,5, function() use ($IDs){
            $_award = SendRewardLog::whereIn('activity_id',$IDs)->where('status','>=',1)->select('user_id','remark')->orderBy('id', 'desc')->take(50)->get()
                    ->map(function ($item, $key) {
                        $tmp = json_decode($item['remark'],1);
                        $phone = protectPhone(Func::getUserPhone($item['user_id']) );
                        return ['user'=>$phone ,'name'=> $tmp['award_name'],];
                    });
            return $_award;
        });
    }
}

