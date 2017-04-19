<?php

namespace App\Http\Traits;

use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;
use Validator;

trait BasicDatatables{
    public function getDtList()
    {
        $items = $this->model->select($this->fileds);
        $res = Datatables::of($items)->make();
        return $this->outputJson(0, $res->getData());
    }

    public function postDtDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:channels,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = $this->model->destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    public function postDtUpdate(Request $request) {
        $validator = Validator::make($request->all(), $this->updateValidates);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = $this->model->find($request['id'])->update($request->all());
        if($res){
            return $this->outputJson(0, $res);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }

    public function postDtAdd(Request $request) {
        $validator = Validator::make($request->all(), $this->addValidates);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = $this->model->create($request->all());
        if($res){
            return $this->outputJson(0, $res);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }
}
