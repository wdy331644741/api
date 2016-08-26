<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Service\Func;
use App\Models\LoanBook;

class LoanBookController extends Controller
{
    public function getLoanList(Request $request){
        $data = Func::Search($request,new LoanBook());
        return $this->outputJson(0,$data);
    }
}
