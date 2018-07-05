<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Traits\BasicDatatables;
use App\Http\Requests;
use App\Models\HdWorldCupConfig;
use Validator;

class WorldCupConfigController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'team', 'number'];
    protected $deleteValidates = [
        'id' => 'required|exists:hd_world_cup_config,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:hd_world_cup_config,id',
    ];

    function __construct() {
        $this->model = new HdWorldCupConfig;
    }
}
