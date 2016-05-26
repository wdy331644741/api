<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Cms\Content;
use App\Models\Cms\Banner;

use App\Http\Requests;

class CmsController extends Controller
{
    //banner图列表
    public function getBannerList(){
        $data = Banner::orderBy('sort','ASC')->get();
        return $this->outputJson(0,$data);
    }
    //添加banner
    public function postBannerAdd(Request $request){

    }



}
