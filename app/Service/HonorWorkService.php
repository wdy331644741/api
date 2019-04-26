<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\InviteLimitTask;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Models\UserAttribute;
use App\Models\Activity;
use App\Service\ActivityService;

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
    public function updateHonorWorkAttr($source_id)
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

    //签到  发放勋章
    public function updateCheckInAttr(){

    }


    //注册发放 踏实勋章
    public function updateHonorInviteAttr($from_user_id){

        DB::beginTransaction();
        $res = UserAttribute::where(['key'=> self::HONOR_CONFIG,'user_id'=>$from_user_id])
            ->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);

            //邀请注册数
            if($userAttrData['badge']['tashi'] == 1 ){
                DB::rollBack();//已经有勋章  不发送
                return 0;
            }
            $userAttrData['badge']['tashi'] = 1;//发房 踏实勋章

            $updatestatus = UserAttribute::where(['key'=>self::HONOR_CONFIG,'user_id'=>$from_user_id])
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