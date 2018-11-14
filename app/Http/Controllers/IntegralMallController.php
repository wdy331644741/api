<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\BasicDatatables;
use App\Models\IntegralMall;
use App\Service\Func;
use App\Models\InPrizetype;
use App\Models\InPrize;
use Validator;
use DB;

class IntegralMallController extends Controller
{

    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id', 'name', 'banner','is_online','created_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:in_prizetypes,id',
    ];
    protected $addValidates = [
        'name' => 'required',
        'banner' => 'required',
    ];
    protected $updateValidates = [
        'id' => 'required|exists:in_prizetypes,id',
    ];

    function __construct() {
        $this->model = new InPrizetype;
    }

    //----------------app 3.7.1 积分商城-----------------------//

    function postPtDelete(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:in_prizetypes,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        DB::beginTransaction();
        $res = InPrizetype::destroy($request->id);
        $child_res  = InPrize::where('type_id',$request->id)->delete();
        if($child_res !== false && $res){
            DB::commit();
            return $this->outputJson(0);
        }else{
            DB::rollback();
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //上线，下线
    function postChangeStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = InPrizetype::where($where)->select('is_online')->first();
        if(empty($data)){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该类型不存在'));
        }
        $status = $data['is_online'] ? 0 : 1;
        //上线修改
        $status = InPrizetype::where('id',$mall_id)->update(array('is_online'=>$status));
        if($status){
            return $this->outputJson(0, array('error_msg'=>'修改成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'操作失败'));
        }
    }

    //上移
    function postUp(Request $request){
        $id = $request->id;
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:in_prizetypes,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = InPrizetype::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = InPrizetype::whereRaw("id + sort > $current_num")->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = InPrizetype::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = InPrizetype::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //下移
    function postDown(Request $request){
        $id = $request->id;
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:in_prizetypes,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = InPrizetype::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = InPrizetype::whereRaw("id + sort < $current_num")->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = InPrizetype::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = InPrizetype::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }




    //---------------- 原积分商城 -----------------------//
    /**
     * 商品添加&修改
     */
    function postOperation(Request $request){
        $mall_id = intval($request->id);
        $validator = Validator::make($request->all(), [
            'integral' => 'required|integer|min:1',
            'desc' => 'required|string|min:1',
            'photo' => 'required|string|min:1',
            'photo_min' => 'required|string|min:1',
            'total_quantity' => 'required|integer|min:0',
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required|integer|min:1',
            'groups' => 'required|string|min:1',
            'start_time' => 'date',
            'end_time' => 'date',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //积分值
        $data['integral'] = $request->integral;
        //说明
        $data['desc'] = $request->desc;
        //配图
        $data['photo'] = $request->photo;
        //配图(小)
        $data['photo_min'] = $request->photo_min;
        //总数量
        $data['total_quantity'] = $request->total_quantity;
        //奖品类型
        $data['award_type'] = $request->award_type;
        //奖品id
        $data['award_id'] = $request->award_id;
        //用户兑换总量
        $data['user_quantity'] = $request->user_quantity;
        //分组
        $data['groups'] = $request->groups;
        //开始时间
        $data['start_time'] = !empty($request->start_time) ? $request->start_time : null;
        //结束时间
        $data['end_time'] = !empty($request->end_time) ? $request->end_time : null;
        //判断是添加还是修改
        if($mall_id != 0){
            //查询该信息是否存在
            $where = array();
            $where['id'] = $mall_id;
            $isExist = IntegralMall::where($where)->count();
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = IntegralMall::where('id',$mall_id)->update($data);
                if($status){
                    return $this->outputJson(0, array('error_msg'=>'修改成功'));
                }else{
                    return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
                }
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
            }
        }else{
            //添加时间
            $data['created_at'] = date("Y-m-d H:i:s");
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            $id = IntegralMall::insertGetId($data);
            return $this->outputJson(0, array('insert_id'=>$id));
        }
    }
    /**
     * 商品列表
     */
    function getList(Request $request){
        $request->data = array('order'=>array("status desc," => "id + priority desc"));
        $data = Func::Search($request,new IntegralMall());
        $awardCommon = new AwardCommonController;
        foreach($data as &$item){
            $params = array();
            $params['award_type'] = $item->award_type;
            $params['award_id'] = $item->award_id;
            $awardList = $awardCommon->_getAwardList($params,1);
            if(!empty($awardList) && isset($awardList['name']) && !empty($awardList['name'])){
                $item->name = $awardList['name'];
            }else{
                $item->name = '';
            }
        }
        return $this->outputJson(0,$data);
    }
    /**
     * 商品上线
     */
    function postUpStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = IntegralMall::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //上线修改
        $status = IntegralMall::where('id',$mall_id)->update(array('status'=>1,'release_time'=>date("Y-m-d H:i:s")));
        if($status){
            return $this->outputJson(0, array('error_msg'=>'上线成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'上线失败'));
        }
    }
    /**
     * 商品下线
     */
    function postDownStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = IntegralMall::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //下线修改
        $status = IntegralMall::where('id',$mall_id)->update(array('status'=>0));
        if($status){
            return $this->outputJson(0, array('error_msg'=>'下线成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'下线失败'));
        }
    }
    /**
     * 商品删除
     */
    function postDelete(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = IntegralMall::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //删除修改
        $integralMall = IntegralMall::find($mall_id);
        $res = $integralMall->delete();
        if($res){
            return $this->outputJson(0, array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }

    //商品上移
    public function getUp($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|integer|min:1'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = IntegralMall::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['priority'];
        $pre = IntegralMall::whereRaw("id + priority > $current_num")->where('status',1)->orderByRaw('id + priority ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['priority']) - $current['id'];

        $current_res = IntegralMall::where('id',$id)->update(array('priority'=>$curremt_sort));
        $pre_res = IntegralMall::where('id',$pre['id'])->update(array('priority'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //商品下移
    public function getDown($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:cms_contents,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = IntegralMall::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['priority'];
        $pre = IntegralMall::whereRaw("id + priority < $current_num")->where('status',1)->orderByRaw('id + priority DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'	Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['priority']) - $current['id'];

        $current_res = IntegralMall::where('id',$id)->update(array('priority'=>$curremt_sort));
        $pre_res = IntegralMall::where('id',$pre['id'])->update(array('priority'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


}
