<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Traits\BasicDatatables;
use App\Http\Requests;
use App\Models\TmpWechatUser;
use Validator;

class JianmianhuiController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'openid', 'nick_name', 'headimgurl', 'iswin', 'isdefault', 'created_at', 'updated_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:tmp_wecaht_users,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:tmp_wecaht_users,id',
    ];

    function __construct() {
        $this->model = new TmpWechatUser;
    }
}
