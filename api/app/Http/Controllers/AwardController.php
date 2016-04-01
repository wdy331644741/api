<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\App;
use App\Models\Award;
use App\Models\Award1;
use App\Models\Award2;
use App\Models\Award3;
use App\Models\Award4;
use App\Models\Award5;
use App\Models\Award6;
use Config;
use Validator;

class AwardController extends Controller
{
    /**
     * 奖品添加
     * @param Request $request
     * @return mixed
     */
    function add(Request $request){
        //获取配置信息
        $awards = Config::get('app.awards');
        //奖品类型
        $award_type = intval($request->award_type);
        //活动ID
        $activityID = intval($request->activity_id);
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request);
            }
        }
        if($return['code'] == 200){
            //插到中间表 awards
            $awardID = $this->_awardAdd($award_type,$return['insert_id'],$activityID);
            if($awardID){
                return $this->outputJson(200,array('insert_id'=>$awardID));
            }else{
                return $this->outputJson(404,array('error_msg'=>'插入奖品中间表失败！'));
            }
        }else{
            return $this->outputJson(404,array('error_msg'=>$return['error_msg']));
        }
    }
    /**
     * 奖品添加
     * @param Request $request
     * @return mixed
     */
    function update(Request $request){
        //获取配置信息
        $awards = Config::get('app.awards');
        //奖品类型
        $award_type = isset($request->award_type) ? intval($request->award_type) : 0;
        //奖品ID（如果存在说明是修改）
        $award_id = isset($request->award_id) ? intval($request->award_id) : 0;
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request,$award_id,$award_type);
            }
        }
        if($return['code'] == 200){
            return $this->outputJson(200,array('error_msg'=>'修改成功！'));
        }else{
            return $this->outputJson(404,array('error_msg'=>$return['error_msg']));
        }
    }
    /**
     * 加息券
     * @param $request
     * @return bool
     */
    function _rateIncreases($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //加息值
        $data['rate_increases'] = isset($request->rate_increases) ? intval($request->rate_increases) : 0;
        if($data['rate_increases'] == 0){
            return array('code'=>'404','error_msg'=>'加息值不能为空!');
        }
        //加息时长类型
        $data['rate_increases_type'] = $request->rate_increases_type;
        if($data['rate_increases_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择加息时长类型!');
        }
        //加息时长信息
        $rate_increases_info = '';
        if($data['rate_increases_type'] == 2){
            $start = $this->FormValidation($request,'rate_increases_start','required','date');
            $end = $this->FormValidation($request,'rate_increases_end','required','date');
            if($start == false || $end == false){
                return array('code'=>'300','error_msg'=>'加息时间格式不对！');
            }
            $rate_increases_info = strtotime($start)."-".strtotime($end);
        }
        $data['rate_increases_info'] = $rate_increases_info;
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择有效时间!');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>'300','error_msg'=>'发放顺延天数不能为空！');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                return array('code'=>'300','error_msg'=>'有效时间格式不对！');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>'404','error_msg'=>'投资门槛不能为空!');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>'404','error_msg'=>'请选择项目期限类型!');
        }
        //项目期限信息
        $project_duration_info = '';
        if($data['project_duration_type'] == 1){
            $month = $request->project_duration_month;
            $project_duration_info = $month;
        }elseif($data['project_duration_type'] == 2){
            $start = isset($request->project_duration_start) ? intval($request->project_duration_start) : 0;
            $end = isset($request->project_duration_end) ? intval($request->project_duration_end) : 0;
            if($start == 0 || $end == 0){
                return array('code'=>'300','error_msg'=>'项目期限时间格式不对！');
            }
            $project_duration_info = $start."-".$end;
        }
        $data['project_duration_info'] = $project_duration_info;
        //项目类型
        $data['project_type'] = $request->project_type;
        //还款方式
        $data['repayment_type'] = $request->repayment_type;
        //计算方式
        $data['calculation_type'] = $request->calculation_type;
        //产品类型
        $data['product_type'] = $request->product_type;
        if($data['product_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择产品类型!');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>'300','error_msg'=>'产品类型指定ID不能为空！');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择平台端!');
        }
        //活动渠道
        $data['activity_channel'] = $request->activity_channel;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award1::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else{
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award1::insertGetId($data);
            return array('code'=>'200','insert_id' => $id);
        }
    }

    /**
     * 直抵红包
     * @param $request
     * @return bool
     */
    function _redMoney($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //红包金额
        $data['red_money'] = isset($request->red_money) ? intval($request->red_money) : 0;
        if($data['red_money'] == ''){
            return array('code'=>'404','error_msg'=>'红包金额不能为空!');
        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择有效时间!');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>'300','error_msg'=>'发放顺延天数不能为空！');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                return array('code'=>'300','error_msg'=>'有效时间格式不对！');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>'404','error_msg'=>'投资门槛不能为空!');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>'404','error_msg'=>'请选择项目期限类型!');
        }
        //项目期限信息
        $project_duration_info = '';
        if($data['project_duration_type'] == 1){
            $month = $request->project_duration_month;
            $project_duration_info = $month;
        }elseif($data['project_duration_type'] == 2){
            $start = isset($request->project_duration_start) ? intval($request->project_duration_start) : 0;
            $end = isset($request->project_duration_end) ? intval($request->project_duration_end) : 0;
            if($start == 0 || $end == 0){
                return array('code'=>'300','error_msg'=>'项目期限时间格式不对！');
            }
            $project_duration_info = $start."-".$end;
        }
        $data['project_duration_info'] = $project_duration_info;
        //项目类型
        $data['project_type'] = $request->project_type;
        //还款方式
        $data['repayment_type'] = $request->repayment_type;
        //计算方式
        $data['calculation_type'] = $request->calculation_type;
        //产品类型
        $data['product_type'] = $request->product_type;
        if($data['product_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择产品类型!');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>'300','error_msg'=>'产品类型指定ID不能为空！');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择平台端!');
        }
        //活动渠道
        $data['activity_channel'] = $request->activity_channel;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award2::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award2::insertGetId($data);
            return array('code' => '200', 'insert_id' => $id);
        }
    }
    /**
     * 百分比红包
     * @param $request
     * @return bool
     */
    function _rateRedMoney($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //红包最高金额
        $data['red_max_money'] = isset($request->red_max_money) ? intval($request->red_max_money) : 0;
        if($data['red_max_money'] == ''){
            return array('code'=>'404','error_msg'=>'红包最高金额不能为空!');
        }
        //百分比例
        $data['percentage'] = isset($request->percentage) ? intval($request->percentage) : 0;
        if($data['percentage'] == ''){
            return array('code'=>'404','error_msg'=>'百分比例不能为空!');
        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择有效时间!');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>'300','error_msg'=>'发放顺延天数不能为空！');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                return array('code'=>'300','error_msg'=>'有效时间格式不对！');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>'404','error_msg'=>'投资门槛不能为空!');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>'404','error_msg'=>'请选择项目期限类型!');
        }
        //项目期限信息
        $project_duration_info = '';
        if($data['project_duration_type'] == 1){
            $month = $request->project_duration_month;
            $project_duration_info = $month;
        }elseif($data['project_duration_type'] == 2){
            $start = isset($request->project_duration_start) ? intval($request->project_duration_start) : 0;
            $end = isset($request->project_duration_end) ? intval($request->project_duration_end) : 0;
            if($start == 0 || $end == 0){
                return array('code'=>'300','error_msg'=>'项目期限时间格式不对！');
            }
            $project_duration_info = $start."-".$end;
        }
        $data['project_duration_info'] = $project_duration_info;
        //项目类型
        $data['project_type'] = $request->project_type;
        //还款方式
        $data['repayment_type'] = $request->repayment_type;
        //计算方式
        $data['calculation_type'] = $request->calculation_type;
        //产品类型
        $data['product_type'] = $request->product_type;
        if($data['product_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择产品类型!');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>'300','error_msg'=>'产品类型指定ID不能为空！');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择平台端!');
        }
        //活动渠道
        $data['activity_channel'] = $request->activity_channel;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award3::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award3::insertGetId($data);
            return array('code' => '200', 'insert_id' => $id);
        }
    }
    /**
     * 体验金
     * @param $request
     * @return bool
     */
    function _experienceAmount($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //体验金额
        $data['experience_amount_type'] = isset($request->experience_amount_type) ? intval($request->experience_amount_type) : 0;
        if($data['experience_amount_type'] == 0){
            return array('code'=>'404','error_msg'=>'体验金额不能为空!');
        }
        //体验金额信息
        $experience_amount_info = '';
        if($data['experience_amount_type'] == 1){
            $experience_amount_money = $this->FormValidation($request,'experience_amount_money','required','integer');
            if($experience_amount_money == false){
                return array('code'=>'404','error_msg'=>'固定金额不能为空!');
            }
            $experience_amount_info = $experience_amount_money;
        }elseif($data['experience_amount_type'] == 2){
            $experience_amount_multiple = $this->FormValidation($request,'experience_amount_multiple','required','integer');
            if($experience_amount_multiple == false){
                return array('code'=>'404','error_msg'=>'投资额倍数不能为空!');
            }
            $experience_amount_info = $experience_amount_multiple;
        }
        $data['experience_amount_info'] = $experience_amount_info;
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择有效时间!');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>'300','error_msg'=>'发放顺延天数不能为空！');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                return array('code'=>'300','error_msg'=>'有效时间格式不对！');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>'404','error_msg'=>'请选择平台端!');
        }
        //活动渠道
        $data['activity_channel'] = $request->activity_channel;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award4::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award4::insertGetId($data);
            return array('code' => '200', 'insert_id' => $id);
        }
    }
    /**
     * 用户积分
     * @param $request
     * @return bool
     */
    function _integral($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //积分值
        $data['integral_type'] = isset($request->integral_type) ? intval($request->integral_type) : 0;
        if($data['integral_type'] == 0){
            return array('code'=>'404','error_msg'=>'请选择积分值!');
        }
        //积分值信息
        $integral_info = '';
        if($data['integral_type'] == 1){
            $integral_money = $this->FormValidation($request,'integral_money','required','integer');
            if($integral_money == false){
                return array('code'=>'404','error_msg'=>'固定金额不能为空!');
            }
            $integral_info = $integral_money;
        }elseif($data['integral_type'] == 2){
            $integral_multiple = $this->FormValidation($request,'integral_multiple','required','integer');
            if($integral_multiple == false){
                return array('code'=>'404','error_msg'=>'投资额倍数不能为空!');
            }
            $integral_info = $integral_multiple;
        }
        $data['integral_info'] = $integral_info;
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award5::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award5::insertGetId($data);
            return array('code' => '200', 'insert_id' => $id);
        }
    }
    /**
     * 用户积分
     * @param $request
     * @return bool
     */
    function _objects($request,$award_id,$award_type){
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>'404','error_msg'=>'名称不能为空!');
        }
        //判断是添加还是修改
        if($award_id != 0 && $award_type != 0){
            //查询该信息是否存在
            $params['award_id'] = $award_id;
            $params['award_type'] = $award_type;
            $limit = 1;
            $isExist = $this->_getAwardList($params,$limit);
            //修改时间
            $data['updated_at'] = time();
            if($isExist){
                $status = Award6::where('id',$award_id)->update($data);
                if($status){
                    return array('code'=>'200','error_msg'=>'修改成功!');
                }else{
                    return array('code'=>'404','error_msg'=>'修改失败!');
                }
            }else{
                return array('code'=>'404','error_msg'=>'该奖品不存在!');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award6::insertGetId($data);
            return array('code' => '200', 'insert_id' => $id);
        }
    }
    /**
     * 添加到awards
     * @param $award_type
     * @param $award_id
     * @param $activityID
     * @return mixed
     */
    function _awardAdd($award_type,$award_id,$activityID){
        $data['activity_id'] = $activityID;
        $data['award_type'] = $award_type;
        $data['award_id'] = $award_id;
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $id = Award::insertGetId($data); 
        return $id;
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
     * 获取全部列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function getList(Request $request){
        //活动ID
        $params['activity_id'] = isset($request->activity_id) ? intval($request->activity_id) : 0;
        //是否是全部数据
        $limit = 0;
        //获取全部列表
        $awardList = $this->_getAwardList($params,$limit);
        if($awardList){
            return $this->outputJson(200,array('data'=>$awardList));
        }else{
            return $this->outputJson(404,array('error_msg'=>'参数错误'));
        }

    }
    /**
     * 获取全部列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function getOne(Request $request){
        //奖品类型
        $params['award_type'] = isset($request->award_type) ? intval($request->award_type) : 0;
        //奖品ID
        $params['award_id'] = isset($request->award_id) ? intval($request->award_id) : 0;
        //是否是全部数据
        $limit = 1;
        //获取全部列表
        $awardList = $this->_getAwardList($params,$limit);
        if($awardList){
            return $this->outputJson(200,array('data'=>$awardList));
        }else{
            return $this->outputJson(404,array('error_msg'=>'参数错误'));
        }
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
        if($limit == 0 && $params['activity_id'] !== 0){
            //获取全部列表
            //查询中间表
            $awards = Award::where('activity_id','=',$params['activity_id'])->orderBy('updated_at','desc')->get()->toArray();
            //遍历获取奖品信息
            foreach($awards as $item){
                $table = $this->_getAwardTable($item['award_type']);
                $data = $table::where('id',$item['award_id'])->get()->toArray();
                if(!empty($data)){
                    $returnArray[] = $data[0];
                }
            }
            return $returnArray;
        }elseif ($limit == 1 && !empty($params['award_type']) && !empty($params['award_id'])) {
            //获取单条信息
            $table = $this->_getAwardTable($params['award_type']);
            $returnArray = $table::where('id', $params['award_id'])->get()->toArray();
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
        if($awardType >= 1 && $awardType <= 6){
            if($awardType == 1){
                return new Award1;
            }elseif($awardType == 2){
                return new Award2;
            }elseif($awardType == 3){
                return new Award3;
            }elseif($awardType == 4){
                return new Award4;
            }elseif($awardType == 5){
                return new Award5;
            }elseif($awardType == 6){
                return new Award6;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
