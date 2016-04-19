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
//奖品添加
Route::controller('rule', 'RuleController');

Route::post('/award/add', 'AwardController@add');
//奖品映射添加
Route::post('/award/addMap', 'AwardController@addMap');
//奖品修改
Route::post('/award/update', 'AwardController@update');
//奖品获取单个信息
Route::post('/award/getOne', 'AwardController@getOne');
//获取奖品列表
Route::post('/award/getList', 'AwardController@getList');
//奖品删除
Route::post('/award/delete', 'AwardController@delete');
//优惠劵添加
Route::post('/coupon/couponAdd', 'AwardController@couponAdd');
//优惠劵列表
Route::post('/coupon/getCouponList', 'AwardController@getCouponList');
//优惠劵使用情况
Route::post('/coupon/getCouponCodeTotal', 'AwardController@getCouponCodeTotal');
//获取优惠券码
Route::post('/coupon/getCouponCode', 'AwardController@getCouponCode');
//获取优惠券码列表
Route::post('/coupon/getCouponCodeList', 'AwardController@getCouponCodeList');
