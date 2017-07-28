<?php
namespace App\Service;
use App\Models\LifePrivilege;
use App\Models\LifePrivilegeConfig;
use Config;
use GuzzleHttp\Client;

class FeeAndFlowBasic
{
    private $_client = null;
    function __construct() {
        $this->_client = new Client([
            'base_uri'=>env("OFPAY_API_ADDR"),
            'timeout'=>9999.0
        ]);
    }

    /**
     * 根据手机号前六位获取归属地
     * @param $phone
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     */
    function AttributionInfo($phone){
        if(strlen($phone) == 7){
            $res = $this->_client->post('/mobinfo.do', ['form_params' => ['mobilenum'=>$phone]]);
            return $res->getBody()->getContents();
        }
        return false;
    }

    /**
     * 判断用户余额是否够用
     * @param $userId
     * @param $amount
     * @return bool
     */
    static function AmountIsEnough($userId,$amount){
        $userInfo = Func::getUserBasicInfo($userId,true);
        $userAmount = isset($userInfo['avaliable']) ? $userInfo['avaliable'] : 0;
        if($userAmount >= $amount){
            return true;
        }
        return false;
    }
    function FeeSend($phone,$cardNum,$uuid){
        //获取配置文件
        $config = Config::get("feeandflow.fee");
        //拼接参数
        $feeParams['userid'] = env('OFPAY_USER_ID');
        $feeParams['userpws'] = env('OFPAY_USER_PASS');
        $feeParams['cardid'] = $config['fee_cardid'];
        $feeParams['cardnum'] = intval($cardNum);
        $feeParams['mctype'] = '';
        $feeParams['sporder_id'] = $uuid;//唯一订单id
        $feeParams['sporder_time'] = date("YmdHis");
        $feeParams['game_userid'] = $phone;
        //获取验签
        $md5Str = self::makeFeeMd5Str($feeParams);
        if(empty($md5Str)){
            return false;
        }
        $feeParams['md5_str'] = $md5Str;
        $feeParams['ret_url'] = env("APP_URL")."/yunying/wl/fee_flow_callback";
        $feeParams['version'] = $config['fee_version'];
        $feeParams['buynum'] = '';
        //请求接口
        $res = $this->_client->post('/onlineorder.do', ['form_params' => $feeParams]);
        $res = self::xmlToArray($res->getBody());
        if(!empty($res)) {
            $res['callback'] = $feeParams['ret_url'];
        }
        return $res;
    }
    /**
     * 根据手机号和面值查询商品信息
     * @param $phone
     * @param $perValue
     * @param $flowValue
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    function FeeInfo($phone,$perValue){
        //配置文件
        $config = Config::get("feeandflow.fee");
        //拼接参数
        $feeParams['userid'] = env('OFPAY_USER_ID');
        $feeParams['userpws'] = env('OFPAY_USER_PASS');
        $feeParams['phoneno'] = $phone;
        $feeParams['pervalue'] = intval($perValue);
        $feeParams['mctype'] = '';
        $feeParams['version'] = $config['fee_version'];
        //请求接口
        $res = $this->_client->post('/telquery.do', ['form_params' => $feeParams]);
        $res = self::xmlToArray($res->getBody());
        return $res;
    }
    /**
     * 流量发送
     * @param $phone
     * @param $perValue
     * @param $flowValue
     * @param $uuid
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     */
    function FlowSend($phone,$perValue,$flowValue,$uuid){
        //获取配置文件
        $config = Config::get("feeandflow.flow");
        //拼接参数
        $flowParams['userid'] = env('OFPAY_USER_ID');
        $flowParams['userpws'] = env('OFPAY_USER_PASS');
        $flowParams['phoneno'] = $phone;
        $flowParams['perValue'] = intval($perValue);
        $flowParams['flowValue'] = trim($flowValue);
        $flowParams['range'] = $config['flow_range'];
        $flowParams['effectStartTime'] = $config['flow_effectStartTime'];
        $flowParams['effectTime'] = $config['flow_effectTime'];
        $flowParams['netType'] = $config['flow_netType'];
        $flowParams['sporderId'] = $uuid;//唯一订单id
        $flowParams['retUrl'] = env("APP_URL")."/yunying/wl/fee_flow_callback";
        $flowParams['version'] = $config['flow_version'];
        //获取验签
        $md5Str = self::makeFlowMd5Str($flowParams);
        if(empty($md5Str)){
            return false;
        }
        $flowParams['md5Str'] = $md5Str;
        //请求接口
        $res = $this->_client->post('/flowOrder.do', ['form_params' => $flowParams]);
        $res = self::xmlToArray($res->getBody());
        if(!empty($res)){
            $res['callback'] = $flowParams['retUrl'];
        }
        return $res;
    }

