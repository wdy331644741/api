<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use App\Jobs\FileImport;
use DB;

class AwardController extends AwardCommonController
{
    private $awards = [];

    public function __construct()
    {
        //发送奖品配置
        $this->awards = [
            'awards' => [
                '1' => '_rateIncreases',//加息券
                '2' => '_redMoney',//红包
                '3' => '_experienceAmount',//百分比红包
                '4' => '_integral',//体验金
                '5' => '_objects',//用户积分
                '6' => '_couponAdd',//实物
            ]
        ];
    }

    /**
     * 奖品添加
     * @param Request $request
     * @return mixed
     */
    function postAdd(Request $request){
        //获取配置信息
        $awards = $this->awards['awards'];
        //奖品类型
        $award_type = intval($request->award_type);
        if(empty($award_type)){
            return $this->outputJson(PARAMS_ERROR,array('award_type'=>'奖品类型id不能为空'));
        }
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request,0,0);
            }
        }
        if($return['code'] == 200){
            return $this->outputJson(0,array('insert_id'=>$return['insert_id']));
        }elseif($return['code'] == 404){
            return $this->outputJson(PARAMS_ERROR,array('error_param'=>$return['params'],'error_msg'=>$return['error_msg']));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>$return['error_msg']));
        }
    }
    /**
     * 奖品添加
     * @param Request $request
     * @return mixed
     */
    function postUpdate(Request $request){
        //获取配置信息
        $awards = $this->awards['awards'];
        //奖品类型
        $award_type = isset($request->award_type) ? intval($request->award_type) : 0;
        if(empty($award_type)){
            return $this->outputJson(PARAMS_ERROR,array('award_type'=>'奖品类型id不能为空'));
        }
        //奖品ID（如果存在说明是修改）
        $award_id = isset($request->award_id) ? intval($request->award_id) : 0;
        if(empty($award_id)){
            return $this->outputJson(PARAMS_ERROR,array('award_id'=>'奖品id不能为空'));
        }
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request,$award_id,$award_type);
            }
        }
        if($return['code'] == 200){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }elseif($return['code'] == 404){
            return $this->outputJson(PARAMS_ERROR,array('error_param'=>$return['params'],'error_msg'=>$return['error_msg']));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>$return['error_msg']));
        }
    }

    /**
     * 获取某个类型全部列表含分页
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postGetList(Request $request){
        //奖品类型ID
        $params['award_type'] = isset($request->award_type) ? intval($request->award_type) : 0;
        //是否是全部数据
        $limit = 0;
        //获取全部列表
        $awardList = $this->_getAwardList($params,$limit);
        if($awardList){
            return $this->outputJson(0,$awardList);
        }else{
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'参数错误'));
        }

    }
    /**
     * 获取单个信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postGetOne(Request $request){
        //奖品类型
        $params['award_type'] = isset($request->award_type) ? intval($request->award_type) : 0;
        //奖品ID
        $params['award_id'] = isset($request->award_id) ? intval($request->award_id) : 0;
        //是否是全部数据
        $limit = 1;
        //获取全部列表
        $awardList = $this->_getAwardList($params,$limit);
        if($awardList){
            return $this->outputJson(0,$awardList);
        }else{
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>'参数错误'));
        }
    }
    /**
     * 删除奖品
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postDelete(Request $request){
        //奖品类型
        $params['award_type'] = isset($request->award_type) ? intval($request->award_type) : 0;
        //奖品ID
        $params['award_id'] = isset($request->award_id) ? intval($request->award_id) : 0;
        //删除奖品表
        $table = $this->_getAwardTable($params['award_type']);
        $status = $table::where('id',$params['award_id'])->delete();
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    /**
     * 获取优惠券使用状态
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postCouponCodeTotal(Request $request){
        $where['coupon_id'] = $request['coupon_id'];
        $total = CouponCode::where($where)->select('is_use',DB::raw('COUNT(*) AS count'))->GroupBy('is_use')->get()->toArray();
        if(empty($total)){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'没有找到该优惠券的码'));
        }else{
            if(count($total) >= 1){
                $return = array();
                foreach($total as $item){
                    if($item['is_use'] == 0){
                        $return['notUse'] = $item['count'];
                    }
                    if($item['is_use'] == 1){
                        $return['use'] = $item['count'];
                    }
                }
            }
            return $this->outputJson(0,$return);
        }
    }

    /**
     * 获取优惠码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postGetCouponCode(Request $request){
        $where['coupon_id'] = isset($request->coupon_id) ? intval($request->coupon_id) : 0;
        if($where['coupon_id'] == 0){
            return $this->outputJson(PARAMS_ERROR,array('coupon_id'=>'优惠券id参数有误'));
        }
        $where['is_use'] = 0;
        //获取一个可用的优惠券
        $first = CouponCode::where($where)->first()->toArray();
        if(isset($first['id']) && !empty($first['id']) && !empty($first['code'])){
            $data['is_use'] = 1;
            //修改为已使用状态
            $status = CouponCode::where('id',$first['id'])->update($data);
            if($status === 1){
                return $this->outputJson(0,array('code'=>$first['code']));
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该优惠券不存在或已被使用'));
            }
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该优惠券已使用完'));
        }
    }
    /**
     * 获取优惠码列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function postCouponCodeList(Request $request){
        $where['coupon_id'] = isset($request->coupon_id) ? intval($request->coupon_id) : 0;
        if($where['coupon_id'] == 0){
            return $this->outputJson(PARAMS_ERROR,array('coupon_id'=>'优惠券id参数有误'));
        }
        $where['is_use'] = 0;
        //获取一个可用的优惠券
        $list = CouponCode::where($where)->paginate(3);
        return $this->outputJson(0,$list);
    }
}
