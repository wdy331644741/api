<?php

namespace App\Http\Controllers;

use App\Models\OneYuanJoinInfo;
use App\Service\OneYuanBasic;
use Illuminate\Http\Request;
use App\Models\OneYuan;
use App\Service\Func;
use Validator;

class OneYuanController extends Controller
{
    /**
     * 商品添加&修改
     */
    function postOperation(Request $request){
        $mall_id = intval($request->id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1',
            'desc' => 'required|string|min:1',
            'photo' => 'required|string|min:1',
            'total_num' => 'required|integer|min:0',
            'start_time' => 'date',
            'end_time' => 'date'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //积分值
        $data['name'] = $request->name;
        //说明
        $data['desc'] = $request->desc;
        //配图
        $data['photo'] = $request->photo;
        //总数量
        $data['total_num'] = $request->total_num;
        //开始时间
        $data['start_time'] = $request->start_time;
        //结束时间
        $data['end_time'] = $request->end_time;
        //判断是添加还是修改
        if($mall_id != 0){
            //查询该信息是否存在
            $where = array();
            $where['id'] = $mall_id;
            $isExist = OneYuan::where($where)->count();
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = OneYuan::where('id',$mall_id)->update($data);
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
            $id = OneYuan::insertGetId($data);
            return $this->outputJson(0, array('insert_id'=>$id));
        }
    }
    /**
     * 商品列表
     */
    function getList(Request $request){
        $request->data = array('order'=>array("status desc," => "id + priority desc"));
        $data = Func::Search($request,new OneYuan());
        return $this->outputJson(0,$data);
    }
    /**
     * 商品上线
     */
    function postUpStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $data = OneYuan::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //上线修改
        $status = OneYuan::where('id',$mall_id)->update(array('status'=>1,'release_time'=>date("Y-m-d H:i:s")));
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
        $data = OneYuan::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //下线修改
        $status = OneYuan::where('id',$mall_id)->update(array('status'=>0,'offline_time'=>date("Y-m-d H:i:s")));
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
        $data = OneYuan::where($where)->select('status')->first();
        if(empty($data) && !isset($data['status'])){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
        }
        //删除修改
        $oneYuan = OneYuan::find($mall_id);
        $res = $oneYuan->delete();
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
        $current = OneYuan::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['priority'];
        $pre = OneYuan::whereRaw("id + priority > $current_num")->where('status',1)->orderByRaw('id + priority ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['priority']) - $current['id'];

        $current_res = OneYuan::where('id',$id)->update(array('priority'=>$curremt_sort));
        $pre_res = OneYuan::where('id',$pre['id'])->update(array('priority'=>$pre_sort));
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
        $current = OneYuan::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['priority'];
        $pre = OneYuan::whereRaw("id + priority < $current_num")->where('status',1)->orderByRaw('id + priority DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'	Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['priority']) - $current['id'];

        $current_res = OneYuan::where('id',$id)->update(array('priority'=>$curremt_sort));
        $pre_res = OneYuan::where('id',$pre['id'])->update(array('priority'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    function postLuckDraw(){
        $a = OneYuanBasic::luckDraw(3,23);
        print_R($a);exit;
    }

    /**
     * 给用户手动冲次数
     * @param Request $request
     */
    function postAddOneYuanNum(Request $request){
        $phone = $request->phone;
        $num = intval($request->num);
        if(empty($phone) || empty($num)){
            return $this->outputJson(10001,array('error_msg'=>"参数错误"));
        }
        //根据手机号查询用户id
        $userId = Func::getUserIdByPhone($phone);
        if(empty($userId)){
            return $this->outputJson(10001,array('error_msg'=>"没查询到该手机号"));
        }
        //添加次数
        $return = OneYuanBasic::addNum($userId,$num,'manual',array('manual'=>$num));
        if(isset($return['status']) && $return['status'] === true){
            return $this->outputJson(0,array('error_msg'=>"添加次数成功"));
        }
        return $this->outputJson(10002,array('error_msg'=>'添加次数失败'));
    }
}
