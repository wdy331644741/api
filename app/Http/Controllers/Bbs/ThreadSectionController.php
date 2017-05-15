<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use App\Http\Traits\BasicDatatables;
use App\Models\Bbs\ThreadSection;

class ThreadSectionController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','name','description','isuse', 'created_at', 'sort', 'isban' ];
    protected $deleteValidates = [
        'id' => 'required|exists:bbs_thread_sections,id'
    ];
    protected $addValidates = [
        'name' => 'required',
    ];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_thread_sections,id'
    ];

    function __construct() {
        $this->model = new ThreadSection();
    }

    //添加版块
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'name'=>'required|unique:bbs_thread_sections,name',
            'sort'=>'required|integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $section = new ThreadSection();
        $section->name = $request->name;
        $section->isuse = isset($request->isuse) ? $request->isuse : 0;
        $section->isban = isset($request->isban) ? $request->isban : 0;
        $section->sort = isset($request->sort) ? $request->sort : 0;
        $section->description = isset($request->description) ? $request->description : NULL;
        $section->save();
        if($section->id){
            return $this->outputJson(0,array('insert_id'=>$section->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除版块
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = ThreadSection::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //修改版块
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
            'name'=>'unique:bbs_thread_sections,name',
            'sort'=>'integer'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putData = [];
        if(isset($request->name)){
            $putData['name'] = $request->name;
        }
        if(isset($request->isban)){
            $putData['isban'] = $request->isban;
        }
        if(isset($request->isuse)){
            $putData['isuse'] = $request->isuse;
        }
        if(isset($request->sort)){
            $putData['sort'] = $request->sort;
        }
        if(isset($request->description)){
            $putData['description'] = $request->description;
        }
        if (empty($putData)){
            return $this->outputJson(10009,array('error_msg'=>'Not Changed'));
        }
        $res = ThreadSection::where('id',$request->id)->update($putData);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
    //版块列表
    public function getList(){
        $data = ThreadSection::where('isuse',1)->orderBy('sort','asc')->orderBy('id','desc')->get();
        return $this->outputJson(0,$data);
    }

    //开启版块
    public function postOpen(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isuse'=> 1,
        ];
        $res = ThreadSection::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //关闭版块
    public function postClose(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isuse'=> 0,
        ];
        $res = ThreadSection::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //禁止版块普通用户发帖
    public function postBan(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isban'=> 1,
        ];
        $res = ThreadSection::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }


    //解除版块普通用户发帖
    public function postUnBan(Request $request){
        $validator = Validator::make($request->all(), [
            'id'=>'required|exists:bbs_thread_sections,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $putArr = [
            'isban'=> 0,
        ];
        $res = ThreadSection::where('id',$request->id)->update($putArr);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
