<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Storage;

class UploadController extends Controller
{

    public function __construct()
    {
        $this->disk = Storage::disk('local');
    }

    //上传图片
    public function postImage(Request $request) {

        if(!$request->hasFile('img')) {
            return $this->output_json('error');
        }

        $extension = $request->file('img')->guessExtension();
        $storagePath = storage_path() . '/app';
        $fileName =  time() . '.' . $extension;
        if($request->file('img')->move($storagePath, $fileName)){
            return $this->output_json('ok');
        }else{
            return $this->output_json('error');
        }
    }

    //上传界面
    public function getImage() {
        return view('upload.index');
    }
}
