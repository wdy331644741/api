<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryQuestion;
use Illuminate\Http\Request;
use Validator;

class CategoryController extends Controller {

    public function getList() {
        $data = Category::select(['id','title', 'icon', 'status'])->with(['CategoryQuestion'=> function ($query){
            $query->select('q_id');
        }])->orderBy('id', 'desc')->get();
        return $this->outputJson(0, $data);
    }

    public function postAdd (Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'icon' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001, ['error_msg'=>$validator->errors()->first()]);
        }
        $flag = false;
        $qids = isset($request->qids) ? $request->qids : '';
        if ($qids) {
            $qids = explode(',', $qids);
            foreach($qids as $v) {
                if (!is_numeric($v)) {
                    return $this->outputJson(10001, ['error_msg'=>'Params Error']);
                }
            }
            $flag = true;
        }
        $category = new Category();
        $category->title = $request->title;
        $category->icon = $request->icon;
        if ($category->save()) {
            if ($flag) {
                $data = array();
                foreach ($qids as $k=>$v) {
                    $data[$k]['q_id'] = $v;
                    $data[$k]['c_id'] = $category->id;
                }
                $cate_question = CategoryQuestion::insert($data);
            }
            return $this->outputJson(0, ['id'=> $category->id]);
        } else {
            return $this->outputJson(10001, ['error_msg'=>'Database Error']);
        }
    }

    public function postUpdate (Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'title' => 'required|max:255',
            'icon' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001, ['error_msg'=>$validator->errors()->first()]);
        }

        $qids = isset($request->qids) ? $request->qids : '';
        if ($qids) {
            $qids = explode(',', $qids);
            $qids = array_map('intval', $qids);
            $flag = true;
        }

        try {
            $category = Category::findOrFail($request->id);
            $category->title = $request->title;
            $category->content = $request->content;
            $category->relative = $request->relative;
            if(!$category->save()){
                return $this->outputJson(10001, array('error_msg'=>'Database Error'));
            }
            return $this->outputJson(0);
        }catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>'Database Error'));
        }
    }

    public function getEdit ($id) {
        try {
            $category = Category::findOrFail($id);
            if ($category->save()) {
                return $this->outputJson(0, $category);
            } else {
                return $this->outputJson(10001, array('error_msg'=>'Database Error'));
            }
        } catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>'Database Error'));
        }
    }

    public function postOnLine (Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        try {
            $category = Category::findOrFail($request->id);
            $category->status = 1;
            $category->save();
            return $this->outputJson(0);
        } catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>$e->getMessage()));
        }
    }

    public function postOffline(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        try {
            $category = Category::findOrFail($request->id);
            $category->status = 0;
            $category->save();
            return $this->outputJson(0);
        } catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>$e->getMessage()));
        }
    }

    public function postDelete(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        try {
            $category = Category::findOrFail($request->id);
            if($category->delete()){
                $cate_question = CategoryQuestion::where(['c_id'=>$request->id])->delete();
                return $this->outputJson(0);
            }else{
                return $this->outputJson(10001,array('error_msg'=>'Database Error'));
            }
        } catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>'Database Error'));
        }
    }
}