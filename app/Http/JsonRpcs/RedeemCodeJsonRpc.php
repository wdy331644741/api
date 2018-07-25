<?php

namespace App\Http\JsonRpcs;
use App\Models\RedeemAward;
use App\Models\RedeemCode;
use App\Service\Attributes;
use App\Service\SendAward;
use App\UserAttribute;
use Cache,DB;
use App\Exceptions\OmgException as OmgException;
use Illuminate\Support\Facades\Config;

class RedeemCodeJsonRpc extends JsonRpc {
    
    /**
     *  banner列表
     *
     * @JsonRpcMethod
     */
    public function sendCodeAward($params) {
        global $userId;
        //防止刷兑换码
        $key = 'user_id_'.$userId;
        $frequency = Cache::get($key);
        $configFrequency = Config::get('redeem.frequency');
        if($frequency >= $configFrequency){
            throw new OmgException(OmgException::FREQUENCY_ERROR);
        }
        $where = array();
        //code码
        $code = $params->code;
        if (empty($code)) {
            throw new OmgException(OmgException::VALID_CODE_FAIL);
        }else{
            $where['is_use'] = 0;
            $where['code'] = strtoupper($code);
        }
        //用户ID
        if (empty($userId)) {
            throw new OmgException(OmgException::VALID_USERID_FAIL);
        }
        //获取兑换码关联表ID
        $code_id = RedeemCode::where($where)->select('rel_id')->get()->toArray();
        $code_id = isset($code_id[0]['rel_id']) && !empty($code_id[0]['rel_id']) ? $code_id[0]['rel_id'] : 0;
        $is_password = 0;
        if(empty($code_id)){
            //错误次数加1
            Cache::put($key,$frequency+1,60);
            //判断是否是口令红包
            $code_id = $this->isPasswordRedEnvelopes($userId,$params->code);
            if($code_id > 0){
                $is_password = 1;
            }else{
                throw new OmgException(OmgException::GET_CODEDATAEMPTY_FAIL);
            }
        }
        //获取奖品类型和奖品id和奖品名称
        $list = $this->getAwardInfo($code_id);
        if(!empty($list)){
            if(empty($list['award_type']) && empty($list['award_id']) && empty($list['name']) && empty($list['award_id'])){
                throw new OmgException(OmgException::GET_AWARDDATAEXIST_FAIL);
            }
            $table = SendAward::_getAwardTable($list['award_type']);
            $info = $table::where('id', $list['award_id'])->get()->toArray();
            if(count($info) >= 1){
                $info = $info[0];
            }
            if(!empty($info)){
                //来源id
                $info['source_id'] = $list['id'];
                //来源名称
                $info['source_name'] = $list['name'];
                //用户id
                $info['user_id'] = $userId;
                //兑换码
                $info['code'] = $code;
                //触发类型---兑换码触发类型
                $info['trigger'] = 8;
                $status = false;
                if($list['award_type'] == 1){
                    //加息券
                    $status = SendAward::increases($info);
                }elseif($list['award_type'] == 2){
                    //直抵红包&&新手直抵红包
                    if(isset($info['red_type']) && ($info['red_type'] == 1 || $info['red_type'] == 3)){
                        $status = SendAward::redMoney($info);
                    }
                    //百分比红包
                    if(isset($info['red_type']) && $info['red_type'] == 2){
                        $status = SendAward::redMaxMoney($info);
                    }
                }elseif($list['award_type'] == 3){
                    //体验金
                    $status = SendAward::experience($info);
                }elseif($list['award_type'] == 4){
                    //积分
                    $status = SendAward::integral($info,[]);
                }elseif($list['award_type'] == 6){
                    //优惠券
                    $status = SendAward::coupon($info);
                }elseif($list['award_type'] == 7){
                    //现金
                    $status = SendAward::cash($info);
                }
                if(!$status){
                    throw new OmgException(OmgException::SENDAEARD_FAIL);
                }else{
                    if($is_password == 1){
                        RedeemAward::where('id',$code_id)->increment("use_num",1);
                        Attributes::increment($userId,"redeem_password",1);
                        Attributes::increment($userId,"redeem_password_".$code_id,1);
                        DB::commit();
                    }else{
                        //修改兑换码状态为已使用
                        RedeemCode::where('code',$code)->update(array('is_use'=>1,'user_id'=>$userId));
                    }
                    return array(
                        'code' => 0,
                        'message' => 'success'
                    );
                }
            }
        }else{
            throw new OmgException(OmgException::GET_AWARDDATAEMPTY_FAIL);
        }
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
    public function getAwardInfo($code_id){
        $date = date("Y-m-d H:i:s");
        $where = array();
        $where['id'] = $code_id;
        $where['status'] = 2;
        $list =  RedeemAward::where($where)->where('expire_time','>=',$date)->get()->toArray();
        $list = isset($list[0]) && !empty($list[0]) ? $list[0] : array();
        return $list;
    }
    private function isPasswordRedEnvelopes($userId,$code){
        $code = trim($code);
        $data = RedeemAward::where("name",$code)->first();
        if(isset($data['id']) && $data['id'] > 0){
            if($data['use_num'] >= $data['number']){
                //口令红包已领完
                throw new OmgException(OmgException::REDEEM_EMPTY);
            }
            //事务开始
            DB::beginTransaction();
            Attributes::getItemLock($userId,"redeem_password");//锁住该用户行
            $userCount = Attributes::getNumber($userId,"redeem_password_".$data['id']);
            //判断用户是否领取过该口令红包
            if($userCount > 0){
                throw new OmgException(OmgException::REDEEM_IS_GET);
            }
            return $data['id'];
        }else{
            throw new OmgException(OmgException::GET_CODEDATAEMPTY_FAIL);
        }
    }
}
