<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Thread;

class ThreadController extends Controller
{
    //帖子为审核列表
    public function getList(){
        $res = Thread::where('isverify',0)->with('users','sections')->get()->toArray();
        return $this->outputJson(0,$res);
    }

    //
}
