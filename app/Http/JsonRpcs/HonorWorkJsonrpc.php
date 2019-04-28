<?php

namespace App\Http\JsonRpcs;


use App\Models\Activity;
use App\Models\SendRewardLog;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\Func;
use App\Service\SendAward;
use Config,DB,Cache;
use Illuminate\Support\Facades\Redis;

use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\HonorWorkUpdateJob;
use App\Jobs\HonorWorkAwardJob;

class HonorWorkJsonRpc extends JsonRpc {

    use DispatchesJobs;
    /**
     * 劳动红包领取状态
     *TODO 读取config 加入缓存。
     *
     *
     * @JsonRpcMethod
     */
    public function workingInfo() {
        global $userId;


        $config = Config::get('honor_work');
        $awards = isset($config['red']) ? $config['red'] : [];
        $badge = isset($config['badge']) ? $config['badge'] : [];
        $welfare = isset($config['welfare']) ? $config['welfare'] : [];
        $key = isset($config['key']) ? $config['key'] : '';
        if(!$userId) {//未登录的时候也返回红包列表
            return [
                'code' => 0,
                'message' => 'success',
                'data' => ['red'=>$awards,'badge'=>$badge ,'welfare'=>$welfare]
            ];
        }
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->first();
        if($res){
            $res_text = json_decode($res->text,1);
            //队列刷新签到 属性
//            if(!$res_text['badge']['xianfeng']
//                || !$res_text['badge']['xianfeng']
//                || !Cache::has('HonorWork_check_in_'.$userId)
//            ){
//                //当这两个徽章不存在时 去检查签到
//                $this->dispatch(new HonorWorkUpdateJob($userId ,'check_in_alias'));
//                Cache::put('HonorWork_check_in_'.$userId,1,5);//5分钟刷新一次用户属性
//
//            }
//            //更新使用红包勋章
//            if(!$res_text['badge']['xianjin']
//                || !$res_text['badge']['mofan']
//                || !$res_text['badge']['aixin']
//                || !$res_text['badge']['jingye']
//                || !Cache::has('HonorWork_red_use_'.$userId)
//            ){
//                $this->dispatch(new HonorWorkUpdateJob($userId ,'check_red'));
//                Cache::put('HonorWork_red_use_'.$userId,1,5);//5分钟刷新一次用户属性
//            }
//这一逻辑 写在消息触发 实时中
//            if(!$res_text['badge']['tashi']){
//                //去检查  注册 分开检查
//                if(!Cache::has('HonorWork_check_invite'.$userId)){
//                    $this->dispatch(new HonorWorkUpdateJob($userId ,'check_invite'));
//                    Cache::put('HonorWork_check_invite'.$userId,1,5);//5分钟刷新一次用户属性
//                }
//            }
            return [
                'code' => 0,
                'message' => 'success',
                'data' => $res_text
            ];
        }
        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->key = $key;
        $userAttr->text = json_encode(['red'=>$awards,'badge'=>$badge ,'welfare'=>$welfare]);
        $res = $userAttr->save();
        if($res){
            return [
                'code' => 0,
                'message' => 'success',
                'data' => ['red'=>$awards,'badge'=>$badge ,'welfare'=>$welfare]
            ];
        }
    }
    /**
     * 劳动红包领取
     *
     * @JsonRpcMethod
     */
    public function workingRedDrew($params) {
        if(empty($params->key)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get('honor_work');
        $key = isset($config['key']) ? $config['key'] : '';
        $count = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->count();
        if($count < 1){
            $awards = isset($config['red']) ? $config['red'] : '';
            $badge = isset($config['badge']) ? $config['badge'] : [];
            $welfare = isset($config['welfare']) ? $config['welfare'] : [];
            $userAttr = new UserAttribute();
            $userAttr->user_id = $userId;
            $userAttr->key = $key;
            $userAttr->text = json_encode(['red'=>$awards,'badge'=>$badge,'welfare'=>$welfare]);
            $userAttr->save();
        }
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $data = SendAward::ActiveSendAward($userId,$params->key);
            if(isset($data[0]['status'])){
                $userAttrData = json_decode($res->text,1);
                if(isset($userAttrData['red'][$params->key]['status']) && $userAttrData['red'][$params->key]['status'] == 1){
                    DB::rollBack();
                    return [
                        'code' => -1,
                        'message' => 'fail',
                        'data' => '已领取'
                    ];
                }
                $userAttrData['red'][$params->key]['status'] = 1;
                $updatestatus = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->update(['text'=>json_encode($userAttrData)]);
            }
        }
        if(isset($updatestatus)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'领取成功'
            ];
        }
        DB::rollBack();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>isset($data['msg']) ? $data['msg'] : '领取失败'
        ];
    }

    /**
     * 福利轮播图
     *
     *
     * @JsonRpcMethod
     */
    public function workingWelfareList(){
        $newData = [];
        $list_key = 'honor_work_welfare';
        $data = Redis::LRANGE($list_key,0,20);
        foreach ($data as $value){
            $tempArray = explode(',' ,$value);
            array_push($newData,['username'=>$tempArray[0],'award'=>$tempArray[1] ]);
        }

        //array_push($data,['username'=>'188****88','award'=>'2000积分']);
        //array_push($data,['username'=>'188****18','award'=>'1.5%加息券']);

        return [
            'code' => 0,
            'message' => 'success',
            'data' =>$newData
        ];
    }

    /**
     * 分享回调
     *
     * @JsonRpcMethod
     */
    public function shareHonorWorking(){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        $config = Config::get('honor_work');
        $key = isset($config['key']) ? $config['key'] : '';
        $witch_honor = isset($config['rule']['share']) ? $config['rule']['share'] : '';
        $count = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->count();
        if($count < 1){
            $awards = isset($config['red']) ? $config['red'] : '';
            $badge = isset($config['badge']) ? $config['badge'] : [];
            $welfare = isset($config['welfare']) ? $config['welfare'] : [];
            $userAttr = new UserAttribute();
            $userAttr->user_id = $userId;
            $userAttr->key = $key;
            $userAttr->text = json_encode(['red'=>$awards,'badge'=>$badge,'welfare'=>$welfare]);
            $userAttr->save();
        }
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);
            if(isset($userAttrData['badge'][$witch_honor]) && $userAttrData['badge'][$witch_honor] == 1){
                DB::rollBack();
                return [
                    'code' => -1,
                    'message' => 'fail',
                    'data' => '已获得'
                ];
            }
            $userAttrData['badge'][$witch_honor] = 1;
            $updatestatus = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->update(['text'=>json_encode($userAttrData)]);
        }

        if(isset($updatestatus)){
            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'获得成功'
            ];
        }
        DB::rollBack();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>isset($data['msg']) ? $data['msg'] : '失败'
        ];
    }


    /**
     * 领取福利一、二、三四
     *
     * @JsonRpcMethod
     */
    public function workingWelfareDraw($params){
        global $userId;
        if(!$userId) {
            throw new OmgException(OmgException::NO_LOGIN);
        }

        if(empty($params->welfare)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        $config = Config::get('honor_work');
        $key = isset($config['key']) ? $config['key'] : '';
        $alias_act = 'honor_work_'.$params->welfare;//对应活动别名
        //检查用户属性 是否符合条件
        DB::beginTransaction();
        $res = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->lockForUpdate()->first();
        if($res){
            $userAttrData = json_decode($res->text,1);
            $user_badge = $userAttrData['badge'];
            $honor_count = array_count_values($user_badge);

            //1当前福利 未领取 && 勋章个数符合条件
            if($userAttrData['welfare'][$params->welfare]['status'] !=1 && isset($honor_count[1]) && $honor_count[1] >= $userAttrData['welfare'][$params->welfare]['condition']){
                $userAttrData['welfare'][$params->welfare]['status'] = 1;
                //更新数据
                $updatestatus = UserAttribute::where(['key'=>$key,'user_id'=>$userId])->update(['text'=>json_encode($userAttrData)]);
            }
        }

        if(isset($updatestatus)){
            $this->dispatch(new HonorWorkAwardJob($userId ,$alias_act));

            DB::commit();
            return [
                'code' => 0,
                'message' => 'success',
                'data' =>'领取成功'
            ];
        }
        DB::rollBack();
        return [
            'code' => -1,
            'message' => 'fail',
            'data' =>'领取失败'
        ];

    }
}