    /**
     * 流量商品信息
     * @param $phone
     * @param $perValue
     * @param $flowValue
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    function FlowInfo($phone,$perValue,$flowValue){
        //配置文件
        $config = Config::get("feeandflow.flow");
        //拼接参数
        $flowParams['userid'] = env('OFPAY_USER_ID');
        $flowParams['userpws'] = env('OFPAY_USER_PASS');
        $flowParams['phoneno'] = $phone;
        $flowParams['perValue'] = $perValue;
        $flowParams['flowValue'] = $flowValue;
        $flowParams['range'] = $config['flow_range'];
        $flowParams['effectStartTime'] = $config['flow_effectStartTime'];
        $flowParams['effectTime'] = $config['flow_effectTime'];
        $flowParams['netType'] = $config['flow_netType'];
        $flowParams['version'] = $config['flow_version'];
        //请求接口
        $res = $this->_client->post('/flowCheck.do', ['form_params' => $flowParams]);
        $res = self::xmlToArray($res->getBody());
        return $res;
    }
    /**
     * 话费的验签
     * @param $data | array
     * @return bool|string
     */
    static function makeFeeMd5Str($data){
        if(empty($data)){
            return false;
        }
        if(!empty($data['userid']) && !empty($data['userpws']) && !empty($data['cardid']) && !empty($data['cardnum']) &&
            !empty($data['sporder_id']) && !empty($data['sporder_time']) && !empty($data['game_userid'])){

            $str = $data['userid'].$data['userpws'].$data['cardid'].$data['cardnum'].$data['sporder_id'].$data['sporder_time'].$data['game_userid'].env("OFPAY_KEYSTR");
            return strtoupper(md5($str));
        }
        return false;
    }
    /**
     * 流量的验签
     * @param $data | array
     * @return bool|string
     */
    static function makeFlowMd5Str($data){
        if(empty($data)){
            return false;
        }
        if(!empty($data['userid']) && !empty($data['userpws']) && !empty($data['phoneno']) && !empty($data['perValue']) &&
            !empty($data['flowValue']) &&!empty($data['range']) && !empty($data['effectStartTime']) && !empty($data['effectTime'])  && !empty($data['sporderId'])){

            $str = $data['userid'].$data['userpws'].$data['phoneno'].$data['perValue'].$data['flowValue'].$data['range'].$data['effectStartTime'].$data['effectTime'];
            if(empty($data['netType'])){
                unset($data['netType']);
                $str .= $data['sporderId'].env("OFPAY_KEYSTR");
                return strtoupper(md5($str));
            }
            $str .= $data['netType'].$data['sporderId'].env("OFPAY_KEYSTR");
            return strtoupper(md5($str));
        }
        return false;
    }

