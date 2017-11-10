<?php
namespace App\Service;

use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use Cache;
use Illuminate\Support\Facades\DB;
use App\Models\WechatUser;
use App\Models\JsonRpc;
use App\Models\Admin;

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

    //搜索优化
    public static function freeSearch(Request $request,$model_name){
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
            $like_str = self::getFilterData($request->data['like'],'like');
            $filterData = self::getFilterData($request->data['filter']);
            if(isset($filterData['filter_str'])){
                $data = $model_name::where($filterData['filter_data'])
                    ->whereRaw($filterData['filter_str'])
                    ->whereRaw($like_str)
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }else{
                $data = $model_name::where($filterData['filter_data'])
                    ->whereRaw($like_str)
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }

        }elseif (isset($request->data['like']) && !isset($request->data['filter'])){
            $like_str = self::getFilterData($request->data['like'],'like');
            $data = $model_name::whereRaw($like_str)
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }elseif (isset($request->data['filter']) && !isset($request->data['like'])){
            $filterData = self::getFilterData($request->data['filter']);
            if(isset($filterData['filter_str'])){
                $data = $model_name::where($filterData['filter_data'])
                    ->whereRaw($filterData['filter_str'])
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }else{
                $data = $model_name::where($filterData['filter_data'])
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }

        }else{
            $data = $model_name::orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
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
            'like' => 'LIKE'
        ];
        $filterStr = '';
        if($type == 'like'){
            foreach ($filter as $key=>$val){
                $filterStr .= "AND ".$key." LIKE '%".$val."%' ";
                return substr($filterStr,4);
            }
        }
        foreach ($filter as $key=>$val){
            $patternKey = $key.'_pattern';
            if(stripos($key,'_pattern') === false && isset($filter[$patternKey])){
                $pattern = $filter[$patternKey];
                $filterStr .= "AND ".$key." ".$patternArr[$pattern]." ".$val." ";
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

    /**
     * 给用户减钱
     */
    static function decrementAvailable($userId, $recordId, $uuid, $amount, $type) {
        $client = new JsonRpcClient(env('INSIDE_HTTP_URL'));
        return $client->decrementAvailable(array(
            "user_id" => $userId,
            "record_id"  => $recordId,
            "uuid" => $uuid,
            "amount" => $amount,
            "type" => $type,
            "sign" => hash('sha256', $userId.env('INSIDE_SECRET')),
        ));
    }

    /**
     * 验证交易密码
     */
    static function checkTradePwd($tradePwd) {
        $client = new JsonRpcClient(env('ACCOUNT_HTTP_URL'));
        return $client->checkTradePwd(array(
            "trade_pwd" => $tradePwd,
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
}
