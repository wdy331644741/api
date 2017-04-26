<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Traits\BasicDatatables;
use App\Http\Requests;
use App\Models\Admin;
use Validator;

class AdminController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'name', 'mobile', 'privilege_id', 'last_login', 'updated_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:admins,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:admins,id',
    ];

    function __construct() {
        $this->model = new Admin;
    }
}
