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
    //获取某个位置的banner列表
    public function getBannerList(Request $request)
    {
        $where = array();
        //位置
        $position = trim($request->position);
        if(!empty($position)){
            $where['position'] = $position;
        }
        $data = Banner::where('can_use','>=','0')->where('can_use','<=','1')->where($where)->orderBy('can_use','DESC')->orderBy('sort','DESC')->paginate(20);
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
        if(!empty($data)){
            foreach($data as &$item){
                if(!empty($item['file_name'])){
                    $item['http_url'] = Config::get('cms.img_http_url').$item['file_name'];
                    unset($item['file_name']);
                }
            }
        }
        return $this->outputJson(0,$data);
    }
    //banner添加
    public function postBannerAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'position' => 'required|min:1|max:255',
            'img_path' => 'required|min:1|max:255',
            'activity_time' => 'date'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //位置id
        $data['position'] = $request['position'];
        //名称
        $data['name'] = $request['name'];
        //图片
        $data['img_path'] = trim($request['img_path']);
        //跳转url
        $data['url'] = trim($request['url']);
        //是否分享
        $data['short_des'] = trim($request['short_des']);
        //简短说明
        $data['short_desc'] = $request['short_desc'];
        //类型
        $data['type'] = $request['type'];
        //开始时间
        $data['start'] = empty($request['start']) ? null : $request['start'];
        //结束时间
        $data['end'] = empty($request['end']) ? null : $request['end'];
        //排序
        $maxSort = Banner::max("sort");
        $data['sort'] = empty($maxSort) ? 1 : $maxSort+1;
        //描述
        $data['desc'] = trim($request['desc']);
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        //是否可用
        $data['can_use'] = 0;
        $data['tag'] = empty($request['tag']) ? null : $request['tag'];

        //图片活动的时间
        $data['activity_time'] = $request['activity_time'];
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
        if(empty($file)){
            return $this->outputJson(PARAMS_ERROR,array('img_path'=>'文件不能为空'));
        }
        //文件名
        $data['file_name'] = trim($fileName);
        //添加时间
        $data['created_at'] = date("Y-m-d H:i:s");
        $id = Image::insertGetId($data);
        if($id){
            return $this->outputJson(0,array('url'=>Config::get('cms.img_http_url').$data['file_name']));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'添加失败'));
        }
    }
    //banner详情
    public function postBannerInfo(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        $res = Banner::where(array('id', $request['id']))->first();
        return $this->outputJson(0, $res);
    }
    //banner修改
    public function postBannerEdit(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'position' => 'required|min:2|max:255',
            'img_path' => 'required|min:2|max:255',
            'activity_time' => 'date'
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要修改的信息不存在'));
        }
        //位置id
        $data['position'] = $request['position'];
        //名称
        $data['name'] = $request['name'];
        //图片路径
        $data['img_path'] = trim($request['img_path']);
        //跳转url
        $data['url'] = trim($request['url']);
        //开始时间
        $data['start'] = empty($request['start']) ? null : $request['start'];
        //结束时间
        $data['end'] = empty($request['end']) ? null : $request['end'];
        //描述
        $data['desc'] = trim($request['desc']);
        //是否分享
        $data['short_des'] = trim($request['short_des']);
        //简短描述
        $data['short_desc'] = trim($request['short_desc']);
        //类型
        $data['type'] = isset($request['type']) ? $request['type'] : null ;
        //修改时间
        $data['updated_at'] = date("Y-m-d H:i:s");
        //图片活动的时间
        $data['activity_time'] = $request['activity_time'];
        $status = Banner::where($where)->update($data);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'修改成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'修改失败'));
        }
    }
    //banner发布
    public function postBannerRelease(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要发布的信息不存在'));
        }
        //是否可用（已发布状态）
        $save['can_use'] = 1;
        //图片发布时间
        $save['release_time'] = date("Y-m-d H:i:s");
        //修改时间
        $save['updated_at'] = date("Y-m-d H:i:s");
        $status = Banner::where($where)->update($save);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'发布成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'发布失败'));
        }
    }
    //banner下线
    public function postBannerOffline(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要发布的信息不存在'));
        }
        //是否可用（已发布状态）
        $save['can_use'] = 0;
        //修改时间
        $save['updated_at'] = date("Y-m-d H:i:s");
        $status = Banner::where($where)->update($save);
        if($status){
            return $this->outputJson(0,array('error_msg'=>'下线成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'下线失败'));
        }
    }
    //banner删除
    public function postBannerDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //条件
        $where['id'] = $request['id'];
        //判断是否存在
        $count = Banner::where($where)->count();
        if($count < 1){
            return $this->outputJson(DATABASE_ERROR,array('id'=>'要删除的信息不存在'));
        }
        $status = Banner::where($where)->delete();
        if($status){
            return $this->outputJson(0,array('error_msg'=>'删除成功'));
        }else{
            return $this->outputJson(DATABASE_ERROR,array('error_msg'=>'删除失败'));
        }
    }
    //banner上移
    public function postSortUp(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'position' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //获取操作的id
        $id = $request['id'];
        $where['position'] = $request['position'];
        $where['can_use'] = 1;
        //取得当前的值
        $data = Banner::where('id',$id)->where($where)->select('sort')->get()->first()->toArray();
        $value = isset($data['sort']) ? $data['sort'] : 0;
        //取得上一个当前的值
        $data2 = Banner::where('sort','>',$value)->where($where)->select('id','sort')->orderBy('sort','ASC')->get()->toArray();
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
    //banner下移
    public function postSortDown(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'position' => 'required|min:2|max:255',
        ]);
        if($validator->fails()){
            return $this->outputJson(PARAMS_ERROR,array('error_msg'=>$validator->errors()->first()));
        }
        //获取操作的id
        $id = $request['id'];
        $where['position'] = $request['position'];
        $where['can_use'] = 1;
        //取得当前的值
        $data = Banner::where('id',$id)->where($where)->select('sort')->get()->first()->toArray();
        $value = isset($data['sort']) ? $data['sort'] : 0;
        //取得上一个当前的值
        $data2 = Banner::where('sort','<',$value)->where($where)->select('id','sort')->orderBy('sort','DESC')->take(1)->get()->toArray();
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
            'offline_time' => 'required|after:online_time',
            'target_url' => 'url'
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
        if(isset($request->target_url)){
            $insdata['target_url'] = $request->target_url;
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
        $res = AppStartpage::where('id',$request->id)->update(array('enable'=>1,'release_at'=>date('Y-m-d H:i:s')));
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
            'offline_time' => 'required|after:online_time',
            'target_url' => 'url'
        ]);

        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
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
        if(isset($request->target_url)){
            $data['target_url'] = $request->target_url;
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

    //启动页列表 通过平台id获取
    public function getAppInfoPid($platform){
        $filter = [
            'platform'=>$platform,
        ];
        $data = AppStartpage::where($filter)
            ->orderByRaw('id + sort DESC')->get();
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

    //删除启动页
    public function postAppDel(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|exists:app_startpages,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = AppStartpage::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //启动页上移
    public function getAppUp($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:app_startpages,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $now_date = date('Y-m-d H:i:s');
        $current = AppStartpage::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = AppStartpage::where('online_time','<=',$now_date)
            ->where('offline_time','>=',$now_date)
            ->whereRaw("id + sort > $current_num")
            ->orderByRaw('id + sort ASC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = AppStartpage::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = AppStartpage::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //启动页下移
    public function getAppDown($id){
        $validator = Validator::make(array('id'=>$id),[
            'id'=>'required|exists:app_startpages,id'
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $now_date = date('Y-m-d H:i:s');
        $current = AppStartpage::where('id',$id)->first()->toArray();
        $current_num = $current['id'] + $current['sort'];
        $pre = AppStartpage::where('online_time','<=',$now_date)
            ->where('offline_time','>=',$now_date)
            ->whereRaw("id + sort < $current_num")
            ->orderByRaw('id + sort DESC')->first();
        if(!$pre){
            return $this->outputJson(10007,array('error_msg'=>'Cannot Move'));
        }
        $pre_sort = $current_num - $pre['id'];
        $curremt_sort = ($pre['id'] + $pre['sort']) - $current['id'];

        $current_res = AppStartpage::where('id',$id)->update(array('sort'=>$curremt_sort));
        $pre_res = AppStartpage::where('id',$pre['id'])->update(array('sort'=>$pre_sort));
        if($current_res && $pre_res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
