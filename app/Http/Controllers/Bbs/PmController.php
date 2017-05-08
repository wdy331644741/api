<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Pm;
use App\Http\Traits\BasicDatatables;

class PmController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id', 'from_user_id','tid', 'cid', 'content', 'created_at', 'isread'];
    protected $deleteValidates = [];
    protected $addValidates = [
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_pms,id'
    ];

    function __construct() {
        $this->model = new Pm();
    }
}
