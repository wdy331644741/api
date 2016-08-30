<?php

Route::get('/', function () {
    return 'server is ok';
});

Route::controller('activity', 'ActivityController');
Route::controller('account', 'AccountController');
Route::controller('channel', 'ChannelController');
Route::controller('cms/content','Cms\ContentController');
Route::controller('open','OpenController');
Route::controller('app','AppUpdateConfigController');
Route::controller('notice','Cms\NoticeController');
//测试控制器
Route::controller('test','TestController');
//模板控制
Route::controller('template', 'TemplateController');
//奖品路由
Route::controller('award', 'AwardController');

//触发发奖路由
Route::controller('mc', 'MessageCenterController');

Route::controller('rpc', 'RpcController');

//图片管理
Route::controller('img', 'ImgManageController');
//图片地址转调
Route::get('/enclosures/{url}',function ($url) {
    $img = file_get_contents(base_path()."/storage/images/{$url}");
    return Response::make($img)->header('Content-Type', '');
});
//兑换码
Route::controller('redeem', 'RedeemController');
//贷款提交
Route::controller('loan', 'LoanBookController');
