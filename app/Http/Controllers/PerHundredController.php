<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use App\Models\HdPerbai;
use App\Models\HdPerHundredConfig;
use App\Service\PerBaiService;
use Illuminate\Http\Request;
use App\Jobs\PerHundredJob;
use Validator;
use Response, Cache;
class PerHundredController extends Controller
{
    /**
     * 逢百活动配置列表
     */
    function getList(){
        $data = HdPerHundredConfig::orderBy('id','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }
    /**
     * 逢百活动添加
     * @param Request $request
     */
    public function postOperation(Request $request){
        $id = isset($request->id) ? $request->id : 0;
        $filter = [
            'ultimate_award' => 'required|min:1|max:64',
            'ultimate_img1' => 'required|min:1|max:255',
            'ultimate_img2' => 'required|min:1|max:255',
            'first_award' => 'required|min:1|max:64',
            'first_img1' => 'required|min:1|max:255',
            'first_img2' => 'required|min:1|max:255',
            'sunshine_award' => 'required|min:1|max:64',
            'sunshine_img1' => 'required|min:1|max:255',
            'sunshine_img2' => 'required|min:1|max:255',
            'numbers' => 'required|min:1|max:16',
            'start_time' => 'required|date',
            'ultimate_pc1' => 'required|min:1|max:255',
            'ultimate_pc2' => 'required|min:1|max:255',
            'first_pc1' => 'required|min:1|max:255',
            'first_pc2' => 'required|min:1|max:255',
            'sunshine_pc1' => 'required|min:1|max:255',
            'sunshine_pc2' => 'required|min:1|max:255',
            'award_text' => 'required|min:1|max:255',
            'ultimate_rule' => 'required|min:1|max:2000',
            'first_rule' => 'required|min:1|max:2000',
            'sunshine_rule' => 'required|min:1|max:2000',
            'activity_rule' => 'required|min:1',
            'ultimate_price' => 'required|numeric',
            'first_price' => 'required|numeric',
            'sunshine_price' => 'required|numeric',
            'guess_award' => 'required|numeric',
        ];
        if($id > 0){
            unset($filter['numbers']);
        }
        //验证必填项
        $validator = Validator::make($request->all(), $filter);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //添加到关系表
        $data = array();
        $data['ultimate_award'] = trim($request->ultimate_award);
        $data['ultimate_img1'] = trim($request->ultimate_img1);
        $data['ultimate_img2'] = trim($request->ultimate_img2);
        $data['first_award'] = trim($request->first_award);
        $data['first_img1'] = trim($request->first_img1);
        $data['first_img2'] = trim($request->first_img2);
        $data['sunshine_award'] = trim($request->sunshine_award);
        $data['sunshine_img1'] = trim($request->sunshine_img1);
        $data['sunshine_img2'] = trim($request->sunshine_img2);
        $data['start_time'] = trim($request->start_time);
        $data['updated_at'] = date("Y-m-d H:i:s");

        $data['ultimate_pc1'] = trim($request->ultimate_pc1);
        $data['ultimate_pc2'] = trim($request->ultimate_pc2);
        $data['first_pc1'] = trim($request->first_pc1);
        $data['first_pc2'] = trim($request->first_pc2);
        $data['sunshine_pc1'] = trim($request->sunshine_pc1);
        $data['sunshine_pc2'] = trim($request->sunshine_pc2);

        $data['award_text'] = trim($request->award_text);
        $data['ultimate_rule'] = trim($request->ultimate_rule);
        $data['first_rule'] = trim($request->first_rule);
        $data['sunshine_rule'] = trim($request->sunshine_rule);
        $data['activity_rule'] = trim($request->activity_rule);

        $data['ultimate_price'] = trim($request->ultimate_price);
        $data['first_price'] = trim($request->first_price);
        $data['sunshine_price'] = trim($request->sunshine_price);
        $data['guess_award'] = trim($request->guess_award);
        //添加
        if($id <= 0){
            $data['numbers'] = intval($request->numbers);
            $data['created_at'] = date("Y-m-d H:i:s");
            $data['insert_status'] = 0;
            $data['status'] = 0;
            $insertID = HdPerHundredConfig::insertGetId($data);
            $this->dispatch(new PerHundredJob($insertID,$data['numbers']));
            return $this->outputJson(0,array('error_msg'=>'添加成功'));
        }else{
            HdPerHundredConfig::where('id',$id)->update($data);
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }
    }
    /**
     * 逢百活动配置列表
     */
    function postUpStatus(Request $request){
        $id = isset($request->id) ? $request->id : 0;
        $status = isset($request->status) ? $request->status : 0;
        $data = HdPerHundredConfig::where("id",$id)->first();
        if($status == 1){//上线功能逻辑
            //如果开奖码没生成
            if(isset($data->insert_status) && $data->insert_status != 2){
                return $this->outputJson(-1,["error_msg"=>"开奖码还未生成，不可上线！"]);
            }
            //查询当前有没有上线的活动
            $thisData = HdPerHundredConfig::where("status",1)->first();
            if($thisData){
                return $this->outputJson(-1,["error_msg"=>"上线状态只能为一个！"]);
            }
            $data->status = 1;
        }else{
            $data->status = 0;
        }
        $data->update();
        if ($data->status == 1) {
            PerBaiService::getActivityInfo();
        } else {
            PerBaiService::clearActivityInfo();
        }
        return $this->outputJson(0,$data);
    }

    //为了测试，单独生成抽奖号码
    public function postAddNum(Request $request) {
        $number = intval($request->number);
        $id = intval($request->id);
        if ( $number <= 0 || $id <= 0) {
            return $this->outputJson(-1,["error_msg"=>"参数不正确"]);
        }
        $this->dispatch(new PerHundredJob($id,$number));
        return $this->outputJson(0,["error_msg"=>"成功"]);
    }
}
