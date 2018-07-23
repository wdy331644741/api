<?php
namespace App\Service;

use App\Models\Statistics;
use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use Cache;
use Illuminate\Support\Facades\DB;
use App\Models\WechatUser;
use App\Models\JsonRpc;
use App\Models\Admin;
use App\Models\GlobalAttribute;
use Config;

class Func
{
    public static function checkAdmin() {
        $jsonRpc = new JsonRpc();
        $res = $jsonRpc->account()->profile();

        if(isset($res['error'])){
            return false;
        }

        $response['error_code']  = $res['result']['code'];
        $data = isset($res['result']['data']) ? $res['result']['data'] : [];

        $mobile = $data['phone'];
        $admin = Admin::where('mobile', $mobile)->with('privilege')->first();
        if($admin) {
            return true;
        }
        return false;
    }

    public static function GroupSearch(Request $request,$model_name){
        $data = array();
        $order_str = '';
        $pagenum = 20;
        $url = $request->fullUrl();

        if(isset($request->data['pagenum'])){
            $pagenum = $request->data['pagenum'];
        }
        if(isset($request->data['order'])){
            foreach($request->data['order'] as $key=>$val){
                $order_str = "$key $val";
            }
        }else{
            $order_str = "id desc";
        }
        if(isset($request->data['like']) && isset($request->data['filter'])){
            foreach ($request->data['like'] as $key=>$val){
                //$like_str = "$key LIKE %$val%";
                $data = $model_name::where($request->data['filter'])
                    ->where($key,'LIKE',"%$val%")
                    ->with('activities')
                    ->with('activities.rules','activities.awards')
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }
        }elseif (isset($request->data['like']) && !isset($request->data['filter'])){
            $data = $model_name::where($key,'LIKE',"%$val%")
                ->with('activities')
                ->with('activities.rules','activities.awards')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }elseif (isset($request->data['filter']) && !isset($request->data['like'])){
            $data = $model_name::where($request->data['filter'])
                ->with('activities')
                ->with('activities.rules','activities.awards')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }else{
            $data = $model_name::with('activities')
                ->with('activities.rules','activities.awards')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }

        return $data;
    }

    public static function Search(Request $request,$model_name){
        $data = array();
        $order_str = '';
        $pagenum = 20;
        $url = $request->fullUrl();

        if(isset($request->data['pagenum'])){
            $pagenum = $request->data['pagenum'];
        }
        if(isset($request->data['order'])){
            foreach($request->data['order'] as $key=>$val){
                $order_str = "$key $val";
            }
        }else{
            $order_str = "id desc";
        }
        if(isset($request->data['like']) && isset($request->data['filter'])){
            foreach ($request->data['like'] as $key=>$val){
                //$like_str = "$key LIKE %$val%";
                $data = $model_name::where($request->data['filter'])
                    ->where($key,'LIKE',"%$val%")
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }
        }elseif (isset($request->data['like']) && !isset($request->data['filter'])){
            $data = $model_name::where($key,'LIKE',"%$val%")
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }elseif (isset($request->data['filter']) && !isset($request->data['like'])){
            $data = $model_name::where($request->data['filter'])
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }else{
            $data = $model_name::orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }
        return $data;
    }


