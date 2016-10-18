<?php
namespace App\Http\Controllers;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use App\Models\Coupon;
use Validator;
use App\Jobs\FileImport;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/19
 * Time: 17:27
 */
class AwardCommonController extends Controller{
    /**
     * 加息券
     * @param $request
     * @return bool
     */
    function _rateIncreases($request,$award_id,$award_type){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'rate_increases' => 'required|numeric|between:0.1,100',
            'rate_increases_type' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('rate_increases_time', 'required|integer|min:1|max:30', function($input) {
            return $input->rate_increases_type == 2;
        });
//        $validator->sometimes(array('rate_increases_start','rate_increases_end'), 'required|date', function($input) {
//            return $input->rate_increases_type == 3;
//        });
//        $validator->sometimes('rate_increases_time', 'required|integer|min:1|max:12', function($input) {
//            return $input->rate_increases_type == 4;
//        });
        $validator->sometimes('effective_time_day', 'required|integer|min:1', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
        }
        //名称
        $data['name'] = $request->name;
        //加息值
        $data['rate_increases'] = $request->rate_increases/100;
        //加息时长类型
        $data['rate_increases_type'] = $request->rate_increases_type;
        //加息时长天数
//        if($data['rate_increases_type'] == 2 || $data['rate_increases_type'] == 4){
//            $data['rate_increases_time'] = $request->rate_increases_time;
//        }
        if($data['rate_increases_type'] == 1){
            $data['rate_increases_time'] = 0;
        }
        if($data['rate_increases_type'] == 2){
            $data['rate_increases_time'] = $request->rate_increases_time;
        }
//        //加息时长时间段
//        if($data['rate_increases_type'] == 3) {
//            $data['rate_increases_start'] = empty($request->rate_increases_start) ? null : $request->rate_increases_start;
//            $data['rate_increases_end'] = empty($request->rate_increases_end) ? null : $request->rate_increases_end;
//        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        //有效时间顺延天数
        if($data['effective_time_type'] == 1){
            $data['effective_time_day'] = $request->effective_time_day;
            $data['effective_time_start'] = null;
            $data['effective_time_end'] = null;
        }
        //有效时间段
        if($data['effective_time_type'] == 2) {
            $data['effective_time_day'] = 0;
            $data['effective_time_start'] = empty($request->effective_time_start) ? null : $request->effective_time_start;
            $data['effective_time_end'] = empty($request->effective_time_end) ? null : $request->effective_time_end;
        }
        //投资门槛
        $data['investment_threshold'] = $request->investment_threshold;
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $request->project_duration_time;
        }
        //项目类型
        $data['project_type'] = $request->project_type;
        //产品ID
        $data['product_id'] = isset($request->product_id) ? trim($request->product_id) : "";
        //平台端
        $data['platform_type'] = $request->platform_type;
        //限制说明
        $data['limit_desc'] = trim($request->limit_desc);
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Award1::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else{
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = Award1::insertGetId($data);
            return array('code'=>200,'insert_id' => $id);
        }
    }

    /**
     * 直抵红包
     * @param $request
     * @return bool
     */
    function _redMoney($request,$award_id,$award_type){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'red_type' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
            'investment_threshold' => 'required|integer|min:0',
            'project_duration_type' => 'required|integer|min:1'
        ]);
        $validator->sometimes('red_money', 'required|integer|min:1', function($input) {
            return $input->red_type == 1;
        });
        $validator->sometimes('red_max_money', 'required|integer|min:1', function($input) {
            return $input->red_type == 2;
        });
        $validator->sometimes('percentage', 'required|numeric|between:0.1,100', function($input) {
            return $input->red_type == 2;
        });
        $validator->sometimes('effective_time_day', 'required|integer|min:1', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        $validator->sometimes('project_duration_time', 'required|integer', function($input) {
            return $input->project_duration_type > 1;
        });
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
        }
        //名称
        $data['name'] = $request->name;
        //红包类型
        $data['red_type'] = $request->red_type;
        if($data['red_type'] == 2){
            //红包最高金额
            $data['red_money'] = $request->red_max_money;
            //百分比例
            $data['percentage'] = $request->percentage/100;
        }
        if($data['red_type'] == 1){
            //红包金额
            $data['red_money'] = $request->red_money;
        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        //有效时间信息
        if($data['effective_time_type'] == 1){
            $data['effective_time_day'] = $request->effective_time_day;
            $data['effective_time_start'] = null;
            $data['effective_time_end'] = null;
        }elseif($data['effective_time_type'] == 2){
            $data['effective_time_day'] = 0;
            $data['effective_time_start'] = empty($request->effective_time_start) ? null : $request->effective_time_start;
            $data['effective_time_end'] = empty($request->effective_time_end) ? null : $request->effective_time_end;
        }
        //投资门槛
        $data['investment_threshold'] = $request->investment_threshold;
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        //项目期限时间
        if($data['project_duration_type'] > 1){
            $data['project_duration_time'] = $request->project_duration_time;
        }
        //项目类型
        $data['project_type'] = $request->project_type;
        //产品ID
        $data['product_id'] = isset($request->product_id) ? trim($request->product_id) : "";
        //平台端
        $data['platform_type'] = $request->platform_type;
        //限制说明
        $data['limit_desc'] = trim($request->limit_desc);
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Award2::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = Award2::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
        }
    }
    /**
     * 体验金
     * @param $request
     * @return bool
     */
    function _experienceAmount($request,$award_id,$award_type){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'experience_amount_money' => 'required|integer|min:1',
            'effective_time_type' => 'required|integer|min:1',
        ]);
        $validator->sometimes('effective_time_day', 'required|integer|min:1', function($input) {
            return $input->effective_time_type == 1;
        });
        $validator->sometimes(array('effective_time_start','effective_time_end'), 'required|date', function($input) {
            return $input->effective_time_type == 2;
        });
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
        }
        //名称
        $data['name'] = $request->name;
        //体验金额
        $data['experience_amount_money'] = $request->experience_amount_money;
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        //有效时间信息
        if($data['effective_time_type'] == 1){
            $data['effective_time_day'] = $request->effective_time_day;
            $data['effective_time_start'] = null;
            $data['effective_time_end'] = null;
        }elseif($data['effective_time_type'] == 2){
            $data['effective_time_day'] = 0;
            $data['effective_time_start'] = empty($request->effective_time_start) ? null : $request->effective_time_start;
            $data['effective_time_end'] = empty($request->effective_time_end) ? null : $request->effective_time_end;
        }
