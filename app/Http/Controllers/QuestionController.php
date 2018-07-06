<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Validator;

class QuestionController extends Controller {

    public function getList() {
        $data = Question::orderBy('id','desc')->orderBy('status', 'desc')->paginate(20);
        return $this->outputJson(0, $data);
    }

    public function postAdd (Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001, $validator->errors()->first());
        }

        $question = new Question();
        $question->title = $request->title;
        $question->content = $request->content;
        $relative = $request->relative;
        if ($relative) {
            $relative = explode(',', $relative);
            foreach($relative as $v) {
                if (!is_numeric($v)) {
                    return $this->outputJson(10001, ['error_msg'=>'Params Error']);
                }
            }
            $relative = json_encode($relative);
        }
        $question->relative = $relative;
        if ($question->save()) {
            return $this->outputJson(0, ['id'=> $question->id]);
        } else {
            return $this->outputJson(10001, ['error_msg'=>'Database Error']);
        }
    }

    public function postUpdate (Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->outputJson(10001, $validator->errors()->first());
        }
        $relative = $request->relative;
        if ($relative) {
            $relative = explode(',', $relative);
            foreach($relative as $v) {
                if (!is_numeric($v)) {
                    return $this->outputJson(10001, ['error_msg'=>'Params Error']);
                }
            }
            $relative = json_encode($relative);
        }
        try {
            $question = Question::findOrFail($request->id);
            $question->title = $request->title;
            $question->content = $request->content;
            $question->relative = $relative;
            if(!$question->save()){
                return $this->outputJson(10001, array('error_msg'=>'Database Error'));
            }
            return $this->outputJson(0);
        }catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>'Database Error'));
        }
    }

    public function getEdit ($id) {
        try {
            $question = Question::findOrFail($id);
            if ($question->save()) {
                return $this->outputJson(0, $question);
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
            $question = Question::findOrFail($request->id);
            $question->status = 1;
            $question->save();
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
            $question = Question::findOrFail($request->id);
            $question->status = 0;
            $question->save();
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
            $question = Question::findOrFail($request->id);
            if($question->delete()){
                return $this->outputJson(0);
            }else{
                return $this->outputJson(10001,array('error_msg'=>'Database Error'));
            }
        } catch (\Exception $e) {
            return $this->outputJson(10001, array('error_msg'=>'Database Error'));
        }
    }
}