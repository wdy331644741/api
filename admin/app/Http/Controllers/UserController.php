<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\User;

class UserController extends Controller
{
    //列表
    function getIndex() {
        $res = User::all()->get();
        $this->output_json('ok', $res);
    }

    //添加
    function postAdd(Request $request){
        
    }
}
