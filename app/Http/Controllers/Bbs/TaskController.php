<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Http\Traits\BasicDatatables;
use App\Models\Bbs\Tasks;

class TaskController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','task_type', 'name', 'number', 'trigger_type','remark','task_type','description','award_type','award','frequency','created_at'];
    protected $deleteValidates = [
        'id'=>'required|exists:bbs_tasks,id',
    ];
    protected $addValidates = ['task_type','name','number','trigger_type','task_type','award_type','award','frequency'];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Tasks();
    }

    public function getIndex(Request $request)
    {
        return "ok";
    }
}
