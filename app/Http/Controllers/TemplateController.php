<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Cms\Content;
use App\Models\Cms\ContentType;
use Response;
use Storage;

class TemplateController extends Controller
{
    function getIndex() {
        $aliasName = 'report';
        $contentType = ContentType::where(array('alias_name' => $aliasName))->first();    
        if(!$contentType) { 
            return $this->outputJson(10002, array('error_msg' => '类型不存在'));
        }

        $where = array('type_id' => $contentType->id, 'release' => 1);
        $reports = Content::where($where)->get();
        foreach($reports as $report) {
            $res = view('static.news_detail', $report)->render();
            Storage::disk('static')->put("news/detail/{$report->id}.html", $res);
        }
    }

    function getTest() {
        $str = '23456789qwertyupasdfghjkzxcvbnm';
        
    }
}
