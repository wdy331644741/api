<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\GlobalConfig;
use App\Http\Traits\BasicDatatables;

class GlobalController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','remark', 'key','val', ];
    protected $deleteValidates = [];
    protected $addValidates = [
        'key' => 'required|alpha_dash',
        'val' => 'required'
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_global_configs,id'
    ];

    function __construct() {
        $this->model = new GlobalConfig();
    }
}
