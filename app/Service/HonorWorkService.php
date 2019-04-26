<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\InviteLimitTask;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Models\UserAttribute;
use App\Models\ActivityJoin;
use App\Models\Activity;
use App\Service\ActivityService;
use App\Service\Func;
use Lib\JsonRpcClient;

use DB, Config,Cache;
use Illuminate\Support\Facades\Redis;

class HonorWorkService
{
    const HONOR_CONFIG = 'honor_work';


    public $user_id = 0;
    public $act_ids = [];

    /**
     *  构造 从数据库中获取  好友邀请3.0的配置
     */
    public function __construct($userId = 0)
    {
        $this->user_id                 = $userId;
        //$this->act_ids = $this->getAliasID();//TODO 使用红包
    }
    //***********缓存劳动场红包别名 列表**************************
    private function getAliasID(){
        return Cache::remember('RED_HONOR_WORK', 60, function () {
            $config = Config::get(self::HONOR_CONFIG);
            $aliasIDs=[];
            foreach ($config['red'] as $k=>$v){
                $act_info = ActivityService::GetActivityedInfoByAlias($k);
                if(!empty($act_info)){
                    array_push($aliasIDs,$act_info->id);
                }
            }

            return $aliasIDs;
        });
    }
    //***************************************************

    //使用特定的红包 返回remark 到活动参与表
    public function isSpecialRed($source_id)
    {
        if (!$this->user_id ) {
            return false;
        }

        $red_array = $this->getAliasID();
        if(in_array($source_id , $red_array)){
            return 1;
        }else{
            return 0;
        }

    }

    //签到、使用红包 更新用户属性
    //type :check_in_alias|check_red
    public function updateCheckInAttr($type,$source_id = 0)
    {
        $config = Config::get(self::HONOR_CONFIG);
        //获取要检查的活动别名（签到、邀请注册）
        $check_in_alias = $config['rule'][$type];
        $activityId = ActivityService::GetActivityInfoByAlias($check_in_alias);

        $activityId = isset($activityId['id']) ? $activityId['id'] : 0;
        if($activityId <= 0) {
            return 0;
        }

        if($type == 'check_red' && $this->isSpecialRed($source_id)){
            //先查看本次 "使用红包回调"是不是指定到8个红包
            $check_in = ActivityJoin::select('created_at', 'user_id')
                ->where('user_id', $this->user_id)
                ->where('activity_id', $activityId)
                ->where('status',3)
                ->where('remark','[1]')//使用到是 指定到8个红包
                ->orderBy('id', 'desc')->count();
        }else{
            $check_in = ActivityJoin::select('created_at', 'user_id')
                ->where('user_id', $this->user_id)
                ->where('activity_id', $activityId)
                ->where('status',3)
                ->orderBy('id', 'desc')->count();
        }


        DB::beginTransaction();
        $res = UserAttribute::where(['key'=> $config['key'],'user_id'=>$this->user_id])
            ->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);
            ////签到数**************************
            if($type == 'check_in_alias' && $check_in < 3){
                $userAttrData['badge']['xianfeng'] = 1;//签到第一次 发房 勤劳勋章
                if($check_in == 2){//累计已经签到2次  再加本次签到 共计3次
                    $userAttrData['badge']['qinlao'] = 1;
                }
            }
            //红包使用数********************
            if($type == 'check_red' && $check_in < 4){

                $userAttrData['badge']['xianjin'] = 1;//发房 先进勋章
                if($check_in == 1){
                    $userAttrData['badge']['mofan'] = 1;//发房 先进勋章
                }elseif ($check_in == 2){
                    $userAttrData['badge']['aixin'] = 1;//发房 先进勋章
                }elseif ($check_in == 3){
                    $userAttrData['badge']['jingye'] = 1;//发房 先进勋章
                }
            }

            //**********************************
            $updatestatus = UserAttribute::where(['key'=>$config['key'],'user_id'=>$this->user_id])
                ->update(['text'=>json_encode($userAttrData)]);
            if(isset($updatestatus)){
                DB::commit();
                return 1;
            }
        }

        DB::rollBack();
        return 0;

    }

    //绑卡发放 踏实勋章
    public function updateHonorInviteAttr($user_id){

        $from_user_id = Func::getUserBasicInfo($user_id);
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=> self::HONOR_CONFIG,'user_id'=>$from_user_id['from_user_id']])
            ->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);

            //邀请注册数
            if($userAttrData['badge']['tashi'] == 1 ){
                DB::rollBack();//已经有勋章  不发送
                return 0;
            }
            $userAttrData['badge']['tashi'] = 1;//发房 踏实勋章

            $updatestatus = UserAttribute::where(['key'=>self::HONOR_CONFIG,'user_id'=>$from_user_id['from_user_id']])
                ->update(['text'=>json_encode($userAttrData)]);
            if(isset($updatestatus)){
                DB::commit();
                return 1;
            }
        }

        DB::rollBack();
        return 0;

    }

}