    //搜索优化   #TODO  待优化
    public static function freeSearch(Request $request,$modelObj,$fileds,$withs=[]){
        $data = array();
        $order_str = '';
        $pagenum = 20;
        $url = $request->fullUrl();
        if(isset($request->data['pagenum'])){
            $pagenum = $request->data['pagenum'];
        }

        $items = $modelObj->select($fileds);

        if(isset($request->data['order'])){
            foreach($request->data['order'] as $key=>$val){
                $order_str = "$key $val";
            }
        }else{
            $order_str = "id desc";
        }
        if(!empty($withs)){
            foreach ($withs as $key => $with){
                $items->with($with);
            }

        }
        if(isset($request->data['like']) && isset($request->data['filter'])){
            $like_str = self::getFilterData($request->data['like'],'like');
            $filterData = self::getFilterData($request->data['filter']);
            if(isset($filterData['filter_str'])){
                $data = $items->where($filterData['filter_data'])
                    ->whereRaw($filterData['filter_str'])
                    ->whereRaw($like_str)
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url)->toArray();
            }else{
                $data = $items->where($filterData['filter_data'])
                    ->whereRaw($like_str)
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url)->toArray();
            }

        }elseif (isset($request->data['like']) && !isset($request->data['filter'])){
            $like_str = self::getFilterData($request->data['like'],'like');
            $data = $items->whereRaw($like_str)
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url)->toArray();
        }elseif (isset($request->data['filter']) && !isset($request->data['like'])){
            $filterData = self::getFilterData($request->data['filter']);
            if(isset($filterData['filter_str'])){
                $data = $items->where($filterData['filter_data'])
                    ->whereRaw($filterData['filter_str'])
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url)->toArray();
            }else{
                $data = $items->where($filterData['filter_data'])
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url)->toArray();
            }

        }else{
            $data = $items->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url)->toArray();
        }
        return $data;
    }

    static function getFilterData($filter,$type=null){
        $data = array();
        $patternArr = [
            'equal' => '=',
            'max_equal' => '>=',
            'min_equal' => '<=',
            'min' => '<',
            'max' => '>',
            'no_equal' => '<>',
            'like' => 'LIKE',
            'min_equal_max'=>'<=>'
        ];
        $filterStr = '';
        if($type == 'like'){
            foreach ($filter as $key=>$val){
                $filterStr .= "AND ".$key." LIKE '%".$val."%' ";
                return substr($filterStr,4);
            }
        }
        if(isset($filter['end_at'])){
            $end_at = $filter['end_at'];
            unset($filter['end_at']);
        }
        foreach ($filter as $key=>$val){
            $patternKey = $key.'_pattern';
            if(stripos($key,'_pattern') === false && isset($filter[$patternKey])){
                if($filter[$patternKey] == "min_equal_max"){
                    $filterStr .= "AND ".$key." >= '".$val."' AND ".$key." <= '".$end_at."' ";

                }else{
                    $pattern = $filter[$patternKey];
                    $filterStr .= "AND ".$key." ".$patternArr[$pattern]." '".$val."' ";
                }
                $data['filter_str'] = substr($filterStr,4);
            } elseif (stripos($key,'_pattern') === false && !isset($filter[$patternKey])){
                $data['filter_data'][$key] = $val;
            }
        }
        return $data;
    }

    /**
     * 根据用户id获取用户基本信息
     * @param $user_id
     * @return mixed
     */
    static function getUserPhone($user_id,$cache = false){
        if(Cache::has('Phone_'.$user_id) && $cache == false){
            return Cache::get('Phone_'.$user_id);
        }
        $url = env('ACCOUNT_HTTP_URL');
        $client = new JsonRpcClient($url);
        $phone = $client->getUserImportantInfo(array($user_id));
        $phone = isset($phone['result'][$user_id]['mobile']) ? $phone['result'][$user_id]['mobile'] : '';
        if(!empty($phone)){
            Cache::put('Phone_'.$user_id,$phone,30);
        }
        return $phone;
    }

    /**
     * 根据手机号取出用户id
     * @param $phone
     * @return int
     */
    static function getUserIdByPhone($phone,$cache = false){
        if(Cache::has('UserID_'.$phone) && $cache == false){
            return Cache::get('UserID_'.$phone);
        }
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $userId = $client->getUserIdByPhone(array('phone' =>$phone));
        $userId = isset($userId['result']['user_id']) ? $userId['result']['user_id'] : 0;
        if(!empty($userId)){
            Cache::put('UserID_'.$phone,$userId,30);
        }
        return $userId;
    }

    static function randomStr($length) {
        $strArr = 'abcdefghigklmnopqrstuvwxyz0123456';
        $dateStr = date("YmdHis");
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $strArr[rand(0, strlen($strArr)-1)];
        }
        return $str."_".$dateStr;
    }

    /**
     * 根据用户id获取用户基本信息
     * @param $user_id
     * @return mixed
     */
    static function getUserBasicInfo($user_id,$cache = false){
        if(Cache::has('UserInfo_'.$user_id) && $cache == false){
            return Cache::get('UserInfo_'.$user_id);
        }
        $url = env('INSIDE_HTTP_URL');
        $client = new JsonRpcClient($url);
        $info = $client->userBasicInfo(array("userId"=>$user_id));
        $info = isset($info['result']['data']) && !empty($info['result']['data']) ? $info['result']['data'] : array();
        if(!empty($info)){
            Cache::put('UserInfo_'.$user_id,$info,30);
        }
        return $info;
    }

    static function globalUserBasicInfo($userId){
        global $userBasicInfo;
        if($userBasicInfo) {
            return $userBasicInfo;
        }
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        $userBasicInfo = $client->userBasicInfo(array('userId'=>$userId));
        return $userBasicInfo;
    }

    /**
     * 根据userId获取微信信息
     * @param $userId
     * @return array
     */
    static function wechatInfoByUserID($userId){
        if(empty($userId)){
            return array();
        }
        $data = WechatUser::where("uid",$userId)->first();
        return $data;
    }

    /**
     * 给用户加钱
     */
    static function incrementAvailable($userId, $recordId, $uuid, $amount, $type) {
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        return $client->incrementAvailable(array(
            "user_id" => $userId,
            "record_id"  => $recordId,
            "uuid" => $uuid,
            "amount" => $amount,
            "type" => $type,
            "sign" => hash('sha256', $userId.env('INSIDE_SECRET')),
        ));
    }

    //生成Guid
    static function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }

    /*
     *  统计浏览量
     * @type 渠道号    string
     * @ip  ip  string
     * @remark string
     * @return $ret  id
     */
    static function statistics($type, $ip, $userId, $remark='')
    {
        $ret = false;
        if(!empty($type) && !empty($ip)){
            $params['type'] = $type;
            $params['ip'] = $ip;
            $params['user_id'] = $userId ? $userId : 0;
            $params['remark'] = $remark;
            $params['created_at'] = $params['updated_at'] = date("Y-m-d H:i:s");
            $ret = Statistics::insertGetId($params);
        }
        return $ret;
    }

    /*
     * 上传图片公有方法
     *
     *  @Request object
     *  @key string
     *  @path string
     *  @ext arrry
     *  @return string
     */
    static function getImageUrl(Request $request,$key="",$path="",$ext=array())
    {
        //图片
        $file = '';
        if (!isset($path)) {
            $path = base_path() . '/storage/images/';
        }
        if (!isset($key)) {
            $key = "img_path";
        }
        if(empty($ext)){
            $ext = array('jpg', 'jpeg', 'png', 'bmp', 'gif');
        }
        if ($request->hasFile($key)) {
            if ($request->file($key)->isValid()) {
                $mimeTye = $request->file($key)->getClientOriginalExtension();
                if (in_array($mimeTye, $ext)) {
                    $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.' . $mimeTye;
                    //保存文件到路径
                    $request->file($key)->move($path, $fileName);
                    $file = $path . $fileName;
                } else {
                    return array("errorcode"=>1001,'errmsg'=>'文件格式错误');
                }
            } else {
                return array('errcode'=>1002,'errmsg'=>'文件错误');
            }
        }
        if (empty($file)) {
            return array('errcode'=>1003,'errmsg'=>'文件不能为空');
        }
        //文件名
        return array('errcode'=>0,'data'=>Config::get('cms.img_http_url').$fileName);
    }

    /*
     * 奖品阈值预警
     *
     */
    static public function earlyWarning($number,$name,$id){
        $res = GlobalAttribute::where("key","earlyWarning_".$id)->whereRaw( " to_days(created_at) = to_days(now())")->first();
        if($res){
            return false;
        }else{
            $arr  = array('15811347310','13466678840');
            $i =0;
            foreach ($arr as $val){
                $params = array();
                $params['phone'] = $val;
                $params['node_name'] = "custom";
                $params['tplParam'] = array();
                $params['customTpl'] = $name."优惠券剩余已经不多了，请及时补充，优惠券名称：".$name.",剩余数量：".$number."张";
                $url = Config::get('cms.message_http_url');
                $client = new JsonRpcClient($url);
                $res = $client->sendSms($params);
                if(isset($res['result']['code']) && $res['result']['code'] === 0){
                    $i++;
                }
            }
            if($i){
                $obj = new GlobalAttribute();
                $obj->key = "earlyWarning_".$id;
                $obj->number = 1;
                $obj->save();
            }
            return true;
        }
    }

    /*
     * 获取统计日活量
     *
     */
    static function getStatSport(){
        $url = 'http://stat.wanglibao.com:10000/aso_user/get_log_list';
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        $untime = time();

        $data = [
            'code'=>hash('sha256',$untime.'TcW80uaAa4soY6d86hjv'),
            'timestamp'=>$untime,
        ];
        if($data != null){
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $info = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno:'.curl_getinfo($curl);//捕抓异常
            dump(curl_getinfo($curl));
        }
        $data = json_decode($info,true);
        if(!empty($data['data'])){
            return $data['data']['active_num'];
        }
        return false;


    }
}
