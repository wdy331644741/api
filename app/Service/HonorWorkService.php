<?php
namespace App\Service;

use App\Exceptions\OmgException;
use App\Models\InviteLimitTask;
use App\Service\Attributes;
use App\Service\GlobalAttributes;
use App\Service\SendAward;
use App\Models\GlobalAttribute;
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
    public function __construct($userId = 0 ,$id=0)
    {
        $this->user_id                 = $userId;
        $this->act_ids = $this->getAliasID();
    }
    //***********缓存劳动场红包别名 列表**************************
    private function getAliasID(){
        return Cache::remember('RED_HONOR_WORK', 60, function () {
            $config = Config::get(self::HONOR_CONFIG);
            $aliasIDs=[];
            foreach ($config['red'] as $k=>$v){
                $act_info = ActivityService::GetActivityedInfoByAlias($k);
                array_push($aliasIDs,$act_info->id);
            }

            return $aliasIDs;
        });
    }
    //***************************************************
    //领取任务  新增数据
    public function updateHonorWorkAttr()
    {
        if (!$this->user_id ) {
            return false;
        }

//        DB::beginTransaction();
//
//        DB::commit();
        return true;
    }



}