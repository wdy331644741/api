<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Thread;

class ThreadController extends Controller
{
    public function getList(Request $request){
        Thread::select('id','verify_time','user_id','type_id');
    }
}
