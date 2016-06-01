<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Models\Banner;
use App\Models\Image;
use App\Models\ImgPosition;

class ImgManageController extends Controller
{
    //获取某个位置的附件列表
    public function postBannerList(Request $request){
        $where = array();
        $where['can_use'] = 1;
        //位置
        $position = intval($request['position']);
        if(!empty($position)){
            $where['position'] = $position;
        }
        $data = Banner::where($where)->orderBy('sort','DESC')->get();
        return $this->outputJson(0,$data);
    }
    //获取某个位置的附件列表
    public function postImgList(Request $request){
        $where = array();
        //位置
        $position = intval($request['position']);
        if(!empty($position)){
            $where['position'] = $position;
        }
        $data = Image::where($where)->orderBy('id','DESC')->get();
        return $this->outputJson(0,$data);
    }
    //banner添加
    public function postBannerAdd(Request $request){
        //位置id
        $data['position'] = intval($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置不能为空'));
        }
        //名称
        $data['name'] = trim($request['name']);
        if(empty($data['name'])){
            return $this->outputJson(PARAMS_ERROR,array('name'=>'名称不能为空'));
        }
        //验证不能重复添加
        $where['name'] = $data['name'];
        $count = Banner::where($where)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经添加过该信息'));
        }
        //图片
        $file = '';
        $path = base_path().'/storage/banners/';
        if ($request->hasFile('img_path')) {
            //验证文件上传中是否出错
            if ($request->file('img_path')->isValid()) {
                $mimeTye = $request->file('img_path')->getClientOriginalExtension();
                $types = array('jpg','jpeg','png','bmp');
                if (in_array($mimeTye,$types)) {
                    //获取位置配置的宽度
                    $size = $this->_getPostion(array('id'=>$data['position']));
                    $width = isset($size[0]['width']) ? $size[0]['width'] : 0;
                    $height = isset($size[0]['height']) ? $size[0]['height'] : 0;
                    $imgSize = getimagesize($request->file('img_path')->getPathname());
                    $imgWidth = isset($imgSize[0]) ? $imgSize[0] : 0;
                    $imgHeight = isset($imgSize[1]) ? $imgSize[1] : 0;
                    if($width != 0){
                        if($width != $imgWidth){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件宽度应该为'.$width));
                        }
                    }
                    if($height != 0){
                        if($height != $imgHeight){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件高度应该为'.$height));
                        }
                    }
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
        //开始时间
        $data['start'] = strtotime(trim($request['start']));
        //结束时间
        $data['end'] = strtotime(trim($request['end']));
        //排序
        $data['sort'] = intval($request['sort']);
        //描述
        $data['desc'] = trim($request['desc']);
        if(empty($data['desc'])){
            return $this->outputJson(PARAMS_ERROR,array('desc'=>'描述不能为空'));
        }
        //添加时间
        $data['created_at'] = time();
        //修改时间
        $data['updated_at'] = time();
        //是否可用
        $data['can_use'] = 1;
        $id = Banner::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('insert_id'=>$id));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //附件添加
    public function postImgAdd(Request $request){
        //位置id
        $data['position'] = intval($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置不能为空'));
        }
        //图片
        $file = '';
        $path = base_path().'/storage/images/';
        if ($request->hasFile('img_path')) {
            //验证文件上传中是否出错
            if ($request->file('img_path')->isValid()) {
                $mimeTye = $request->file('img_path')->getClientOriginalExtension();
                $types = array('jpg','jpeg','png','bmp');
                if (in_array($mimeTye,$types)) {
                    //获取位置配置的宽度
                    $size = $this->_getPostion(array('id'=>$data['position']));
                    $width = isset($size[0]['width']) ? $size[0]['width'] : 0;
                    $height = isset($size[0]['height']) ? $size[0]['height'] : 0;
                    $imgSize = getimagesize($request->file('img_path')->getPathname());
                    $imgWidth = isset($imgSize[0]) ? $imgSize[0] : 0;
                    $imgHeight = isset($imgSize[1]) ? $imgSize[1] : 0;
                    if($width != 0){
                        if($width != $imgWidth){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件宽度应该为'.$width));
                        }
                    }
                    if($height != 0){
                        if($height != $imgHeight){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件高度应该为'.$height));
                        }
                    }
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
        $data['http_url'] = DOMAIN."/enclosures/".trim($fileName);
        //添加时间
        $data['created_at'] = time();
        $id = Image::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('url'=>$data['http_url']));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //修改
    public function postBannerEdit(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要修改的信息'));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要修改的信息不存在'));
        }
        //获取要修改的信息
        $info = Image::where($where)->get()->toArray();
        $info = isset($info[0]) ? $info[0] : array();
        //位置id
        $data['position'] = intval($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置不能为空'));
        }
        //名称
        $data['name'] = trim($request['name']);
        if(empty($data['name'])){
            return $this->outputJson(PARAMS_ERROR,array('name'=>'名称不能为空'));
        }
        //图片
        $path = base_path().'/storage/banners/';
        if ($request->hasFile('img_path')) {
            //验证文件上传中是否出错
            if ($request->file('img_path')->isValid()) {
                $mimeTye = $request->file('img_path')->getClientOriginalExtension();
                $types = array('jpg','jpeg','png','bmp');
                if (in_array($mimeTye,$types)) {
                    //获取位置配置的宽度
                    $size = $this->_getPostion(array('id'=>$data['position']));
                    $width = isset($size[0]['width']) ? $size[0]['width'] : 0;
                    $height = isset($size[0]['height']) ? $size[0]['height'] : 0;
                    $imgSize = getimagesize($request->file('img_path')->getPathname());
                    $imgWidth = isset($imgSize[0]) ? $imgSize[0] : 0;
                    $imgHeight = isset($imgSize[1]) ? $imgSize[1] : 0;
                    if($width != 0){
                        if($width != $imgWidth){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件宽度应该为'.$width));
                        }
                    }
                    if($height != 0){
                        if($height != $imgHeight){
                            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件高度应该为'.$height));
                        }
                    }
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
        //开始时间
        $data['start'] = strtotime(trim($request['start']));
        //结束时间
        $data['end'] = strtotime(trim($request['end']));
        //排序
        $data['sort'] = intval($request['sort']);
        //描述
        $data['desc'] = trim($request['desc']);
        if(empty($data['desc'])){
            return $this->outputJson(PARAMS_ERROR,array('desc'=>'描述不能为空'));
        }
        //修改时间
        $data['updated_at'] = time();
        $status = Banner::where($where)->update($data);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
        }
    }
    //删除
    public function postBannerDel(Request $request){
        if(empty($request['id'])){
            return $this->outputJson(PARAMS_ERROR,array('id'=>'没找到要删除的信息'));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要修改的信息不存在'));
        }
        //是否可用
        $save['can_use'] = 2;
        //修改时间
        $save['updated_at'] = time();
        $status = Banner::where($where)->update($save);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    //位置添加
    public function postPositionAdd(Request $request){
        //位置名
        $data['position'] = trim($request['position']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置名为空'));
        }
        //图片宽度
        $data['width'] = intval($request['width']);
        if(empty($data['width'])){
            return $this->outputJson(PARAMS_ERROR,array('width'=>'宽度不能为空'));
        }
        //图片高度
        $data['height'] = intval($request['height']);
        if(empty($data['position'])){
            return $this->outputJson(PARAMS_ERROR,array('height'=>'高度不能为空'));
        }
        //判断是否添加过
        $where['position'] = $request['position'];
        $count = ImgPosition::where($where)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经添加过该位置'));
        }
        //添加时间
        $data['created_at'] = time();
        $id = ImgPosition::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('insert_id'=>$id));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //位置列表
    public function postPositionList(){
        $list = $this->_getPostion();
        return $this->outputJson(0,array('data'=>$list));
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
}
