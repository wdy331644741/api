<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Http\Traits\BasicDatatables;
use App\Service\Func;
use App\Models\Bbs\Tasks;
use App\Models\Bbs\GroupTask;
use Config;

class TaskController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'name','task_mark','task_type', 'number', 'trigger_type','award_type','award','frequency','created_at'];
    protected $deleteValidates = [
        'id'=>'required|exists:bbs_tasks,id',
    ];
    protected $infoValidates = [
        'id'=>'required|exists:bbs_tasks,id',
    ];
    protected $addValidates = ['name','task_mark','number','trigger_type','award_type','award','frequency','exp_day'];
    protected $updateValidates = [];

    function __construct() {
        $this->model = new Tasks();
    }

    //获取触发类型
    public function getTriggerType()
    {
        return $this->outputJson(0,Config::get('bbstask.trigger_type'));
    }

    //发布活动
    public function postOnline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:bbs_group_tasks,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Tasks::where('id',$request->id)->update(['enable'=>1]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //下线活动
    public function postOffline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'alpha_num|exists:bbs_group_tasks,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Tasks::where('id',$request->id)->update(['enable'=>0]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    //添加子任务
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'group_id' => 'required|exists:bbs_group_tasks,id',
            'number' => 'required|integer',
            'task_mark' => 'required',
            'trigger_type' => 'required',
            'award_type' => 'required',
            'award' => 'required|integer',
            'frequency' => 'required|in:0,1,2',
            'exp_day' => 'required|integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $task = new Tasks();
        $task->name = $request->name;
        $task->group_id = $request->group_id;
        $task->number = $request->number;
        $task->task_mark = $request->task_mark;
        $task->trigger_type = $request->trigger_type;
        $task->award_type = $request->award_type;
        $task->award = $request->award;
        $task->frequency = $request->frequency;
        $task->exp_day = $request->exp_day;
        $task->remark = $request->task_mark.'_'.$request->number;
        $res = $task->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$task->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改子任务
    public function postPut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:bbs_tasks,id',
            'name' => 'required',
            'group_id' => 'required|exists:bbs_group_tasks,id',
            'number' => 'required|integer',
            'task_mark' => 'required',
            'trigger_type' => 'required',
            'award_type' => 'required',
            'award' => 'required|integer',
            'frequency' => 'required|in:0,1,2',
            'exp_day' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->outputJson(10001, array('error_msg' => $validator->errors()->first()));
        }
        $putData = [
            'name' => $request->name,
            'group_id' => $request->group_id,
            'number' => $request->number,
            'task_mark' => $request->task_mark,
            'trigger_type' => $request->trigger_type,
            'award_type' => $request->award_type,
            'award' => $request->award,
            'frequency' => $request->frequency,
            'exp_day' => $request->exp_day,
            'remark' => $request->task_mark . '_' . $request->number
        ];
        $res = Tasks::where('id', $request->id)->update($putData);
        if ($res) {
            return $this->outputJson(0);
        } else {
            return $this->outputJson(10002, array('error_msg' => 'Database Error'));
        }
    }

    //任务列表
    public function getGroupList(Request $request){
        $data = array();
        $order_str = '';
        $pagenum = 20;
        $url = $request->fullUrl();

        if(isset($request->data['pagenum'])){
            $pagenum = $request->data['pagenum'];
        }
        if(isset($request->data['order'])){
            foreach($request->data['order'] as $key=>$val){
                $order_str = "$key $val";
            }
        }else{
            $order_str = "id desc";
        }
        if(isset($request->data['like']) && isset($request->data['filter'])){
            foreach ($request->data['like'] as $key=>$val){
                $data = GroupTask::where($request->data['filter'])
                    ->where($key,'LIKE',"%$val%")
                    ->with('tasks')
                    ->orderByRaw($order_str)
                    ->paginate($pagenum)
                    ->setPath($url);
            }
        }elseif (isset($request->data['like']) && !isset($request->data['filter'])){
            $data = GroupTask::where($key,'LIKE',"%$val%")
                ->with('tasks')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }elseif (isset($request->data['filter']) && !isset($request->data['like'])){
            $data = GroupTask::where($request->data['filter'])
                ->with('tasks')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }else{
            $data = GroupTask::with('tasks')
                ->orderByRaw($order_str)
                ->paginate($pagenum)
                ->setPath($url);
        }

        return $this->outputJson(0,$data);
    }

    //添加组任务
    public function postGroupAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'type_id'=>'integer|required',
            'tip'=>'required|string',
            'alias_name'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $group = new GroupTask();
        $group->name = $request->name;
        $group->type_id = $request->type_id;
        $group->tip = $request->tip;
        $group->alias_name = $request->alias_name;
        $res = $group->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$group->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //组任务详情
    public function getGroupInfo($group_id){
        if(!$group_id){
            return $this->outputJson(10001,array('error_msg'=>"Parames Error"));
        }
        $res = GroupTask::where('id',$group_id)->findOrFail($group_id);
        if(!$res){
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
        return $this->outPutJson(0,$res);
    }


    //任务详情
    public function getDetail($task_id){
        if(!$task_id){
            return $this->outputJson(10001,array('error_msg'=>"Parames Error"));
        }
        $res = Tasks::where('id',$task_id)->with('groups')->findOrFail($task_id);
        if(!$res){
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
        return $this->outPutJson(0,$res);
    }

    //组任务修改
    public function postGroupPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:bbs_group_tasks,id',
            'name' => 'required',
            'tip'=>'required',
            'alias_name'=>'required'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = GroupTask::where('id',$request->id)->update([
            'name'=>$request->name,
            'alias_name'=>$request->alias_name,
            'tip'=>$request->tip,
        ]);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //删除主任务
    public function postGroupDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:bbs_group_tasks,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = GroupTask::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }


}
