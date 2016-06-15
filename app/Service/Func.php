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
}