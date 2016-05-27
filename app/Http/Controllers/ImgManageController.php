<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Models\Image;
use App\Models\ImgPosition;

class ImgManageController extends Controller
{
    //列表
    public function postImgList(Request $request){
        $where = array();
        $where['can_use'] = 1;
        //手机端还是pc端
        $type = intval($request['type']);
        if(!empty($type)){
            $where['type'] = $type;
        }
        //位置
        $position = intval($request['position']);
        if(!empty($position)){
            $where['position'] = $position;
        }
        $data = Image::where($where)->orderBy('sort','DESC')->get();
        return $this->outputJson(0,$data);
    }
    //添加
    public function postImgAdd(Request $request){
        //类型1、pc端2、手机端
        $data['type'] = intval($request['type']);
        if(empty($data['type'])){
            return $this->outputJson(PARAMS_ERROR,array('type'=>'类型不能为空'));
        }
        //位置id
        $data['position'] = intval($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置不能为空'));
        }
        //图片
        $path = base_path().'/storage/images/';
        if ($request->hasFile('img_path')) {
            //验证文件上传中是否出错
            if ($request->file('img_path')->isValid()) {
                $mimeTye = $request->file('img_path')->getClientOriginalExtension();
                $types = array('jpg','jpeg','png','bmp');
                if (in_array($mimeTye,$types)) {
                    $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.'.$mimeTye;
                    //保存文件到路径
                    $request->file('img_path')->move($path, $fileName);
                    $file = $path . $fileName;
                } else {
                    return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件格式错误'));
                }
            } else {
                return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件错误'));
            }
        }
        $data['img_path'] = trim($file);
        if(empty($data['img_path'])){
            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件不能为空'));
        }
        //跳转url
        $data['img_url'] = trim($request['img_url']);
        if(empty($data['img_url'])){
            return $this->outputJson(PARAMS_ERROR,array('img_url'=>'跳转的url不能为空'));
        }
        //图片宽度
        $data['width'] = intval($request['width']);
        //图片高度
        $data['height'] = intval($request['height']);
        //开始时间
        $data['start'] = strtotime(trim($request['start']));
        //结束时间
        $data['end'] = strtotime(trim($request['end']));
        //排序
        $data['sort'] = intval($request['sort']);
        //是否可用
        $data['can_use'] = 1;
        //添加时间
        $data['created_at'] = time();
        //修改时间
        $data['updated_at'] = time();
        $id = Image::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('insert_id'=>$id));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //修改
    public function postImgEdit(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要修改的信息'));
        }
        //条件
        $where['id'] = $request['id'];
        //获取要修改的信息
        $info = Image::where($where)->get()->toArray();
        $info = isset($info[0]) ? $info[0] : array();
        //类型1、pc端2、手机端
        $data['type'] = intval($request['type']);
        if(empty($data['type'])){
            return $this->outputJson(PARAMS_ERROR,array('type'=>'类型不能为空'));
        }
        //位置id
        $data['position'] = intval($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置不能为空'));
        }
        //图片
        $path = base_path().'/storage/images/';
        if ($request->hasFile('img_path')) {
            //验证文件上传中是否出错
            if ($request->file('img_path')->isValid()) {
                $mimeTye = $request->file('img_path')->getClientOriginalExtension();
                $types = array('jpg','jpeg','png','bmp');
                if (in_array($mimeTye,$types)) {
                    $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.'.$mimeTye;
                    //保存文件到路径
                    $request->file('img_path')->move($path, $fileName);
                    //删除原来的
                    @unlink($info['img_path']);
                    $file = $path . $fileName;
                } else {
                    return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件格式错误'));
                }
            } else {
                return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件错误'));
            }
        }
        $data['img_path'] = trim($file);
        if(empty($data['img_path'])){
            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件不能为空'));
        }
        //跳转url
        $data['img_url'] = trim($request['img_url']);
        if(empty($data['img_url'])){
            return $this->outputJson(PARAMS_ERROR,array('img_url'=>'跳转的url不能为空'));
        }
        //图片宽度
        $data['width'] = intval($request['width']);
        //图片高度
        $data['height'] = intval($request['height']);
        //开始时间
        $data['start'] = strtotime(trim($request['start']));
        //结束时间
        $data['end'] = strtotime(trim($request['end']));
        //排序
        $data['sort'] = intval($request['sort']);
        //是否可用
        $data['can_use'] = 1;
        //修改时间
        $data['updated_at'] = time();
        $status = Image::where($where)->update($data);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
        }
    }
    //删除
    public function postImgDel(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要删除的信息'));
        }
        //条件
        $where['id'] = $request['id'];
        //是否可用
        $save['can_use'] = 2;
        //修改时间
        $save['updated_at'] = time();
        $status = Image::where($where)->update($save);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    //位置添加
    public function postPositionAdd(Request $request){
        if(empty($request['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置名为空'));
        }
        //位置名
        $data['position'] = trim($request['position']);
        //判断是否添加过
        $where['can_use'] = 1;
        $where['position'] = $request['position'];
        $count = ImgPosition::where($where)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经添加过该位置'));
        }
        //添加时间
        $data['created_at'] = time();
        //修改时间
        $data['updated_at'] = time();
        //是否可用
        $data['can_use'] = 1;
        $id = ImgPosition::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('insert_id'=>$id));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //位置修改
    public function postPositionEdit(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要修改的信息'));
        }
        if(empty($request['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置名为空'));
        }
        //位置名
        $data['position'] = trim($request['position']);
        //修改时间
        $data['updated_at'] = time();
        $where['id'] = $request['id'];
        $status = ImgPosition::where($where)->update($data);

        if($status){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
        }
    }
    //位置删除
    public function postPositionDel(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要删除的信息'));
        }
        $where['id'] = $request['id'];
        //是否可用
        $save['can_use'] = 2;
        //修改时间
        $save['updated_at'] = time();
        $status = ImgPosition::where($where)->update($save);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
}
