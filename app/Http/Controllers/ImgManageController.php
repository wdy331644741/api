<?php
namespace App\Http\Controllers;

use App\Models\AppStartpage;
use App\Service\Func;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Models\Banner;
use App\Models\Image;
use App\Models\ImgPosition;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Validator;
use Config;
class ImgManageController extends Controller
{
    //获取某个位置的附件列表
    public function getBannerList(Request $request)
    {
        $where = array();
        $where['can_use'] = 1;
        //位置
        $position = $request['position'];
        if (!empty($position)) {
            $typeID = $this->_getPostion(array('nickname' => $position));
            if(empty($typeID)){
                $where['position'] = $position;
            }else {
                if (isset($typeID[0]['id']) && !empty($typeID[0]['id'])) {
                    $where['position'] = $typeID[0]['id'];
                }
            }
        }
        $data = Banner::where($where)->orderBy('sort','DESC')->paginate(20);
        return $this->outputJson(0,$data);
    }
    //获取某个位置的附件列表
    public function getImgList(Request $request){
        $where = array();
        //位置
        $position = intval($request['position']);
        if(!empty($position)){
            $where['position'] = $position;
        }
        $data = Image::where($where)->orderBy('id','DESC')->paginate(20);
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
        $data['img_path'] = trim($request['img_path']);
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
        $maxSort = Banner::max("sort");
        $data['sort'] = empty($maxSort) ? 1 : $maxSort+1;
        //描述
        $data['desc'] = trim($request['desc']);
        if(empty($data['desc'])){
            return $this->outputJson(PARAMS_ERROR,array('desc'=>'描述不能为空'));
        }
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
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
        $data['type'] = !empty($request['type']) ? $request['type'] : 'default';
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
                    $width = intval($request['width']);
                    $height = intval($request['height']);
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
        $data['http_url'] = Config::get('cms.img_http_url')."/enclosures/".trim($fileName);
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
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
        //图片路径
        $data['img_path'] = trim($request['img_path']);
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
        $data['updated_at'] = date("Y-m-d H:i:s");
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
        $save['updated_at'] = date("Y-m-d H:i:s");
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
            return $this->outputJson(PARAMS_ERROR,array('position'=>'位置名不能为空'));
        }
        //位置名
        $data['nickname'] = trim($request['nickname']);
        if(empty($data['nickname'])){
            return $this->outputJson(PARAMS_ERROR,array('nickname'=>'别名不能为空'));
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
        $where['nickname'] = $request['nickname'];
        $count = ImgPosition::where($where)->count();
        if($count > 0){
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'已经添加过该位置'));
        }
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        $id = ImgPosition::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('insert_id'=>$id));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //位置列表
    public function getPositionList(){
        $list = $this->_getPostion();
        return $this->outputJson(0,array('data'=>$list));
    }
    public function _getPostion($where = array()){
        $list = ImgPosition::where($where)->get()->toArray();
        return $list;
    }
    #TODO 有bug
    //上移
    public function postSortMove(Request $request){
        //获取操作的id
        $id = intval($request->id);
        //取得当前的值
        $data = Banner::where('id',$id)->where('can_use',1)->select('sort')->get()->first()->toArray();
        $value = isset($data['sort']) ? $data['sort'] : 0;
        //取得上一个当前的值
        $data2 = Banner::where('sort','>',$value)->where('can_use',1)->select('id','sort')->orderBy('sort','ASC')->get()->toArray();
        $move = isset($data2[0]['sort']) ? $data2[0]['sort'] : 0;
        $moveID = isset($data2[0]['id']) ? $data2[0]['id'] : 0;
        //开始交换值
        if(!empty($move)){
            Banner::where('id',$id)->update(array('sort'=>$move));
            Banner::where('id',$moveID)->update(array('sort'=>$value));
            return $this->outputJson(0,array('error_msg'=>'成功'));
        }
        return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'排序已经是最前'));
    }
    #TODO 有bug
    //下移
    public function postSortDown(Request $request){
        //获取操作的id
        $id = intval($request->id);
        //取得当前的值
        $data = Banner::where('id',$id)->where('can_use',1)->select('sort')->get()->first()->toArray();
        $value = isset($data['sort']) ? $data['sort'] : 0;
        //取得上一个当前的值
        $data2 = Banner::where('sort','<',$value)->where('can_use',1)->select('id','sort')->orderBy('sort','DESC')->take(1)->get()->toArray();
        $move = isset($data2[0]['sort']) ? $data2[0]['sort'] : 0;
        $moveID = isset($data2[0]['id']) ? $data2[0]['id'] : 0;
        //开始交换值
        if(!empty($move)){
            Banner::where('id',$id)->update(array('sort'=>$move));
            Banner::where('id',$moveID)->update(array('sort'=>$value));
            return $this->outputJson(0,array('error_msg'=>'成功'));
        }
        return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'排序已经是最后'));
    }


    /************************************App启动页**************************************/

    //添加启动页
    public function postAppAdd(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'platform' => 'required|numeric',
            'img1' => 'url',
            'img2' => 'url',
            'img3' => 'url',
            'img4' => 'url',
            'online_time' => 'date|required',
            'offline_time' => 'required|after:online_time'
        ]);

        if($validator->fails()){
            return $this->outputJson('10001',array('error_msg'=>$validator->errors()->first()));
        }

        $insdata = [
            'name' => $request->name,
            'platform' => $request->platform,
            'online_time' => $request->online_time,
            'offline_time' => $request->offline_time,
        ];
        if(isset($request->img1)){
            $insdata['img1'] = $request->img1;
        }
        if(isset($request->img2)){
            $insdata['img2'] = $request->img2;
        }
        if(isset($request->img3)){
            $insdata['img3'] = $request->img3;
        }
        if(isset($request->img4)){
            $insdata['img4'] = $request->img4;
        }

        $id = AppStartpage::insertGetId($insdata);
        return $this->outputJson(0,array('insert_id'=>$id));
    }


    //上线启动页
    public function postAppOnline(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|exists:app_startpages,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = AppStartpage::where('id',$request->id)->update(array('enable'=>1));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //下线启动页
    public function postAppOffline(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|exists:app_startpages,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = AppStartpage::where('id',$request->id)->update(array('enable'=>0));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改启动页
    public function postAppPut(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|exists:app_startpages,id',
            'name' => 'required',
            'platform' => 'required|numeric',
            'img1' => 'url',
            'img2' => 'url',
            'img3' => 'url',
            'img4' => 'url',
            'online_time' => 'date|required',
            'offline_time' => 'required|after:online_time'
        ]);

        if($validator->fails()){
            return $this->outputJson('10001',array('error_msg'=>$validator->errors()->first()));
        }

        $data = [
            'name' => $request->name,
            'platform' => $request->platform,
            'online_time' => $request->online_time,
            'offline_time' => $request->offline_time,
        ];
        if(isset($request->img1)){
            $data['img1'] = $request->img1;
        }
        if(isset($request->img2)){
            $data['img2'] = $request->img2;
        }
        if(isset($request->img3)){
            $data['img3'] = $request->img3;
        }
        if(isset($request->img4)){
            $data['img4'] = $request->img4;
        }
        $res = AppStartpage::where('id',$request->id)->update($data);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //启动页列表
    public function getAppList(Request $request){
        $data = Func::Search($request,new AppStartpage);
        return $this->outputJson(0,$data);
    }
    
    //启动页详情 通过平台id获取
    public function getAppInfoPid($platform){
        $filter = [
            'platform'=>$platform,
            'enable'=>1
        ];
        $newdate = date('Y-m-d H:i:s');
        $data = AppStartpage::where($filter)
            ->where('online_time','<=',$newdate)
            ->where('offline_time','>=',$newdate)
            ->orderByRaw("offline_time - now() ASC")
            ->first();
        return $this->outputJson(0,$data);
    }

    //启动页详情 通过id获取
    public function getAppInfo($id){
        $validator = Validator::make(array('id'=>$id),[
            'id' => 'required|exists:app_startpages,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $data = AppStartpage::find($id);
        return $this->outputJson(0,$data);
    }
}
