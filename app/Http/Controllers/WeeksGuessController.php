<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use App\Jobs\WeeksGuessJob;
use App\Models\HdWeeksGuessConfig;
use Illuminate\Http\Request;
use Validator;
use Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
class WeeksGuessController extends Controller
{
    use DispatchesJobs;
    /**
     * 逢百活动配置列表
     */
    function getList(){
        $data = HdWeeksGuessConfig::orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }
    /**
     * 逢百活动添加
     * @param Request $request
     */
    public function postOperation(Request $request){
        $id = isset($request->id) ? $request->id : 0;
        $filter = [
            'period' => 'required',
            'special' => 'required|min:1|max:255',
            'home_team' => 'required|min:1|max:255',
            'guest_team' => 'required|min:1|max:255',
            'recent' => 'min:1|max:255',
            'home_img' => 'required|min:1|max:255',
            'guest_img' => 'required|min:1|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'race_time' => 'required|min:1|max:255',
            'money' => 'required|numeric',
            'activity_rule' => 'required|min:1',
        ];
        //验证必填项
        if ($id > 0) {
            $filter = [
                'guest_score' => 'required|numeric',
                'home_score' => 'required|numeric',
                'result' => 'required|numeric',
                ];
        }
        $validator = Validator::make($request->all(), $filter);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //添加到关系表
        $data = array();
        $data['period'] = trim($request->period);
        $data['special'] = trim($request->special);
        $data['home_team'] = trim($request->home_team);
        $data['guest_team'] = trim($request->guest_team);
        $data['recent'] = trim($request->recent);
        $data['home_img'] = trim($request->home_img);
        $data['guest_img'] = trim($request->guest_img);
        $data['race_time'] = trim($request->race_time);
        $data['start_time'] = trim($request->start_time);
        $data['end_time'] = trim($request->end_time);
        $data['money'] = trim($request->money);
        $data['activity_rule'] = trim($request->activity_rule);
//        $data['updated_at'] = date("Y-m-d H:i:s");
        //添加
        if($id <= 0){
//            $data['created_at'] = date("Y-m-d H:i:s");
            $data['draw_status'] = 0;
            $data['status'] = 0;
            if (HdWeeksGuessConfig::create($data)) {
                return $this->outputJson(0,array('error_msg'=>'添加成功'));
            }
        }else{
            $data['guest_score'] = trim($request->guest_score);
            $data['home_score'] = trim($request->home_score);
            $data['result'] = trim($request->result);
            HdWeeksGuessConfig::where('id',$id)->update($data);
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }
    }
    /**
     * 逢百活动配置列表
     */
    function postUpStatus(Request $request){
        $id = isset($request->id) ? $request->id : 0;
        $status = isset($request->status) ? $request->status : 0;
        $draw_status = isset($request->draw_status) ? $request->draw_status : 0;
        $data = HdWeeksGuessConfig::where("id",$id)->first();
        if (!$data) {
            return $this->outputJson(-1,["error_msg"=>"数据有误"]);
        }
        if($status == 1){//上线功能逻辑
            //查询当前有没有上线的活动
            $thisData = HdWeeksGuessConfig::where("status",1)->first();
            if($thisData){
                return $this->outputJson(-1,["error_msg"=>"上线状态只能为一个！"]);
            }
            $data->status = 1;
        }else if($draw_status == 1) {//开奖
            if($data->status == 1){
                $result = intval($data->result);
                //未开奖状态才能开奖, 比赛结果不能为0
                if ($data->draw_status == 0 && $result != 0) {
                    $data->draw_status = 1;
                    $this->dispatch(new WeeksGuessJob($id, $data->money, $result));
                } else {
                    return $this->outputJson(-1,["error_msg"=>"请设置比赛结果或已开奖"]);
                }
                //发站内信通知
            }else {
                return $this->outputJson(-1,["error_msg"=>"下线状态不能开奖"]);
            }
        }else{
            $data->status = 0;
        }
        $data->update();
        return $this->outputJson(0,$data);
    }

    function postDrawStatus(Request $request){
        $id = isset($request->id) ? $request->id : 0;
        $status = isset($request->draw_status) ? $request->draw_status : 0;
        $data = HdWeeksGuessConfig::where("id",$id)->first();
        if($status == 1){
            $data->draw_status = 1;
        }else{
            $data->draw_status = 0;
        }
        $data->update();
        return $this->outputJson(0,$data);
    }
}
