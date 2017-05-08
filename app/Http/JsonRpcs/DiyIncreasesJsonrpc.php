<?php

namespace App\Http\JsonRpcs;
use App\Exceptions\OmgException;
use App\Models\DiyIncreases;
use App\Models\UserAttribute;
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

        if (empty($userId)) {
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $config = Config::get("diyIncreases");
        if(empty($config)){
            throw new OmgException(OmgException::API_FAILED);
        }
        //判断多动是否结束
        $isEnd = DiyIncreasesBasic::activityIsExist('diy_increases_time');
        if(!$isEnd){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }
        //DIY加息列表
        $where['user_id'] = $userId;
        $where['key'] = $config['key'];
        $list = UserAttribute::where($where)->orderByRaw("id asc")->get()->toArray();

        //格式化列表
        $newList = [];
        foreach($list as $k => $item){
            if(isset($item['id']) && !empty($item['id'])){
                $increases = $config['default_value'] + $item['number'];
                if($increases > 35){
                    $increases = 35;
                }
                //第几个加息券
                $newList[$k]['id'] = $item['id'];
                $newList[$k]['increases'] = $increases / 10;
                $newList[$k]['is_receive'] = intval($item['string']);
                //获取邀请人加息列表
                $newList[$k]['invite_list'] = DiyIncreases::where(['increases_id' => $item['id']])->orderBy('id','desc')->get()->toArray();
                foreach($newList[$k]['invite_list'] as &$val){
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
        //判断多动是否结束
        $isEnd = DiyIncreasesBasic::activityIsExist('diy_increases_time');
        if(!$isEnd){
            throw new OmgException(OmgException::ACTIVITY_NOT_EXIST);
        }

        $id = isset($params->id) && !empty($params->id) ? $params->id : 0;
        $status = DiyIncreasesBasic::_DIYIncreasesSend($id,$userId);
        if(isset($status['status']) && $status['status'] == true){
            //如果没有生成记录就手动生成
            if($id <= 0){
                //添加到用户属性表
                $userAttId = DiyIncreasesBasic::setUserAttributesItem($userId,0,$config);
                //修改为已发送状态
                if($userAttId > 0){
                    $id = $userAttId;
                }

            }
            //修改为已领取状态
            UserAttribute::where(['id' => $id])->update(['string'=>1]);
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
