<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Http\Traits\BasicDatatables;
use App\Models\Bbs\Tasks;
use Config;

class TaskController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'name', 'task_type', 'number', 'trigger_type','award_type','award','frequency','created_at'];
    protected $deleteValidates = [
        'id'=>'required|exists:bbs_tasks,id',
    ];
    protected $addValidates = ['task_type','name','number','trigger_type','task_mark','award_type','award','frequency'];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Tasks();
    }

    public function getTriggerType()
    {
        return $this->outputJson(0,Config::get('bbstask.trigger_type'));
    }
}
