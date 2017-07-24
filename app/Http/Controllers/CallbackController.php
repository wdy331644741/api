<?php

namespace App\Http\Controllers;

use App\Models\LifePrivilege;
use Illuminate\Http\Request;
use App\Http\Requests;

class CallbackController extends Controller
{
    public function getFeeAndFlowCallback(Request $request){
        $orderId = isset($request->order_id) && !empty($request->order_id) ? $request->order_id : '';
        $count = LifePrivilege::where('order_id',$orderId)->count();
        if($count == 1){
            LifePrivilege::where('order_id',$orderId)->update(['order_status' => 3]);
            return 1;
        }
        return 0;
    }
}
