<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\CmsCategory, App\Models\CmsItem, App\Models\CmsValue;

//原子类型
use App\Models\CmsInteger, app\Models\CmsString, App\Models\CmsHtml;

class CmsController extends Controller
{

    public function __construct()
    {
        $this->CmsCategory = new CmsCategory();
    }

    public function getIndex() {
        $res = $this->CmsCategory->with(['items' => function($query){
            $query->orderby('id', 'asc')->orderby('prority', 'desc');
        }])->get();
        return view('cms.index')->withResult($res);
    }

    public function getItem($id) {
        $res = $this->CmsCategory->whereId($id)->with(['items' => function($query){
            $query->orderby('id', 'asc')->orderby('prority', 'desc');
        }])->first();
        if(!$res){
           return $this->output_json('error');
        }
        foreach($res['items'] as &$item){
            $value = $item->value($res['cur_version']);
            $item['value'] = $value;
        }

        return view('cms.item')->withResult($res);
    }

    public function postItemAdd(Request $request) {
        $item = $request->all();
        $cmsItem = new CmsItem();
        var_dump($item);
        $res = $cmsItem->whereId($request->id)->update($item);
        var_dump($res);
    }

}
