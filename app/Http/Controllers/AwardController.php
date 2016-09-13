<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use App\Models\Coupon;
use App\Jobs\CouponExport;
use DB;
use App\Service\SendAward;
use Response;
use App\Models\AwardBatch;
use App\Jobs\BatchAward;
use App\Models\JsonRpc;
class AwardController extends AwardCommonController
{
    private $awards = [];

    public function __construct()
    {
        //发送奖品配置
        $this->awards = [
            'awards' => [
                '1' => '_rateIncreases',//加息券
                '2' => '_redMoney',//红包&百分比红包
                '3' => '_experienceAmount',//体验金
                '4' => '_integral',//用户积分
                '5' => '_objects',//实物
                '6' => '_couponAdd',//优惠券
            ]
        ];
    }

    /**
     * 给用户添加奖品
     * 
     * @param Request $request
     * @return json 
     */
    function postAddAwardToUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|min:1',
            'awardType' => 'required|integer',
            'awardId' => 'required|integer',
            'sourceName' => 'string',
        ]); 
        $userId = $request->userId;
        if(strlen($userId) == 11) {
            $jsonRpc = new JsonRpc();
            $rpcRes = $jsonRpc->inside()->getUserIdByPhone(array('phone'=>$userId));
            if(isset($rpcRes['result']) && $rpcRes['result']['code'] == 0 && $rpcRes['result']['message'] == 'success') {
                $userId = $rpcRes['result']['user_id'];    
            }else{
                return $this->outputRpc($rpcRes);    
            }
        }
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $res = SendAward::sendDataRole($request->userId, $request->awardType, $request->awardId, 0, $request->sourceName );
        return $this->outputJson(0, $res);
    }

    /**
     * 奖品添加
     * @param Request $request
     * @return mixed
     */
    function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //获取配置信息
        $awards = $this->awards['awards'];
        //奖品类型
        $award_type = $request->award_type;
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request,0,0);
            }
        }
        if($return['code'] == 200){
            return $this->outputJson(0,array('insert_id'=>$return['insert_id']));
        }elseif($return['code'] == 404){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$return['error_msg']));
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
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //获取配置信息
        $awards = $this->awards['awards'];
        //奖品类型
        $award_type = $request->award_type;
        //奖品ID（如果存在说明是修改）
        $award_id = $request->award_id;
        foreach($awards as $k=>$v){
            if($award_type == $k){
                $return = $this->$v($request,$award_id,$award_type);
            }
        }
        if($return['code'] == 200){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }elseif($return['code'] == 404){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$return['error_msg']));
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
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //奖品类型
        $params['award_type'] = $request->award_type;
        //奖品ID
        $params['award_id'] = $request->award_id;
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
        $validator = Validator::make($request->all(), [
            'coupon_id' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $where['coupon_id'] = $request->coupon_id;
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
        $validator = Validator::make($request->all(), [
            'coupon_id' => 'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $where['coupon_id'] = $request->coupon_id;
        $where['is_use'] = 0;
        //获取一个可用的优惠券
        $list = CouponCode::where($where)->paginate(20);
        return $this->outputJson(0,$list);
    }
    /**
     * 优惠券导出
     * @param Request $request
     */
    public function getCouponExport(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $where['id'] = $request->id;
        $names = Coupon::where($where)->select('name')->get()->toArray();
        if(empty($names)){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该数据不存在'));
        }
        $name = isset($names[0]['name']) && !empty($names[0]['name']) ? $names[0]['name'] : 'default';
        $this->dispatch(new CouponExport($request->id,$name));
        //修改导出状态为正在导出
        Coupon::where('id',$request->id)->update(array('export_status'=>1));
        return $this->outputJson(0,array('error_msg'=>'导出成功'));
    }
    public function getCouponDownload(Request $request){
        //验证必填项
        $validator = Validator::make($request->all(), [
            'file' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        return Response::download(base_path()."/storage/exports/{$request->file}");
    }
    /**
     * 批次发奖品
     */
    public function postBatchAward(Request $request){
        $uids = $request->uids;
        
        //验证必填项
        $validator = Validator::make($request->all(), [
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
            'source_name' => 'required|string|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //插入日志
        $data['uids'] = $request->uids;
        $data['award_type'] = $request->award_type;
        $data['award_id'] = $request->award_id;
        $data['source_name'] = $request->source_name;
        $data['created_at'] = date("Y-m-d H:i:s");
        $data['updated_at'] = date("Y-m-d H:i:s");
        $insertID = AwardBatch::insertGetId($data);
        //放入队列
        $this->dispatch(new BatchAward($request->uids,$request->award_type,$request->award_id,$request->source_name,$insertID));
        return $this->outputJson(0,array('error_msg'=>'成功'));
    }
}
