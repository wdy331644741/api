<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Activity;
use Validator;

class ActivityController extends Controller
{
    //

    public function getAdd(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(10000,$validator->errors());
        }
    }

    public function getIndex() {
       $data = Activity::all();
       return $this->outputJson(0,$data);
    }
}
