<?php
namespace App\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class Func
{
    public static function GroupSearch(Request $request,$model_name){
        $data = array();
        $order_str = '';
        $pagenum = 20;
        $url = $request->fullUrl();
        if(isset($request->data['filter'])){
            $filter = $request->data['filter'];
        }else{
            $filter = array('type_id',0);
        }
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
        if(isset($request->data['like'])){
            foreach ($request->data['like'] as $key=>$val){
                //$like_str = "$key LIKE %$val%";
                $data = $model_name::where($filter)
                    ->where($key,'LIKE',"%$val%")
                    ->with('activities')
                    ->with('activities.rules','activities.awards')
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }
        }else{
            $data = $model_name::where($filter)
                ->with('activities')
                ->with('activities.rules','activities.awards')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }

        return $data;
    }
}