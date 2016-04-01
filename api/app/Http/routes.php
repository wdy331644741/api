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

Route::post('/award/add', 'AwardController@add');

Route::post('/award/update', 'AwardController@update');

Route::post('/award/getOne', 'AwardController@getOne');

Route::post('/award/getList', 'AwardController@getList');

