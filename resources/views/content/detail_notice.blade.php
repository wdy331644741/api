<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>{!! $title !!}-网利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <meta name="apple-itunes-app" content="app-id=881326898 affiliate-data=, app-argument="/>
    <meta name="keywords" content="网利宝,网利宝官网,p2p金融,p2p理财,p2p网贷,P2p贷款,p2p平台,P2P网贷平台,互联网金融">
    <meta name="description" content="网利宝（www.wanglibao.com）是中国领先的互联网金融p2p理财、p2p网贷平台，网利宝官网提供安全、精准的网贷平台投资及互联网金融服务，大型机构100%本息担保，保障投资人资金安全，P2P投资理财网贷客户首选的互联网金融平台。">
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link type="text/css" rel="styleSheet" href='{!! env("YY_BASE_HOST") !!}/css/cms/public.css'>
    <link type="text/css" rel="styleSheet" href='{!! env("YY_BASE_HOST") !!}/css/cms/news_list.css'>
    <link href="{!! env('YY_BASE_HOST') !!}/css/icon/iconfont.css" rel="stylesheet" type="text/css"/>
    <link href="{!! env('YY_BASE_HOST') !!}/css/pc.css" rel="stylesheet" type="text/css"/>
    <link href="{!! env('YY_BASE_HOST') !!}/css/header-footer.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class="about_sidebar clearfix">
        <div class="about_sidebar_div"><a href="/theme/about/" class="about_sidebar_a">关于我们</a><a href="{!! action('ContentController@getList',['type'=>'report']) !!}" class="about_sidebar_a">媒体报道</a><a href="{!! action('ContentController@getList',['type'=>'trends']) !!}" class="about_sidebar_a">网利动态</a><a href="{!! action('ContentController@getList',['type'=>'notice']) !!}" class="about_sidebar_a active">网站公告</a><a href="{!! action('ContentController@getList',['type'=>'classroom']) !!}" class="">理财课堂</a>
    </div>
    <div class='section'>
        <div class="page_nav">
            <a href="{!! action('ContentController@getList',['type'=>'notice']) !!}" target="_blank">网站公告</a>&gt;公告详情
        </div>
        <div class='media_box detail_box clearfix'>
            <div class='announcement_detail clearfix'>
                <h3>{!! $title !!}</h3>
                <span>{!! date('Y-m-d',strtotime($release_at)) !!}</span>
            </div>
            <div class='medil_detail'>
                {!! $content !!}
            </div>
        </div>
    </div>
</div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="{!! env('YY_BASE_HOST') !!}/js/sea.js"></script>
<script type="text/javascript" src="{!! env('YY_BASE_HOST') !!}/js/sea-config.js"></script>
<script src='{!! env("YY_BASE_HOST") !!}/js/cms/jquery.min.js'></script>
<script src='{!! env("YY_BASE_HOST") !!}/js/cms/news_list.js'></script>
<script type="text/javascript">
    seajs.use(['jquery'],function($){
        seajs.use(['public','template'],function(){
            seajs.use(['header'],function($){

            });
        });
    });
</script>
</body>
</html>
