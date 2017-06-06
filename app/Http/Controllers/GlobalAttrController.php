<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Traits\BasicDatatables;
use App\Http\Requests;
use App\Models\GlobalAttribute;
use Validator;

class GlobalAttrController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'key', 'number', 'string', 'text', 'created_at', 'updated_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:global_attributes,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:global_attributes,id',
    ];

    function __construct() {
        $this->model = new GlobalAttribute;
    }
}
