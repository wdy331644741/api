<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryQuestion;
use Illuminate\Http\Request;
use Validator;

class CategoryController extends Controller {

    public function getList() {
        $data = Category::select(['id','title', 'icon', 'status'])->with('questions')->orderBy('id', 'desc')->paginate(50);
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
        $qids = isset($request->qids) ? $request->qids : '';
        if ($qids) {
            $qids = explode(',', $qids);
            foreach($qids as $v) {
                if (!is_numeric($v)) {
                    return $this->outputJson(10001, ['error_msg'=>'Params Error']);
                }
            }
        }
        $category = new Category();
        $category->title = $request->title;
        $category->icon = $request->icon;
        if ($category->save()) {
            if ($qids) {
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
            foreach($qids as $v) {
                if (!is_numeric($v)) {
                    return $this->outputJson(10001, ['error_msg'=>'Params Error']);
                }
            }
        }

        try {
            $category = Category::findOrFail($request->id);
            $category->title = $request->title;
            $category->icon = $request->icon;
            if(!$category->save()){
                return $this->outputJson(10001, array('error_msg'=>'Database Error'));
            }
            CategoryQuestion::where(['c_id'=>$request->id])->delete();
            if ($qids) {
                $data = array();
                foreach ($qids as $k=>$v) {
                    $data[$k]['q_id'] = $v;
                    $data[$k]['c_id'] = $category->id;
                }
                $cate_question = CategoryQuestion::insert($data);
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

    public function postOffLine(Request $request) {
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