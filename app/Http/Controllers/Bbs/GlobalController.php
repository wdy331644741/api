<?php

namespace App\Http\Controllers\Bbs;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Bbs\GlobalConfig;
use App\Http\Traits\BasicDatatables;

class GlobalController extends Controller
{
    use BasicDataTables;
    protected $model = null;
    protected $fileds = ['id','alias_name','vip_level', 'send_max', 'comment_max'];
    protected $deleteValidates = [];
    protected $addValidates = [];
    protected $updateValidates = [
        'id' => 'required|exists:bbs_global_configs,id'
    ];

    function __construct() {
        $this->model = new GlobalConfig();
    }
    
    //获取全局配置
    public function getConfig(){
        $data = GlobalConfig::where('alias_name','global_config')->first();
        return $this->outputJson(0,$data);
    } 
}
