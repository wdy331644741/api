<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Traits\BasicDatatables;
use App\Http\Requests;
use App\Models\UserAttribute;
use Validator;

class UserAttrController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'user_id', 'key', 'number', 'string', 'text', 'created_at', 'updated_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:user_attributes,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:user_attributes,id',
    ];

    function __construct() {
        $this->model = new UserAttribute;
    }
}
