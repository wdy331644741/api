<?php

namespace App\Http\Controllers;

use App\Models\Privilege;
use App\Http\Traits\BasicDatatables;
use Illuminate\Http\Request;
use App\Http\Requests;

class PrivilegeController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','name', 'privilege', 'updated_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:privileges,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Privilege;
    }
}
