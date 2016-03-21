<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->Category = new Category();
    }

    //首页
    public function getIndex(){
        $res = $this->Category->with(['children' => function($query){
            $query-> orderby('index','desc')-> orderby('id','asc');
        }])->whereParentId(0)->orderby('index','desc')->orderby('id','asc')->get();
        return view('category.index', ['result' => $res]);
    }

    //添加
    public function postAdd(Request $request) {

        $this->validate($request, [
            'name' => 'required',
            'alias_name' => 'required'
        ]);
        $inputs = $request->all();

        if($this->Category->insert($inputs)){
           return $this->output_json('ok');
        }else{
           return $this->output_json('db_error', array('msg' => '数据插入失败.'));
        }
    }

    //删除
    public function postDelete(Request $request) {
        $this->validate($request, [
            'id' => 'required'
        ]);
        $id = $request->input('id');
        if($this->Category->whereId($id)->delete()){
            return $this->output_json('ok');
        }else{
            return $this->output_json('error', array('msg' => '数据已删除'));
        }
    }

    //生成列表
    public function getList() {
        $res = $this->Category->with(['children' => function($query){
            $query->where('is_show', true)->orderby('index','desc')-> orderby('id','asc');
        }])->whereParentId(0)->where('is_show', true)->orderby('index','desc')->orderby('id','asc')->get();

        return $this->output_json('ok', $res);
    }

}
