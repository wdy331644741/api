<?php

namespace App\Http\Controllers\Bbs;

use App\Models\Bbs\Comment;
use App\Models\Bbs\ReplyConfig;
use App\Models\Bbs\Tasks;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Service\Func;
use App\Http\Controllers\Controller;
use App\Models\Bbs\Thread;
use App\Http\Traits\BasicDatatables;
use App\Models\Bbs\Pm;
use Validator;
use App\Models\Bbs\Task;
use App\Service\BbsSendAwardService;
use App\Service\SendAward;

class ThreadController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','user_id','title','content', 'type_id','video_code','created_at', 'istop', 'isgreat', 'ishot', 'isverify', 'comment_num','zan_num','collection_num'];
    protected $deleteValidates = [
        'id' => 'required|exists:bbs_threads,id'
    ];
    protected $addValidates = [
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_threads,id'
    ];

    function __construct() {
        $this->model = new Thread();
    }

    //帖子为审核列表
    public function getList($sid,$isverify=0){
        if(!in_array($isverify,[0,1,2])){
            $res = Thread::where('type_id',$sid)->onlyTrashed()->with('user','section')->orderBy('id','desc')->paginate(20)->toArray();
            return $this->outputJson(0,$res);
        }
        $res = Thread::where(['type_id'=>$sid,'isverify'=>$isverify])->with('user','section')->orderBy('id','desc')->paginate(20)->toArray();
        return $this->outputJson(0,$res);
    }

    //帖子搜索
    public function getSearch(Request $request){
        $res = Func::Search($request,new Thread());
        return $this->outputJson(0,$res);
    }

    //还原帖子
    public function postRestore(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Thread::where('id',$request->id)->restore();
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //内部发帖
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'=>'required|exists:bbs_users,user_id',
            'type_id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $thread = new Thread();
        $thread->user_id = $request->user_id;
        $thread->type_id = $request->type_id;
        $thread->cover = isset($request->cover) ? $request->cover : NULL;
        $thread->title = isset($request->title) ? $request->title : NULL;
        $thread->content = isset($request->content) ? $request->content : NULL;
        $thread->isinside = 1;
        $thread->istop = $request->istop ? $request->istop : 0;
        $thread->isverify = 1;
        $thread->verify_time = date('Y-m-d H:i:s');
        $thread->save();
        if($thread->id){
            return $this->outputJson(0,array('insert_id'=>$thread->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //编辑帖子
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'user_id'=>'exists:bbs_users,user_id',
            'type_id'=>'exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [
            'istop'=>$request->istop ? $request->istop : 0
        ];
        if(isset($request->user_id)){
            $putData['user_id'] = $request->user_id;
        }
        if(isset($request->type_id)){
            $putData['type_id'] = $request->type_id;
        }
        if(isset($request->cover)){
            $putData['cover'] = $request->cover;
        }
        if(isset($request->title)){
            $putData['title'] = $request->title;
        }
        if(isset($request->content)) {
            $putData['content'] = $request->content;
        }
        $res = Thread::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //加精，置顶，最热
    public function postToogleStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [];
        if(isset($request->istop)){
            $putData['istop'] = $request->istop;
        }
        if(isset($request->isgreat)){
            $putData['isgreat'] = $request->isgreat;
        }
        if(isset($request->ishot)){
            $putData['ishot'] = $request->ishot;
        }
        $res = Thread::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //审核状态修改
    public function postVerifyPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'isverify'=>'required|in:0,1,2'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        switch ($request->isverify){
            case  1:
                return $this->_checkSuccess($request->id);
                break;
            case  2:
                return $this->_checkFail($request->id);
                break;
        }
    }

    //拒绝审核
    private function _checkFail($id){
        if(empty($id)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $thread = Thread::find($id);
        if(in_array($thread->isverify,[2])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        $res = Thread::where('id',$id)->update(['isverify'=>2,'verify_time'=>date('Y-m-d H:i:s')]);
        if($thread != null){
            $pm = new Pm();
            $pm->user_id = $thread->user_id;
            $pm->from_user_id = 0;
            $pm->tid = $id;
            $pm->msg_type = 1;
            $pm->type = 2;
            $pm->content = '您的贴子未能通过审核';
            $pm->save();
        }
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //审核通过
    private function _checkSuccess($id){
        if(empty($id)){
            return $this->outputJson(10001,array('error_msg'=>'Parames Error'));
        }
        $thread = Thread::find($id);
        if(in_array($thread->isverify,[1])){
            return $this->outputJson(10010,array('error_msg'=>'Repeat Actions'));
        }
        $res = Thread::where('id',$id)->update(['isverify'=>1,'verify_time'=>date('Y-m-d H:i:s')]);
        if($res){
            $postTime = date('Y-m-d',strtotime($thread->created_at));
            $taskHistry = Task::where('id',$thread->user_id)->whereRaw('DATE_FORMAT(award_time, "%Y-%m-%d") = '."'".$postTime."'")->count();
            if(!$taskHistry){
                $tasks = Tasks::where("task_mark","dayPublishThread")->get()->toArray();
                foreach ($tasks as $value) {
                    $postNum = Thread::where(["user_id"=>$thread->user_id,"isverify"=>1])->whereRaw('DATE_FORMAT(created_at, "%Y-%m-%d") = '."'".$postTime."'")->count();
                    //审核发奖条件
                    if($postNum >= $value['number']){
                        //发奖
                        $this->organizeDataAndSend($value,$thread->user_id,$thread->created_at);
                    }
                }
            }
            $bbsSendAward = new BbsSendAwardService($thread->user_id);
            $bbsSendAward->publishThreadAward(2);
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //后台回复帖子
    public function postAdminReply(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_threads,id',
            'content'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $res = Thread::where('id',$request->id)->first();
        if(in_array($res->isverify,[0,2])){
            return $this->outputJson(10012,array('error_msg'=>'Error Operation'));
        }
        $verify_time = date('Y-m-d H:i:s');
        $comment = new Comment();
        $comment->user_id = 0;
        $comment->tid = $request->id;
        $comment->content = $request->content;
        $comment->isverify = 1;
        $comment->verify_time = $verify_time;
        $comment->save();
        if($comment->id){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }

    }

    //批量审帖
    public function postBatchPass(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        foreach ($request->id as $val){
            $thread = Thread::find($val);
            if(in_array($thread->isverify,[1])){
                $error[$val] = 10010;
                continue;
            }

            if($thread != null){
                $user_id = $thread->user_id;
                $pm = new Pm();
                $pm->user_id = $user_id;
                $pm->from_user_id = 0;
                $pm->tid = $val;
                $pm->msg_type = 1;
                $pm->type = 3;
                $pm->save();
            }
            $putData = [
                'isverify'=>1,
                'verify_time'=>date('Y-m-d H:i:s')
            ];
            $res = Thread::find($val)->update($putData);
            if(!$res){
                $error[$val] = 10002;
                continue;
            }
        }
        if(empty($error)){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10011,array('error_msg'=>'Error Array','error_arr'=>$error));
        }
    }

    //批量拒绝帖子
    public function postBatchFail(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        foreach ($request->id as $val){
            $thread = Thread::find($val);
            if(in_array($thread->isverify,[2])){
                $error[$val] = 10010;
                continue;
            }
            $user_id = null;
            if($thread != null){
                $user_id = $thread->user_id;
                $pm = new Pm();
                $pm->user_id = $user_id;
                $pm->from_user_id = 0;
                $pm->tid = $val;
                $pm->msg_type = 1;
                $pm->type = 2;
                $pm->save();
            }
            $putData = [
                'isverify'=>2,
                'verify_time'=>date('Y-m-d H:i:s')
            ];
            $res = Thread::find($val)->update($putData);
            if(!$res){
                $error[$val] = 10002;
                continue;
            }
        }
        if(empty($error)){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10011,array('error_msg'=>'Error Array','error_arr'=>$error));
        }
    }

    /*
     *帖子发奖
     */
    private function organizeDataAndSend($params,$awardUserId,$award_time=null){
        $awards['id'] = 0;
        $awards['user_id'] = $awardUserId;
        $awards['source_id'] = $params['id'];
        $awards['name'] = $params['award'].'元体验金';
        $awards['source_name'] = $params['name'];
        $awards['experience_amount_money'] = $params['award'];
        $awards['effective_time_type'] = 1;
        $awards['effective_time_day'] = $params['exp_day'];
        $awards['platform_type'] = 0;
        $awards['limit_desc'] = '';
        $awards['trigger'] = "";
        $awards['mail'] = "恭喜您在'{{sourcename}}'活动中获得了'{{awardname}}'奖励。";
        SendAward::experience($awards);
        //记录发奖数据
        $task = new Task();
        $task->user_id = $awardUserId;
        $task->task_type = $params['remark'];
        $task->award = $params['award'];
        $task->award_time = $award_time ? $award_time : date('Y-m-d H:i:s');
        $task->task_group_id = $params['group_id'];
        $task->save();

    }
}