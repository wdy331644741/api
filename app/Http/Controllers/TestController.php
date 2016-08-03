<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Service\RuleCheck;
use Log;

class TestController extends Controller
{
    public function getIndex(){
        Log::write('error','Send Msg Fails');
    }

}
