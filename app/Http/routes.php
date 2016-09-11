<?php
Route::get('/', function () {
    return 'server is ok';
});
Route::get('/ip', function () {
    return Request::getClientIp();
});

// admin接口
Route::group(['middleware' => 'admin'], function(){
    Route::controller('activity', 'ActivityController');
    Route::controller('account', 'AccountController');
    Route::controller('channel', 'ChannelController');
    Route::controller('cms/content','Cms\ContentController');
    Route::controller('app','AppUpdateConfigController');
    Route::controller('notice','Cms\NoticeController');
    //测试控制器
    Route::controller('test','TestController');
    //模板控制
    Route::controller('template', 'TemplateController');
    //奖品路由
    Route::controller('award', 'AwardController');
    Route::controller('admin', 'AdminController');
    //兑换码
    Route::controller('redeem', 'RedeemController');
    //贷款提交
    Route::controller('loan', 'LoanBookController');
    //图片管理
    Route::controller('img', 'ImgManageController');
});

// 对外接口
Route::group(['middleware' => 'web'], function() {
    Route::controller('rpc', 'RpcController');
    Route::controller('open', 'OpenController');
});


// 内部调用接口
Route::group(['middleware' => 'internal'], function() {
    // 触发发奖路由
    Route::controller('mc', 'MessageCenterController');
});   

//图片地址转调
Route::get('/enclosures/{url}',function ($url) {
    $img = file_get_contents(base_path()."/storage/images/{$url}");
    return Response::make($img)->withHeaders([
        'Content-type' => getMimeTypeByExtension($url),
        'Cache-Control'=> "max-age=" . 60*60*24*30,
        'Expires' => gmdate('r', time()+60*60*24*30),
    ]);
});
Route::controller('media', 'MediaController');
