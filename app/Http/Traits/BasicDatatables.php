<?php

namespace App\Http\Traits;

use App\Service\Func;
use phpDocumentor\Reflection\Types\Null_;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;
use Validator;
use App\Models\Bbs\Thread;

trait BasicDatatables{
    public function getDtList(Request $request)
    {
        $items = $this->model->select($this->fileds);
        // 只显示删除
        if ($request->has('onlyTrashed')) {
            $items->onlyTrashed()->orderBy('id', 'desc');
        }
        // 定制化搜索
        if ($request->has('customSearch')) {
            $customSearch = $request->get('customSearch');
            foreach ($customSearch as $item){
                switch ($item['pattern']) {
                    case 'like':
                        $items->where($item['name'], 'like', "%{$item['value']}%");
                        break;
                    case 'equal':
                    case '=':
                        $items->where($item['name'], '=', $item['value']);
                        break;
                    case '<=':
                        $items->where($item['name'], '<=', $item['value']);
                        break;
                    case '>=':
                        $items->where($item['name'], '>=', $item['value']);
                        break;
                    case '!=':
                        $items->where($item['name'], '!=', $item['value']);
                        break;
                    case '<':
                        $items->where($item['name'], '<', $item['value']);
                        break;
                    case '>':
                        $items->where($item['name'], '>', $item['value']);
                        break;
                    default :
                        break;
                }
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

    //查询列表
    public function getSearchList(Request $request)
    {

        $path = $request->path();
        $items = $this->model->select($this->fileds);
        // 只显示删除
        if ($request->has('onlyTrashed')) {
            $items->onlyTrashed()->orderBy('id', 'desc');
        }
        // 定制化搜索
        if ($request->has('customSearch')) {
            $customSearch = $request->get('customSearch');
            if(count($customSearch) == count($customSearch,1)){
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
            }else{
                $patternArr = [
                    'equal' => '=',
                    'max_equal' => '>=',
                    'min_equal' => '<=',
                    'min' => '<',
                    'max' => '>',
                    'no_equal' => '<>',
                    'like' => 'LIKE'
                ];
                $str ='';
                foreach ($customSearch as $val){
                    if($val['pattern'] == 'like'){
                        $str .= "AND ".$val['name']." ".$patternArr[$val['pattern']]." '%".$val['value']."%' ";
                    }else{
                        $str .= "AND ".$val['name']." ".$patternArr[$val['pattern']]." '".$val['value']."' ";
                    }
                    $whereStr = substr($str,4);
                }
                $items->whereRaw($whereStr);
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
        $resArr = json_decode(json_encode($res->getData()),true);
        if($path == 'bbs/user/search-list'){
            $newdata = [];
            foreach ($resArr['data'] as $val){
                $num = Thread::where(['user_id'=>$val[1]])->count();
                $val[] = $num;
                $newdata[] = $val;
            }
            $resArr['data'] = $newdata;
            return $this->outputJson(0, $resArr);
        }
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

    public function getDtDetail(Request $request){
        $validator = Validator::make($request->all(), $this->infoValidates);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = $this->model->find($request->id);
        if($res){
            return $this->outputJson(0, $res);
        }else{
            return $this->outputJson(10002,array('error_msg'=>"Database Error"));
        }
    }
}
