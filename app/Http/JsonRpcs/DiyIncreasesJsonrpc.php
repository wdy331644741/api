<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\DiyIncreases;
use App\Models\UserAttribute;
use App\Service\AmountShareBasic;
use App\Models\AmountShare;
use App\Models\AmountShareInfo;
use App\Service\Attributes;
use App\Service\DiyIncreasesBasic;
use App\Service\Func;
use Config;

class DiyIncreasesJsonRpc extends JsonRpc
{
    /**
     *  获取该用户DIY加息券列表
     *
     * @JsonRpcMethod
     */
    public function DiyIncreasesList()
    {
        global $userId;
        $userId = 1716845;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get("diyIncreases");
        if(empty($config)){
            throw new OmgException(OmgException::API_FAILED);
        }
        //DIY加息列表
        $where['user_id'] = $userId;
        $where['key'] = $config['key'];
        $list = UserAttribute::where($where)->orderByRaw("id asc")->get()->toArray();

        //格式化列表
        $newList = [];
        foreach($list as $item){
            if(isset($item['id']) && !empty($item['id'])){
                $increases = $config['default_value'] + $item['number'];
                if($increases > 35){
                    $increases = 35;
                }
                //第几个加息券
                $thisNum = intval($item['string']);
                $newList[$thisNum]['increases'] = $increases/10;
                $newList[$thisNum]['this_number'] = $thisNum;
                $newList[$thisNum]['is_receive'] = intval($item['text']);
                $newList[$thisNum]['expired_time'] = !empty($item['updated_at']) ? strtotime($item['updated_at']) + (3600*24*7) : 0;
                //获取邀请人加息列表
                $newList[$thisNum]['invite_list'] = DiyIncreases::where(['increases_id' => $item['id']])->orderBy('id','desc')->get()->toArray();
                foreach($newList[$thisNum]['invite_list'] as &$val){
                    if(isset($val['id']) && !empty($val['id'])) {
                        $val['number'] = $val['number'] / 10;
                        //获取用户加密手机号
                        $phone = Func::getUserPhone($val['invite_user_id']);
                        $val['phone'] = !empty($phone) ? substr_replace($phone, '******', 3, 6) : "";
                    }
                }
            }
        }

        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $newList
        );
    }

    /**
     *  DIY加息券领奖
     *
     * @JsonRpcMethod
     */
    public function DiyIncreasesSend($params)
    {
        global $userId;
        $userId = 1716845;
        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get("diyIncreases");
        if(empty($config)){
            return array(
                'code' => -1,
                'message' => 'config_faild'
            );
        }
        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        $status = DiyIncreasesBasic::_DIYIncreasesSend($id,$userId);
        if(isset($status['status']) && $status['status'] == true){
            //修改为已经领取状态
            Attributes::increment($userId,$config['num_key'],1);
            //修改为已经领取状态
            if($id > 0){
                UserAttribute::where(['id' => $id])->update(['text'=>1]);
            }
            return array(
                'code' => 0,
                'message' => 'success',
                'data' => $status['award_name']
            );
        }
        return array(
            'code' => -1,
            'message' => 'faild'
        );
    }

}
