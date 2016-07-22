<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Service\RuleCheck;

class TestController extends Controller
{
    public function getIndex(){
        $res = RuleCheck::register(12,5000032);
        dd($res);
    }
}
