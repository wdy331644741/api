<?php
Route::get('/', function () {
    return 'server is ok';
});
Route::get('/ip', function () {
    return Request::getClientIp();
});

// 内部调用接口,需要放到上边,先生效。
Route::group(['middleware' => 'internal'], function() {
    // 触发发奖路由
    Route::get('channel/count-json', 'ChannelController@getCountJson'); // 数据获取渠道列表
    Route::post('rpc/inside', 'RpcController@postInside'); // 内部rpc接口 
    Route::controller('mc', 'MessageCenterController');
    Route::controller('wsm', 'WechatSendmsgController'); //微信模板消息发送
});

// admin接口
Route::group(['middleware' => 'admin'], function(){
    Route::controller('activity', 'ActivityController');
    Route::controller('account', 'AccountController');
    Route::controller('channel', 'ChannelController');
    Route::controller('cms/content','Cms\ContentController');
    Route::controller('cms/idiom','Cms\IdiomController');
    Route::controller('app','AppUpdateConfigController');
    Route::controller('notice','Cms\NoticeController');
    // 测试控制器
    Route::controller('test','TestController');
    // 模板控制
    Route::controller('template', 'TemplateController');
    // 奖品路由
    Route::controller('award', 'AwardController'); 
    // 权限管理
    Route::controller('admin', 'AdminController'); 
    // 兑换码
    Route::controller('redeem', 'RedeemController');
    // 贷款提交
    Route::controller('loan', 'LoanBookController');
    // 图片管理
    Route::controller('img', 'ImgManageController');
    // 果粉专享
    Route::controller('mark', 'MarkController');
    // 积分商城
    Route::controller('integral', 'IntegralMallController');
    // 1元夺宝
    Route::controller('one', 'OneYuanController');
    // 红包分享
    Route::controller('money', 'MoneyShareController');
});

// 对外接口
Route::group(['middleware' => 'web'], function() {
    Route::post('rpc', 'RpcController@postIndex');  //对外rpc接口
    Route::post('rpc/index', 'RpcController@postIndex');  //对外rpc接口
    Route::controller('open', 'OpenController'); //微信相关
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
