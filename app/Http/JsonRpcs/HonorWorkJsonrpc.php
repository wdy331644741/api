<?php

namespace App\Http\JsonRpcs;


use App\Models\Activity;
use App\Models\SendRewardLog;
use App\Models\UserAttribute;
use App\Exceptions\OmgException;
use App\Service\Func;
use App\Service\SendAward;
use Config,DB,Cache;

use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\HonorWorkUpdateJob;

class HonorWorkJsonRpc extends JsonRpc {

    use DispatchesJobs;
    /**
     * 劳动红包领取状态
     *
     * @JsonRpcMethod
     */
    public function workingInfo() {
        global $userId;

        $userId = 5101340;


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
            //队列刷新签到 属性
            $res_text = json_decode($res->text,1);
            if(!$res_text['badge']['xianfeng'] || !$res_text['badge']['xianfeng'] ){
                //当这两个徽章不存在时
                if(!Cache::has('HonorWork_'.$userId)){
                    $this->dispatch(new HonorWorkUpdateJob($userId));
                    Cache::put('HonorWork_'.$userId,1,1);//5分钟刷新一次用户属性
                }
            }
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
     * TODO
     *
     * @JsonRpcMethod
     */
    public function workingWelfareList(){

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
}
