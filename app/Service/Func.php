<?php
namespace App\Service;

use Illuminate\Http\Request;
use Lib\JsonRpcClient;
use Cache;
use Illuminate\Support\Facades\DB;

class Func
{
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
        $str = ''; 
        for ($i = 0; $i < $length; $i++) {
            $str .= $strArr[rand(0, strlen($strArr)-1)];
        }
        return $str;
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
}