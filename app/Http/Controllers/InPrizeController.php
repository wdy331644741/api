<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\BasicDatatables;
use App\Service\Func;
use App\Models\InPrizetype;
use App\Models\InPrize;
use Validator;
use DB;

class InPrizeController extends Controller
{

    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','name','type_id','price','kill_price','start_at','end_at','award_type','award_id','stock','des','ex_note','disclaimer','list_img','detail_img','istop','is_online','des_img','created_at'];
    protected $deleteValidates = [
        'id' => 'required|exists:in_prizes,id',
    ];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:in_prizes,id',
    ];

    function __construct() {
        $this->model = new InPrize;
    }

    //----------------app 3.7.1 积分商城-----------------------//

    //商品列表
    public function getList(Request $request){
        $res = Func::freeSearch($request,new InPrize(),$this->fileds,['prizetypes']);
        return response()->json(array('error_code'=> 0, 'data'=>$res));
    }

    //商品类型列表
    public function getTypelist(){

        $data = InPrizetype::select('id','name')->where('is_online',1)->get()->toArray();
        $new_arr = [];
        foreach ($data as $val){
            $new_arr[$val['id']] = $val['name'];
        }
        return $this->outputJson(0,$data);
    }

    //添加积分商城奖品
    public function postOperation(Request $request)
    {
        $prize_id = intval($request->id);
        $validator = Validator::make($request->all(), [
            'type_id' => 'required|exists:in_prizetypes,id',
            'name' => 'required|string|min:1',
            'price' => 'required|integer|min:1',
            'kill_price' => 'integer|min:1',
            'award_type' => 'required|integer|min:1',
            'award_id' => 'required_unless:award_type,5',
            'stock' => 'required|string|min:1',
            'list_img' => 'required',
            'detail_img' => 'required',
            'des' => 'required',
            'ex_note' => 'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $data['type_id'] = $request->type_id;
        $data['name'] = $request->name;
        $data['price'] = $request->price;
        $data['kill_price'] = !empty($request->kill_price) ? $request->kill_price : null;
        $data['award_type'] = $request->award_type;
        $data['award_id'] = $request->awaed_type == 5 ? 0 : $request->award_id;
        $data['stock'] = $request->stock;
        $data['list_img'] = $request->list_img;
        $data['detail_img'] = $request->detail_img;
        $data['des_img'] = !empty($request->des_img) ? $request->des_img : null;
        $data['des'] = $request->des;
        $data['ex_note'] = $request->ex_note;
        $data['start_at'] = !empty($request->start_at) ? $request->start_at : null;
        $data['end_at'] = !empty($request->end_at) ? $request->end_at : null;
        $data['disclaimer'] = !empty($request->disclaimer) ? $request->disclaimer : null;
        //判断是添加还是修改
        if($prize_id != 0){
            //查询该信息是否存在
            $where = array();
            $where['id'] = $prize_id;
            $isExist = InPrize::where($where)->count();
            //修改时间
            $data['updated_at'] = date("Y-m-d H:i:s");
            if($isExist){
                $status = InPrize::where('id',$prize_id)->update($data);
                if($status){
                    return $this->outputJson(0, array('error_msg'=>'修改成功'));
                }else{
                    return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
                }
            }else{
                return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该商品不存在'));
            }
        }else{
            $data['created_at'] = date("Y-m-d H:i:s");
            $id = InPrize::insertGetId($data);
            return $this->outputJson(0, array('insert_id'=>$id));
        }
    }

    //批量删除
    public function postBatchDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $idArr = array_filter(explode('-',$request->id));

        $res = InPrize::whereIn('id',$idArr)->delete();
        if(empty($res)){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //上线，下线
    function postChangeStatus(Request $request){
        $mall_id = intval($request->id);
        $where = array();
        $where['id'] = $mall_id;
        $changeKey = $request->key;

        $data = InPrize::where($where)->select($changeKey)->first();
        if(empty($data)){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'该类型不存在'));
        }
        $status = $data[$changeKey] ? 0 : 1;
        //上线修改
        $status = InPrize::where('id',$mall_id)->update(array($changeKey=>$status));
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
            'id'=>'required|exists:in_prizes,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = InPrize::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = InPrize::whereRaw("id + sort > $current_num")->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = InPrize::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = InPrize::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
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
            'id'=>'required|exists:in_prizes,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $current = InPrize::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = InPrize::whereRaw("id + sort < $current_num")->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = InPrize::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = InPrize::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    function postPtDelete(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:in_prizes,id',
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
}
