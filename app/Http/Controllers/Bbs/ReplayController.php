<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\ReplyConfig;
use App\Http\Traits\BasicDatatables;

class ReplayController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','name','description','created_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:bbs_replay_configs,id'
    ];
    protected $addValidates = [
        'name' => 'required'
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_replay_configs,id'
    ];

    function __construct() {
        $this->model = new ReplyConfig();
    }
}