//        //产品ID
//        $data['product_id'] = isset($request->product_id) ? trim($request->product_id) : "";
//        if(empty($data['product_id'])){
//            return array('code'=>404,'params'=>'product_id','error_msg'=>'产品ID不能为空');
//        }
        //平台端
        $data['platform_type'] = $request->platform_type;
        //限制说明
        $data['limit_desc'] = trim($request->limit_desc);
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Award3::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = Award3::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
        }
    }
    /**
     * 用户积分
     * @param $request
     * @return bool
     */
    function _integral($request,$award_id,$award_type){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'integral' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
        }
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        //积分值
        $data['integral'] = isset($request->integral) ? intval($request->integral) : 0;
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Award4::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = Award4::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
        }
    }
    /**
     * 实物
     * @param $request
     * @return bool
     */
    function _objects($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Award5::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = Award5::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
        }
    }
    /**
     * 优惠券添加
     * @param $request
     * @return bool
     */
    function _couponAdd($request,$award_id,$award_type){
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:255',
            'desc' => 'required|min:2|max:255'
        ]);
        if($validator->fails()){
            return array('code'=>404,'error_msg'=>$validator->errors()->first());
        }
        //优惠券名称
        $data['name'] = $request->name;
        //优惠券简介
        $data['desc'] = $request->desc;
        //短信模板
        $data['message'] = $request->message;
        //站内信模板
        $data['mail'] = $request->mail;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = Coupon::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //优惠券码文件上传
            $path = base_path().'/storage/coupon/';
            if ($request->hasFile('file')) {
                //验证文件上传中是否出错
                if ($request->file('file')->isValid()){
                    $mimeTye = $request->file('file')->getClientOriginalExtension();
                    if($mimeTye == 'xlsx' || $mimeTye == 'xls'){
                        $fileName = date('YmdHis').mt_rand(1000,9999).'.'.$mimeTye;
                        //保存文件到路径
                        $request->file('file')->move($path,$fileName);
                        $file = $path.$fileName;
                    }else{
                        return array('code'=>404,'params'=>'file','error_msg'=>'优惠券文件格式错误');
                    }
                }else{
                    return array('code'=>404,'params'=>'file','error_msg'=>'优惠券文件错误');
                }
            }else{
                return array('code'=>404,'params'=>'file','error_msg'=>'优惠券文件不能为空');
            }
            $data['file'] = $file;
            if(!file_exists($data['file'])){
                return array('code'=>404,'params'=>'file','error_msg'=>'优惠券文件错误');
            }
            $data['created_at'] = date("Y-m-d H:i:s");
            //插入数据
            $insertID = Coupon::insertGetId($data);
            if($insertID){
                //修改导出状态为正在导入
                Coupon::where('id',$insertID)->update(array('import_status'=>1));
                $this->dispatch(new FileImport($insertID,$file));
                return array('code' => 200, 'insert_id' => $insertID);
            }else{
                return array('code' => 500, 'error_msg' => '插入失败');
            }

        }
    }
    /**
     * 验证必填项
     * @param $request
     * @param $field
     * @param $required
     * @param $limit
     * @return bool
     */
    function FormValidation($request,$field,$required,$limit){
        $rules = '';
        if($required == ''){
            $rules = $limit;
        }elseif($limit == ''){
            $rules = $required;
        }elseif($limit != '' && $required != ''){
            $rules = $required."|".$limit;
        }
        $validator = Validator::make($request->all(), [
            "{$field}" => "{$rules}"
        ]);
        if($validator->fails()){
            return false;
        }
        return $request->$field;
    }
    /**
     * 查询奖品方法
     * @param $params
     * @param $limit
     * @return array|bool
     */
    function _getAwardList($params,$limit){
        //返回值定义
        $returnArray = array();
        if($limit == 0 && $params['award_type'] !== 0){
            //获取全部列表
            $table = $this->_getAwardTable($params['award_type']);
            $returnArray = $table::orderBy('id','DESC')->paginate(20);
            return $returnArray;
        }elseif ($limit == 1 && !empty($params['award_type']) && !empty($params['award_id'])) {
            //获取单条信息
            $table = $this->_getAwardTable($params['award_type']);
            $returnArray = $table::where('id', $params['award_id'])->orderBy('id','DESC')->get()->toArray();
            return !empty($returnArray) ? $returnArray[0] : false;
        } else {
            return false;
        }
    }
    /**
     * 获取表对象
     * @param $awardType
     * @return Award1|Award2|Award3|Award4|Award5|Award6|bool
     */
    function _getAwardTable($awardType){
        if($awardType >= 1 && $awardType <= 6) {
            if ($awardType == 1) {
                return new Award1;
            } elseif ($awardType == 2) {
                return new Award2;
            } elseif ($awardType == 3) {
                return new Award3;
            } elseif ($awardType == 4) {
                return new Award4;
            } elseif ($awardType == 5) {
                return new Award5;
            } elseif ($awardType == 6){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}