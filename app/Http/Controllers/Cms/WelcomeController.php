<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Cms\Welcome;
use App\Http\Traits\BasicDatatables;

class WelcomeController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','content','enable','updated_at','created_at',];
    protected $deleteValidates = [
        'id' => 'required|exists:cms_welcomes,id'
    ];
    protected $addValidates = [
        'content' => 'required'
    ];
    protected $updateValidates = [
        'id' => 'required|exists:cms_welcomes,id'
    ];

    function __construct() {
        $this->model = new Welcome();
    }

    //上线欢迎语
    public function postOnline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_welcomes,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Welcome::find($request->id)->update(['enable'=>1]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //下线欢迎语
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cms_welcomes,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Welcome::find($request->id)->update(['enable'=>0]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
