<?php

namespace App\Http\JsonRpcs;

use App\Exceptions\OmgException;
use App\Models\OneYuanUserInfo;
use App\Service\SendAward;
use Lib\JsonRpcClient;
use App\Service\OneYuanBasic;
use App\Models\OneYuan;
use App\Models\OneYuanJoinInfo;
use App\Models\OneYuanUserRecord;
use App\Service\Func;
use App\Service\SendMessage;
use Illuminate\Pagination\Paginator;

class OneYuanJsonRpc extends JsonRpc {

    /**
     *  获取用户还有多少次抽奖次数
     *
     * @JsonRpcMethod
     */
    public function oneYuanUserNum() {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        //昨天的一元夺宝商品
        $data = OneYuanUserInfo::where('user_id',$userId)->select('user_id','num')->first();
        if(empty($data)){
            $data = array('user_id'=>$userId,'num'=>0);
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        );
    }
    /**
     *  商品列表
     *
     * @JsonRpcMethod
     */
    public function oneYuanMallList() {
        $where['status'] = 1;
        //昨天的一元夺宝商品
        $yesterdayList = OneYuan::where($where)
            ->where('end_time', '<=', date("Y-m-d H:i:s"))
            ->orderBy('end_time','desc')->take(1)
            ->get()->toArray();
        $list['pass'] = $this->_formatData($yesterdayList);
        //今天的一元夺宝商品
        $todayList = OneYuan::where($where)
            ->where('start_time', '<=', date("Y-m-d H:i:s"))
            ->where('end_time', '>=', date("Y-m-d H:i:s"))
            ->take(1)->get()->toArray();
        $list['now'] = $this->_formatData($todayList);
        //明天的一元夺宝商品
        $tomorrowList = OneYuan::where($where)
            ->where('start_time', '>=', date("Y-m-d H:i:s"))
            ->orderBy('start_time','asc')->take(1)
            ->get()->toArray();
        $list['next'] = $this->_formatData($tomorrowList);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        );
    }
    /**
     *  往期列表
     *
     * @JsonRpcMethod
     */
    public function oneYuanHistoryMallList($params) {
        $page = isset($params->page) ? $params->page : 1;
        $per_page = intval($params->per_page);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if(empty($page) || empty($per_page)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $where['status'] = 1;
        //昨天的一元夺宝商品
        $yesterdayList = OneYuan::where($where)
            ->where('end_time', '<', date("Y-m-d H:i:s"))
            ->orderBy('end_time','desc')->paginate($per_page);
        $this->_formatData($yesterdayList);
        return array(
            'code' => 0,
            'message' => 'success',
            'data' => $yesterdayList,
        );
    }
    protected function _formatData($data){
        if(empty($data)){
            return $data;
        }
        foreach($data as &$item){
            $item['time_diff'] = strtotime($item['start_time']) - time();
            $item['code_time_diff'] = strtotime($item['end_time']) - time();
            //如果有获奖的用户
            $item['phone'] = '';
            if(isset($item['user_id']) && !empty($item['user_id'])){
                //获取用户手机号
                $userPhone = Func::getUserPhone($item['user_id']);
                $item['phone'] = !empty($userPhone) ? substr_replace($userPhone, '******', 3, 6) : '';
            }
            if(!empty($item['luck_code'])){
                $item['luck_code'] = $item['luck_code']+10000001;
            }
            //去掉不需要的数据
            unset($item['status']);
            unset($item['buy_id']);
            unset($item['total_times']);
            unset($item['priority']);
        }
        return $data;
    }
    /**
     *  购买积分
     *
     * @JsonRpcMethod
     */
    public function buyLuckNum($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $num = intval($params->num);
        $trade_pwd = $params->tradePwd;
        if(empty($num) || empty($trade_pwd)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //先插入一条日志数据
        $uuid = SendAward::create_guid();
        $operation = array();
        $operation['user_id'] = $userId;
        $operation['num'] = $num;
        $operation['source'] = 'buy';
        $operation['snapshot'] = json_encode(array('buy'=>$num));
        $operation['type'] = 0;
        $operation['uuid'] = $uuid;//唯一id
        $operation['status'] = 1;//失败状态
        $operation['operation_time'] = date("Y-m-d H:i:s");
        $id = OneYuanUserRecord::insertGetId($operation);
        //调用孙峰接口余额购买次数
        $url = env('TRADE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $param['userId'] = $userId;
        $param['id'] = $id;
        $param['uuid'] = $uuid;
        $param['amount'] = $num;
        $param['trade_pwd'] = $trade_pwd;
        $param['sign'] = hash('sha256',$userId."3d07dd21b5712a1c221207bf2f46e4ft");
        $result = $client->qi_bao_purchase($param);
        //如果成功
        if(isset($result['result']) && !empty($result['result'])) {
            //记录修改为成功状态
            OneYuanUserRecord::where("id",$id)->update(array("status"=>0,"operation_time"=>date("Y-m-d H:i:s")));
            //用户次数增加
            $count = OneYuanUserInfo::where("user_id",$userId)->count();
            if(!$count){
                //插入一条数据
                $data = array();
                $data['user_id'] = $userId;
                $data['num'] = $num;
                $data['updated_at'] = date("Y-m-d H:i:s");
                $data['created_at'] = date("Y-m-d H:i:s");
                OneYuanUserInfo::insertGetId($data);
            }else{
                OneYuanUserInfo::where('user_id',$userId)->increment('num', $num,array('updated_at'=>date("Y-m-d H:i:s")));
            }
            return array(
                'code' => 0,
                'message' => 'success'
            );
        }

        OneYuanUserRecord::where("id",$id)->update(array("status"=>1,"remark"=>json_encode($result),"operation_time"=>date("Y-m-d H:i:s")));
        //如果失败
        $msg = isset($result['error']['code']) && $result['error']['code'] < 0 ? array("message"=>"服务异常") : $result['error'];
        return array(
            'code' => -1,
            'message' => 'fail',
            'data' =>$msg
        );
    }
    /**
     * 参与抽奖
     * @JsonRpcMethod
     */
    public function oneYuanJoin($params) {
        global $userId;
        if(empty($userId)){
            throw new OmgException(OmgException::NO_LOGIN);
        }
        $mallId = intval($params->mallId);
        $num = intval($params->num);
        if(empty($mallId) || empty($num)){
            throw new OmgException(OmgException::PARAMS_NEED_ERROR);
        }
        //判断当前商品还能不能参加抽奖
        $info = OneYuan::where("id",$mallId)->where("status",1)->select('id','total_num','buy_num')->lockForUpdate()->first();
        if(empty($info)){
            throw new OmgException(OmgException::NO_DATA);
        }
        if($info->buy_num >= $info->total_num){
            throw new OmgException(OmgException::ONEYUAN_FULL_FAIL);
        }
        if($info->buy_num < $info->total_num){
            if((($info->total_num-$info->buy_num) - $num) < 0){
                throw new OmgException(OmgException::EXCEED_NUM_FAIL);
            }
        }
        //获取用户的抽奖次数判断是否够用
        $totalNum = OneYuanBasic::getUserLuckNum($userId);
        if(isset($totalNum['status']) && !empty($totalNum['status'])){
            if($totalNum['data'] >= $num){
                //添加到抽奖记录表中
                $return = OneYuanBasic::insertJoinInfo($userId,$mallId,$num);
                if(isset($return['status']) && $return['status'] === true){
                    //商品抽奖次数增加
                    OneYuan::where("id",$mallId)->where("status",1)->increment('buy_num',$num);
                    $joinId = isset($return['data']) ? $return['data'] : 0;
                    if(empty($joinId)){
                        return array(
                            'code' => -1,
                            'message' => '数据异常'
                        );
                    }
                    //用户减少抽奖次数
                    $return = OneYuanBasic::reduceNum($userId,$num,'mall',array('mall'=>$mallId));
                    if(isset($return['status']) && $return['status'] === true){
                        $joinList = OneYuanJoinInfo::where("id",$joinId)->first();
                        $codeData = '';
                        if(isset($joinList['num'])){
                            if($joinList['num'] > 1){
                                $codeData = (10000000+$joinList['start'])."~".($joinList['end']+10000000);
                            }else{
                                $codeData = 10000000+$joinList['start'];
                            }
                        }
                        //发送站内信
                        $template = "感谢您参与夺宝奇兵，期号：".str_pad($info->id,2,"0",STR_PAD_LEFT)."，您的抽奖码为：{{start}}";
                        $arr = array('start'=>$joinList['start']+10000000);
                        if($joinList['num'] > 1){
                            $template = "感谢您参与夺宝奇兵，期号：".str_pad($info->id,2,"0",STR_PAD_LEFT)."您的抽奖码为：{{start}} ~ {{end}}";
                            $arr = array('start'=>$joinList['start']+10000000,'end'=>$joinList['end']+10000000);
                        }
                        SendMessage::Mail($userId,$template,$arr,"抽奖码");
                        return array(
                            'code' => 0,
                            'message' => 'success',
                            'data'=>$codeData
                        );
                    }
                }
            }else{
                throw new OmgException(OmgException::EXCEED_USER_NUM_FAIL);
            }
        }
        return array(
            'code' => -1,
            'message' => '服务异常'
        );
    }
    /**
     * 夺宝记录
     * @JsonRpcMethod
     */
    public function oneYuanJoinList($params) {
        $page = isset($params->page) ? $params->page : 1;
        $per_page = intval($params->per_page);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        if(empty($page) || empty($per_page)){
            throw new OmgException(OmgException::API_MIS_PARAMS);
        }
        $where['status'] = 1;
        //今天的一元夺宝商品
        $todayList = OneYuan::where($where)
            ->where('start_time', '<=', date("Y-m-d H:i:s"))
            ->where('end_time', '>=', date("Y-m-d H:i:s"))
            ->select('id')
            ->first();
        if(empty($todayList)){
            throw new OmgException(OmgException::NO_DATA);
        }
        //获取正在夺宝的记录
        $list = OneYuanJoinInfo::where('mall_id',$todayList->id)
            ->orderBy('id','desc')
            ->paginate($per_page);
        if(empty($list)){
            throw new OmgException(OmgException::NO_DATA);
        }
        foreach($list as &$item){
            $userPhone = Func::getUserPhone($item['user_id']);
            $item['phone'] = !empty($userPhone) ? substr_replace($userPhone, '******', 3, 6) : '';
        }
        return array(
            'code' => 0,
            'message' => 'success',
            'data'=>$list
        );
    }
}
