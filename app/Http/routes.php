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
Route::controller('cms','CmsController');
//奖品路由
Route::controller('award', 'AwardController');

//触发发奖路由
Route::controller('mc', 'MessageCenterController');
