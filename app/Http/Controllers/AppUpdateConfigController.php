<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\AppUpdateConfig;
use Illuminate\Support\Facades\DB;

use Validator;

class AppUpdateConfigController extends Controller
{
    //添加升级配置
    public function postAdd(Request $request){
        $validator = Validator::make($request->all(), [
            'update_time' => 'required|date',
            'force' =>'required|in:0,1',
            'description'=>'required',
            'url'=>'required:url',
            'version'=> array('required','regex:/^\d{1,3}\.\d{1,3}\.\d{1,3}$/'),
            'size'=>'required',
            'platform'=>'required|in:1,2,3',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $version_arr = explode('.',$request->version);
        $structure = '';
        for($i=0; $i<count($version_arr); $i++){
            $structure.=str_pad($version_arr[$i],3,'0',STR_PAD_LEFT);
        }
        $appconfig = new AppUpdateConfig();
        $appconfig->update_time = $request->update_time;
        $appconfig->force = $request->force;
        $appconfig->description = $request->description;
        $appconfig->version = $request->version;
        $appconfig->size = $request->size;
        $appconfig->platform = $request->platform;
        $appconfig->structure = intval($structure);
        $appconfig->url = !empty($request->url) ? $request->url : NULL;
        $res = $appconfig->save();
        if($res){
            return $this->outputJson(0,array('insert_id'=>$appconfig->id));
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //删除升级配置
    public function postDel(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:app_update_configs,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = AppUpdateConfig::destroy($request->id);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //修改升级配置
    public function postPut(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:app_update_configs,id',
            'update_time' => 'required|date',
            'force' =>'required|in:0,1',
            'description'=>'required',
            'url'=>'required_if:platform,1|url',
            'version'=>array('required','regex:/^\d{1,3}\.\d{1,3}\.\d{1,3}$/'),
            'size'=>'required',
            'platform'=>'required|in:1,2,3',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $version_arr = explode('.',$request->version);
        $structure = '';
        for($i=0; $i<count($version_arr); $i++){
            $structure.=str_pad($version_arr[$i],3,'0',STR_PAD_LEFT);
        }
        $updata = array(
            'update_time'=>$request->update_time,
            'force' => $request->force,
            'description' => $request->description,
            'version' => $request->version,
            'size' => $request->size,
            'platform' => $request->platform,
            'structure' => $structure,
        );
        $updata['url'] = !empty($request->url) ? $request->url : NULL;
        $res = AppUpdateConfig::where('id',$request->id)->update($updata);
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //查询配置列表通过平台id
    public function getUpdateLog($pid){
        $data = AppUpdateConfig::where('platform',$pid)->orderBy('publish_time','desc')->paginate(20);
        return $this->outputJson(0,$data);
    }

    //查询升级配置详情
    public function getInfo($id){
        $data = AppUpdateConfig::find($id);
        return $this->outputJson(0,$data);
    }

    //启用升级配置
    public function postEnable(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:app_update_configs,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }
        $res = AppUpdateConfig::where('id',$request->id)->update(array('toggle'=>1,'publish_time'=>date('Y-m-d H:i:s')));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }

    //关闭升级配置
    public function postClose(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:app_update_configs,id',
        ]);
        if($validator->fails()){
            return $this->outputJson(10001,array('error_msg'=>$validator->errors()->first()));
        }

        $res = AppUpdateConfig::where('id',$request->id)->update(array('toggle'=>0));
        if($res){
            return $this->outputJson(0);
        }else{
            return $this->outputJson(10002,array('error_msg'=>'Database Error'));
        }
    }
}
