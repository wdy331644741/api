<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>{!! $title !!}-网利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <meta name="apple-itunes-app" content="app-id=881326898 affiliate-data=, app-argument="/>
    <meta name="keywords" content="@if($keywords != null) {!! $keywords !!} @endif">
    <meta name="description" content="@if($description != null) {!! $description !!} @endif">
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link type="text/css" rel="styleSheet" href="{!! env('YY_BASE_HOST') !!}/css/cms/public.css">
    <link type="text/css" rel="styleSheet" href="{!! env('YY_BASE_HOST') !!}/css/cms/news_list.css">
    <link href="{!! env('YY_BASE_HOST') !!}/css/icon/iconfont.css" rel="stylesheet" type="text/css"/>
    <link href="{!! env('YY_BASE_HOST') !!}/css/pc.css" rel="stylesheet" type="text/css"/>
    <link href="{!! env('YY_BASE_HOST') !!}/css/header-footer.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class="about_sidebar clearfix">
        <div class="about_sidebar_div">
            <a href="/theme/about/" class="about_sidebar_a">关于我们</a>
            <a href="{!! action('ContentController@getList',['type'=>'report']) !!}" class="about_sidebar_a active">媒体报道</a>
            <a href="{!! action('ContentController@getList',['type'=>'trends']) !!}" class="about_sidebar_a">网利动态</a>
            <a href="{!! action('ContentController@getList',['type'=>'notice']) !!}" class="about_sidebar_a">网站公告</a>
            <a href="{!! action('ContentController@getList',['type'=>'classroom']) !!}" class="about_sidebar_a">网贷课堂</a>
            <a href="/theme/runreports/" class="">运营报告</a>
        </div>
    </div>
    <div class='section'>
        <div class="page_nav">
            <a href="{!! action('ContentController@getList',['type'=>'report']) !!}" target="_blank">媒体报道</a>&gt;报道详情
        </div>
        <div class='media_box detail_box clearfix'>
            <h3>{!! $title !!}</h3>
            <div class="media_coverage_time">{!! date('Y-m-d H:i',strtotime($release_at)) !!}</div>
            <div class='medil_detail'>
                {!! $content !!}
            </div>
        </div>
    </div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="{!! env('YY_BASE_HOST') !!}/js/sea.js"></script>
<script type="text/javascript" src="{!! env('YY_BASE_HOST') !!}/js/sea-config.js"></script>
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
