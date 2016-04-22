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
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //加息值
        $data['rate_increases'] = isset($request->rate_increases) ? intval($request->rate_increases) : 0;
        if($data['rate_increases'] == 0){
            return array('code'=>404,'params'=>'rate_increases','error_msg'=>'加息值不能为空');
        }
        //加息时长类型
        $data['rate_increases_type'] = $request->rate_increases_type;
        if($data['rate_increases_type'] == null){
            return array('code'=>404,'params'=>'rate_increases_type','error_msg'=>'请选择加息时长类型');
        }
        //加息时长信息
        $rate_increases_info = '';
        if($data['rate_increases_type'] == 2){
            $start = $this->FormValidation($request,'rate_increases_start','required','date');
            $end = $this->FormValidation($request,'rate_increases_end','required','date');
            if($start == false || $end == false){
                $params = $start == false ? 'rate_increases_start' : 'rate_increases_end';
                return array('code'=>404,'params'=>$params,'error_msg'=>'加息时间格式不对');
            }
            $rate_increases_info = strtotime($start)."-".strtotime($end);
        }
        $data['rate_increases_info'] = $rate_increases_info;
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>404,'params'=>'effective_time_type','error_msg'=>'请选择有效时间');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>404,'params'=>'effective_time_day','error_msg'=>'发放顺延天数不能为空');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                $params = $start == false ? 'effective_time_start' : 'effective_time_end';
                return array('code'=>404,'params'=>$params,'error_msg'=>'有效时间格式不对');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>404,'params'=>'investment_threshold','error_msg'=>'投资门槛不能为空');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>404,'params'=>'project_duration_type','error_msg'=>'请选择项目期限类型');
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
                $params = $start == 0 ? 'project_duration_start' : 'project_duration_end';
                return array('code'=>404,'params'=>$params,'error_msg'=>'项目期限时间格式不对');
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
            return array('code'=>404,'params'=>'product_type','error_msg'=>'请选择产品类型');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>404,'params'=>'product_typeid','error_msg'=>'产品类型指定ID不能为空');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>404,'params'=>'platform_type','error_msg'=>'请选择平台端');
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
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else{
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
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
        //名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if($data['name'] == ''){
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //红包金额
        $data['red_money'] = isset($request->red_money) ? intval($request->red_money) : 0;
        if($data['red_money'] == ''){
            return array('code'=>404,'params'=>'red_money','error_msg'=>'红包金额不能为空');
        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>404,'params'=>'effective_time_type','error_msg'=>'请选择有效时间');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>404,'params'=>'effective_time_day','error_msg'=>'发放顺延天数不能为空');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                $params = $start == false ? 'effective_time_start' : 'effective_time_end' ;
                return array('code'=>404,'params'=>$params,'error_msg'=>'有效时间格式不对');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>404,'params'=>'investment_threshold','error_msg'=>'投资门槛不能为空');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>404,'params'=>'project_duration_type','error_msg'=>'请选择项目期限类型');
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
                $params = $start == 0 ? 'project_duration_start' : 'project_duration_end' ;
                return array('code'=>404,'params'=>$params,'error_msg'=>'项目期限时间格式不对');
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
            return array('code'=>404,'params'=>'product_type','error_msg'=>'请选择产品类型');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>404,'params'=>'product_typeid','error_msg'=>'产品类型指定ID不能为空');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>404,'params'=>'platform_type','error_msg'=>'请选择平台端');
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
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award2::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
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
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //红包最高金额
        $data['red_max_money'] = isset($request->red_max_money) ? intval($request->red_max_money) : 0;
        if($data['red_max_money'] == ''){
            return array('code'=>404,'params'=>'red_max_money','error_msg'=>'红包最高金额不能为空');
        }
        //百分比例
        $data['percentage'] = isset($request->percentage) ? intval($request->percentage) : 0;
        if($data['percentage'] == ''){
            return array('code'=>404,'params'=>'percentage','error_msg'=>'百分比例不能为空');
        }
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>404,'params'=>'effective_time_type','error_msg'=>'请选择有效时间');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>404,'params'=>'effective_time_day','error_msg'=>'发放顺延天数不能为空');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                $params = $start == false ? 'effective_time_start' : 'effective_time_end' ;
                return array('code'=>404,'params'=>$params,'error_msg'=>'有效时间格式不对');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //投资门槛
        $data['investment_threshold'] = isset($request->investment_threshold) ? intval($request->investment_threshold) : 0;
        if($data['investment_threshold'] == 0){
            return array('code'=>404,'params'=>'investment_threshold','error_msg'=>'投资门槛不能为空');
        }
        //项目期限类型
        $data['project_duration_type'] = $request->project_duration_type;
        if($data['project_duration_type'] == 0){
            return array('code'=>404,'params'=>'project_duration_type','error_msg'=>'请选择项目期限类型');
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
                $params = $start == 0 ? 'project_duration_start' : 'project_duration_end' ;
                return array('code'=>404,'params'=>$params,'error_msg'=>'项目期限时间格式不对');
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
            return array('code'=>404,'params'=>'product_type','error_msg'=>'请选择产品类型');
        }
        //产品类型信息
        $product_type_info = '';
        if($data['product_type'] == 1){
            $product_type_info = $request->product_types;
        }elseif($data['product_type'] == 2){
            $ids = $request->product_typeid;
            if($ids == ''){
                return array('code'=>404,'params'=>'product_typeid','error_msg'=>'产品类型指定ID不能为空');
            }
            $product_type_info = $ids;
        }
        $data['product_type_info'] = $product_type_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>404,'params'=>'platform_type','error_msg'=>'请选择平台端');
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
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award3::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
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
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //体验金额
        $data['experience_amount_type'] = isset($request->experience_amount_type) ? intval($request->experience_amount_type) : 0;
        if($data['experience_amount_type'] == 0){
            return array('code'=>404,'params'=>'experience_amount_type','error_msg'=>'体验金额不能为空');
        }
        //体验金额信息
        $experience_amount_info = '';
        if($data['experience_amount_type'] == 1){
            $experience_amount_money = $this->FormValidation($request,'experience_amount_money','required','integer');
            if($experience_amount_money == false){
                return array('code'=>404,'params'=>'experience_amount_money','error_msg'=>'固定金额不能为空');
            }
            $experience_amount_info = $experience_amount_money;
        }elseif($data['experience_amount_type'] == 2){
            $experience_amount_multiple = $this->FormValidation($request,'experience_amount_multiple','required','integer');
            if($experience_amount_multiple == false){
                return array('code'=>404,'params'=>'experience_amount_multiple','error_msg'=>'投资额倍数不能为空');
            }
            $experience_amount_info = $experience_amount_multiple;
        }
        $data['experience_amount_info'] = $experience_amount_info;
        //有效时间类型
        $data['effective_time_type'] = $request->effective_time_type;
        if($data['effective_time_type'] == null){
            return array('code'=>404,'params'=>'effective_time_type','error_msg'=>'请选择有效时间');
        }
        //有效时间信息
        $effective_time_info = '';
        if($data['effective_time_type'] == 2){
            $day = isset($request->effective_time_day) ? intval($request->effective_time_day) : 0;
            if($day == 0){
                return array('code'=>404,'params'=>'effective_time_day','error_msg'=>'发放顺延天数不能为空');
            }
            $effective_time_info = $day;
        }elseif($data['effective_time_type'] == 3){
            $start = $this->FormValidation($request,'effective_time_start','required','date');
            $end = $this->FormValidation($request,'effective_time_end','required','date');
            if($start == false || $end == false){
                $params = $start == false ? 'effective_time_start' : 'effective_time_end' ;
                return array('code'=>404,'params'=>$params,'error_msg'=>'有效时间格式不对');
            }
            $effective_time_info = strtotime($start)."-".strtotime($end);
        }
        $data['effective_time_info'] = $effective_time_info;
        //平台端
        $data['platform_type'] = $request->platform_type;
        if($data['platform_type'] == null){
            return array('code'=>404,'params'=>'platform_type','error_msg'=>'请选择平台端');
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
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award4::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
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
            return array('code'=>404,'params'=>'name','error_msg'=>'名称不能为空');
        }
        //积分值
        $data['integral_type'] = isset($request->integral_type) ? intval($request->integral_type) : 0;
        if($data['integral_type'] == 0){
            return array('code'=>404,'params'=>'integral_type','error_msg'=>'请选择积分值');
        }
        //积分值信息
        $integral_info = '';
        if($data['integral_type'] == 1){
            $integral_money = $this->FormValidation($request,'integral_money','required','integer');
            if($integral_money == false){
                return array('code'=>404,'params'=>'integral_money','error_msg'=>'固定金额不能为空');
            }
            $integral_info = $integral_money;
        }elseif($data['integral_type'] == 2){
            $integral_multiple = $this->FormValidation($request,'integral_multiple','required','integer');
            if($integral_multiple == false){
                return array('code'=>404,'params'=>'integral_multiple','error_msg'=>'投资额倍数不能为空');
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
                    return array('code'=>200,'error_msg'=>'修改成功');
                }else{
                    return array('code'=>500,'error_msg'=>'修改失败');
                }
            }else{
                return array('code'=>500,'error_msg'=>'该奖品不存在');
            }
        }else {
            //添加时间
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award5::insertGetId($data);
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
            $data['updated_at'] = time();
            if($isExist){
                $status = Award6::where('id',$award_id)->update($data);
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
            $data['created_at'] = time();
            //修改时间
            $data['updated_at'] = time();
            $id = Award6::insertGetId($data);
            return array('code' => 200, 'insert_id' => $id);
        }
    }
    /**
     * 优惠券添加
     * @param $request
     * @return bool
     */
    function _couponAdd($request,$award_id,$award_type){
        //优惠券名称
        $data['name'] = isset($request->name) ? trim($request->name) : '';
        if(empty($data['name'])){
            return array('code'=>404,'params'=>'name','error_msg'=>'优惠券名称不能为空');
        }
        //优惠券简介
        $data['desc'] = isset($request->desc) ? trim($request->desc) : '';
        if(empty($data['desc'])){
            return array('code'=>404,'params'=>'desc','error_msg'=>'优惠券简介不能为空');
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
            $path = app_path().'/uploads/coupon/';
            if ($request->hasFile('file')) {
                //验证文件上传中是否出错
                if ($request->file('file')->isValid()){
                    $mimeTye = $request->file('file')->getClientOriginalExtension();
                    if($mimeTye == 'txt'){
                        $fileName = date('YmdHis').mt_rand(1000,9999).'.txt';
                        //保存文件到路径
                        $request->file('file')->move($path,$fileName);
                        $file = $path.$fileName;
                    }else{
                        return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'优惠券文件格式错误'));
                    }
                }else{
                    return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'优惠券文件错误'));
                }
            }else{
                return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'优惠券文件不能为空'));
            }
            $data['file'] = $file;
            if(!file_exists($data['file'])){
                return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'优惠券文件错误'));
            }
            $data['created_at'] = time();
            //插入数据
            $insertID = Coupon::insertGetId($data);
            return array('code' => 200, 'insert_id' => $insertID);
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
            $returnArray = $table::paginate(3);
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
        if($awardType >= 1 && $awardType <= 7) {
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
            } elseif ($awardType == 6) {
                return new Award6;
            } elseif ($awardType == 7){
                return new Coupon;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}