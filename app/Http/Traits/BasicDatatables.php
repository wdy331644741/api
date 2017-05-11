<?php

namespace App\Http\Traits;

use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;
use Validator;

trait BasicDatatables{
    public function getDtList(Request $request)
    {
        $items = $this->model->select($this->fileds);

        // 定制化搜索
        if ($request->has('customSearch')) {
            $customSearch = $request->get('customSearch');
            switch ($customSearch['pattern']) {
                case 'like':
                    $items->where($customSearch['name'], 'like', "%{$customSearch['value']}%");
                    break;
                case 'equal':
                case '=':
                    $items->where($customSearch['name'], '=', $customSearch['value']);
                    break;
                case '<=':
                    $items->where($customSearch['name'], '<=', $customSearch['value']);
                    break;
                case '>=':
                    $items->where($customSearch['name'], '>=', $customSearch['value']);
                    break;
                case '!=':
                    $items->where($customSearch['name'], '!=', $customSearch['value']);
                    break;
                case '<':
                    $items->where($customSearch['name'], '<', $customSearch['value']);
                    break;
                case '>':
                    $items->where($customSearch['name'], '>', $customSearch['value']);
                    break;
                default :
                    break;
            }
        }
        // 关联
        if ($request->has('withs')) {
            $withs = $request->get('withs');
            foreach ($withs as $key => $with) {
                $items->with($with);
            }
        }
        /* */
        $res = Datatables::of($items)->make();
        return $this->outputJson(0, $res->getData());
    }

    public function postDtDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
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
