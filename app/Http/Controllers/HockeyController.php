<?php

namespace App\Http\Controllers;


use App\Models\HdHockeyCardAward;
use App\Models\HdHockeyGuessConfig;
use App\Service\Hockey;
use Illuminate\Http\Request;
use App\Jobs\HockeyGuessJob;
use Validator;
use Config,DB;

class HockeyController extends Controller
{
    //列表公共接口
    public function getConfigList(Request $request){
        $type = isset($request->type) && $request->type >= 1 ? $request->type : 0;
        if($type == 1){
            //集卡列表
            $data = HdHockeyCardAward::orderBy('id','asc')->paginate(20);
            return $this->outputJson(0,$data);
        }
        if($type == 2){
            //竞猜列表
            $data = HdHockeyGuessConfig::orderBy('match_date','asc')->paginate(20)->toArray();
            foreach($data['data'] as &$item){
                if(isset($item['first']) && !empty($item['first'])){
                    $item = Hockey::formatHockeyGuessData($item);
                }
            }
            return $this->outputJson(0,$data);
        }
    }
    //集卡活动添加&修改
    public function postCardOperation(Request $request){
        $validator = Validator::make($request->all(), [
            'award_name' => 'required|min:1|max:255',
            'info' => 'required|min:1|max:255',
            'img' => 'required|min:1|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //奖品名
        $opData['award_name'] = trim($request['award_name']);
        //简介
        $opData['info'] = trim($request['info']);
        //图片地址
        $opData['img'] = trim($request['img']);
        $opData['status'] = 1;
        //判断是添加还是修改
        $id = isset($request['id']) && $request['id'] > 0 ? $request['id'] : 0;
        if($id > 0){
            $opData['updated_at'] = date("Y-m-d H:i:s");
            //修改
            $return = HdHockeyCardAward::where('id',$id)->update($opData);
            return $this->outputJson(0,$return);
        }
        $opData['created_at'] = date("Y-m-d H:i:s");
        //添加
        $return = HdHockeyCardAward::insertGetId($opData);
        return $this->outputJson(0,$return);
    }
    //竞猜活动添加
    public function postGuessAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'match_date' => 'required|date',
            'first_master' => 'required|integer|min:0',
            'first_visiting' => 'required|integer|min:0',
            'second_master' => 'required|integer|min:0',
            'second_visiting' => 'required|integer|min:0',
            'third_master' => 'required|integer|min:0',
            'third_visiting' => 'required|integer|min:0',
            'champion_status' => 'required|integer|min:0'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $inData['match_date'] = date("Y-m-d",strtotime(trim($request['match_date'])));
        //第一场主队
        $first_master = intval($request['first_master']);
        //第一场客队
        $first_visiting = intval($request['first_visiting']);
        if($first_master != 0 || $first_visiting != 0){
            //第一场对阵
            $inData['first'] = $first_master."-".$first_visiting;
        }
        //第二场主队比分
        $second_master = intval($request['second_master']);
        //第二场客队比分
        $second_visiting = intval($request['second_visiting']);
        if($second_master != 0 || $second_visiting != 0){
            //第二场对阵
            $inData['second'] = $second_master."-".$second_visiting;
        }
        //第三场主队
        $third_master = intval($request['third_master']);
        //第三场客队
        $third_visiting = intval($request['third_visiting']);
        if($third_master != 0 || $third_visiting != 0){
            //第三场对阵
            $inData['third'] = $third_master."-".$third_visiting;
        }
        //对阵类型0普通场1冠军场
        $inData['champion_status'] = intval($request['champion_status']);
        //判断冠军场是否存在
        $champion = HdHockeyGuessConfig::where('champion_status',1)->first();
        if(isset($champion['id']) && $champion['champion_status'] == 1 && $inData['champion_status'] == 1){
            return $this->outputJson(10002, array('error_msg'=>"冠军场只能添加一次"));
        }
        //判断日程是否重复
        $date = HdHockeyGuessConfig::where('match_date',$inData['match_date'])->first();
        if(isset($date['id']) && $date['match_date'] == $inData['champion_status']){
            return $this->outputJson(10002, array('error_msg'=>"赛程日期不能重复添加"));
        }
        $id = HdHockeyGuessConfig::insertGetId($inData);
        return $this->outputJson(0, $id);
    }
    //竞猜活动修改比分&开奖
    public function postGuessOperation(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'first_master_score' => 'required|integer|min:0',
            'first_visiting_score' => 'required|integer|min:0',
            'first_result' => 'min:0',
            'second_master_score' => 'required|integer|min:0',
            'second_visiting_score' => 'required|integer|min:0',
            'second_result' => 'min:0',
            'third_master_score' => 'required|integer|min:0',
            'third_visiting_score' => 'required|integer|min:0',
            'third_result' => 'min:0',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //第一场主队比分
        $first_master_score = intval($request['first_master_score']);
        //第一场客队比分
        $first_visiting_score = intval($request['first_visiting_score']);
        if($first_master_score != 0 || $first_visiting_score != 0){//第一场比分
            $upData['first_score'] = $first_master_score."-".$first_visiting_score;
        }
        //第二场主队比分
        $second_master_score = intval($request['second_master_score']);
        //第二场客队比分
        $second_visiting_score = intval($request['second_visiting_score']);
        if($second_master_score != 0 || $second_visiting_score != 0){//第二场比分
            $upData['second_score'] = $second_master_score."-".$second_visiting_score;
        }
        //第三场主队比分
        $third_master_score = intval($request['third_master_score']);
        //第三场客队比分
        $third_visiting_score = intval($request['third_visiting_score']);
        if($third_master_score != 0 || $third_visiting_score != 0){//第二场比分
            $upData['third_score'] = $third_master_score."-".$third_visiting_score;
        }
        //第一场比赛结果
        $first_result = intval($request['first_result']);
        //第二场比赛结果
        $second_result = intval($request['second_result']);
        //第三场比赛结果
        $third_result = intval($request['third_result']);
        $id = intval($request['id']);
        if($first_result > 0 && $second_result > 0 && $third_result > 0){
            //开奖
            $upData['first_result'] = $first_result;
            $upData['second_result'] = $second_result;
            $upData['third_result'] = $third_result;
            $upData['draw_info'] = $id."_first_".$first_result.",".$id."_second_".$second_result.",".$id."_third_".$third_result.",";
            $upData['open_status'] = 1;//状态0未开奖1已开奖2已发送开奖结果
        }
        $upData['updated_at'] = date("Y-m-d H:i:s");
        //修改
        $res = HdHockeyGuessConfig::where("id",$id)->update($upData);
        return $this->outputJson(0, $res);
    }
    //竞猜活动开奖结果发送
    public function postGuessSendOpenResult(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $id = intval($request['id']);
        $config = HdHockeyGuessConfig::where("id",$id)->first();
        if(isset($config['id']) && $config['open_status'] = 1 && !empty($config['first_result']) && !empty($config['second_result']) && !empty($config['third_result'])){
            //放入队列修改相关中奖信息
            $this->dispatch((new HockeyGuessJob($config))->onQueue('hockey'));
            return $this->outputJson(0, $config);
        }
        return $this->outputJson(10002,array('error_msg'=>"发送失败"));
    }
}