    /**
     * @param $userId 用户id
     * @param $phone 手机号
     * @param $name 面值 10M或者50
     * @param $perValue 殴飞价格
     * @param $wlValue 网利配置价格
     * @param $type 类型 1充话费 2充流量
     * @param $operator_type 运营商类型1移动2联通3电信
     * @param int $is_repair 补单类型，0不是补单，1是补单
     * @param int $old_order_id 原补单id
     * @return array
     */
    function CreateOrders($userId,$phone,$name,$perValue,$wlValue,$type,$operator_type,$is_repair=0,$old_order_id = NULL){
        //唯一订单id(殴飞)
        $uuid = FeeAndFlowBasic::create_guid();
        //生成订单
        $id = LifePrivilege::insertGetId([
            'user_id' => $userId,
            'phone' => $phone,
            'order_id' => $uuid,
            'amount' => $wlValue,
            'amount_of' => $perValue,
            'name' => $name,
            'type' => $type,
            'repair_id' => $old_order_id,
            'operator_type' => $operator_type,
            'created_at'=>date("Y-m-d H:i:s"),
            'updated_at'=>date("Y-m-d H:i:s")
        ]);
        //获取订单信息
        $orderData = LifePrivilege::where('id',$id)->lockForUpdate()->first();

        //先扣款
        if($type == 1){
            //充话费扣款type
            $reduceTypeName = 'call_cost_refill';
        }elseif($type == 2){
            $reduceTypeName = 'networks_flow_refill';
        }else{
            return ['code' => -2 , 'message' => '扣款失败'];
        }
        if($is_repair == 1) {
            //补单情况--无需扣款
            $debitStatus = 1;
            $debitRes = ['补单情况该单已扣款'];
        }elseif($is_repair == 0){
            //正常情况--扣款
            $config = Config::get("feeandflow");
            $record_id = $config['activity_id']+$orderData->id;
            $uuidActivity = SendAward::create_guid();
            $debitRes = Func::decrementAvailable($userId,$record_id,$uuidActivity,$wlValue,$reduceTypeName);
            $debitStatus = 0;
            if(isset($debitRes['result'])){
                //扣款成功状态
                $debitStatus = 1;
            }else{
                //修改订单状态为未扣款且失败
                LifePrivilege::where('id',$orderData->id)->update([
                    'debit_status' => $debitStatus,
                    'remark' => json_encode($debitRes)
                ]);
                return ['code' => -2 , 'message' => '扣款失败'];
            }
        }

        if($type == 1) {
            //充话费
            $res = $this->FeeSend($phone,$name,$uuid);
        }elseif($type == 2) {
            //充流量
            $res = $this->FlowSend($phone, $perValue, $name, $uuid);
        }
        //充值成功
        if($res['retcode'] == 1){
            $orderStatus = 0;
            if(isset($res['game_state']) && $res['game_state'] == 0){
                //正在充值
                $orderStatus = 1;
            }
            if(isset($res['game_state']) && $res['game_state'] == 9){
                //失败
                $orderStatus = 2;
            }
            if(isset($res['game_state']) && $res['game_state'] == 1){
                //成功订单状态
                $orderStatus = 3;
            }
            //修改订单状态
            LifePrivilege::where('id',$orderData->id)->update([
                'debit_status' => $debitStatus,
                'order_status' => $orderStatus,
                'remark' => json_encode($debitRes),
                'remark_of' => json_encode($res)
            ]);
            if($orderStatus == 2){
                //如果失败
                return ['code' => -1 , 'message' => '订单失败'];
            }
            return ['code' => 0 , 'message' => '订单成功'];
        }
        //修改订单状态为充流量失败
        LifePrivilege::where('id',$orderData->id)->update([
            'order_status' => 2,
            'remark_of' => json_encode($res)
        ]);
        return ['code' => -1 , 'message' => '订单失败'];
    }
    /**
     * 生成guid
     * @return string
     */
    static function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = ''; // "&"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * xml转换为数组
     * @param $xml
     * @return mixed
     */
    static function xmlToArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring),true);
        return $val;
    }
    /**
     * 获取运营商拼音和类型
     * @param $xml
     * @return mixed
     */
    static function getOperator($str,$type){
        $res = ['fee_list'=>[],'flow_list'=>[]];
        $config = Config::get("feeandflow");
        $name = '';
        $opType = '';
        if($str == '移动'){
            $name = 'yidong';
            $opType = 1;
        }
        if($str == '联通'){
            $name = 'liantong';
            $opType = 2;
        }
        if($str == '电信'){
            $name = 'dianxian';
            $opType = 3;
        }
        if($name == '' || $opType == ''){
            return $res;
        }
        //获取话费列表
        if($type == 1){
            //殴飞的配置列表
            $ofFeeList = $config['fee'][$name];
            //获取后台配置话费列表
            $configFeeList = LifePrivilegeConfig::where(['type'=>1,'status'=>1,'operator_type'=>$opType])->orderBy('name','asc')->get()->toArray();
            $displayList = [];
            foreach($configFeeList as $key => $item){
                if(isset($item['name'])){
                    if(isset($ofFeeList[$item['name']])){
                        $displayList[] = $item;
                    }
                }
            }
            $res['fee_list'] = $displayList;
        }
        //获取流量列表
        if($type == 2){
            //殴飞的配置列表
            $ofFlowList = $config['flow'][$name];
            //获取后台配置流量列表
            $configFlowList = LifePrivilegeConfig::where(['type'=>2,'status'=>1,'operator_type'=>$opType])->orderBy('name','asc')->get()->toArray();
            $displayList = [];
            foreach($configFlowList as $key => $item){
                if(isset($item['name'])){
                    if(isset($ofFlowList[$item['name']])){
                        $displayList[] = $item;
                    }
                }
            }
            $res['flow_list'] = $displayList;
        }
        return $res;
    }

    /**
     * 根据商品名和充值和冲流量和运营商类型返回殴飞价格和后台配置价格
     * @param $id后台配置的商品id
     * @param $type 1是话费2是流量
     */
    static function getValues($id,$type){
        $res = ['perValue'=>0,'configValue'=>0,'name'=>'','operatorType'=>0];
        if($type == 1){
            $typeName = 'fee';
        }
        if($type == 2){
            $typeName = 'flow';
        }
        if(empty($typeName)){
            return $res;
        }
        $configInfo = LifePrivilegeConfig::where(['id'=>$id,'status'=>1])->first();
        $res['operatorType'] = isset($configInfo->operator_type) ? $configInfo->operator_type : 0;
        //根据商品名获取殴飞面值
        $config = Config::get("feeandflow.".$typeName);
        if($res['operatorType'] == 1){
            $alias_name = 'yidong';
        }elseif($res['operatorType'] == 2){
            $alias_name = 'liantong';
        }elseif($res['operatorType'] == 3){
            $alias_name = 'dianxin';
        }
        $configInfo = LifePrivilegeConfig::where(['id'=>$id,'status'=>1])->first();
        if(!isset($configInfo->id) || !isset($config[$alias_name][$configInfo->name])){
            return $res;
        }
        $res['perValue'] = $config[$alias_name][$configInfo->name];
        //获取配置的价格
        $res['configValue'] = isset($configInfo->price) && $configInfo->price > 0 ? $configInfo->price : 0;
        $res['name'] = $configInfo->name;
        return $res;
    }
}