<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', function () {
    return view('welcome');
});

/*
Route::group(['middleware' => 'web'], function () {
    Route::auth();

    Route::get('/home', 'HomeController@index');
});
*/


Route::controller('activity', 'ActivityController');
Route::controller('channel', 'ChannelController');
Route::controller('cms/content','Cms\ContentController');
//奖品路由
Route::controller('award', 'AwardController');

//触发发奖路由
Route::controller('mc', 'MessageCenterController');

//图片管理
Route::controller('img', 'ImgManageController');
//图片地址转调
Route::get('/enclosures/{url}',function ($url) {
    $img = file_get_contents(base_path()."/storage/images/{$url}");
    return Response::make($img)->header('Content-Type', '');
});
