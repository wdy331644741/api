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
    <link type="text/css" rel="styleSheet" href='/css/cms/public.css'>
    <link type="text/css" rel="styleSheet" href='/css/cms/news_list.css'>
    <link href="/css/icon/iconfont.css" rel="stylesheet" type="text/css"/>
    <link href="/css/pc.css" rel="stylesheet" type="text/css"/>
    <link href="/css/header-footer.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class="about_sidebar clearfix">
        <div class="about_sidebar_div"><a href="/theme/about/" class="about_sidebar_a">关于我们</a><a href="/cms/news/list/1.html" class="about_sidebar_a">媒体报道</a><a href="/cms/dynamic/list/1.html" class="about_sidebar_a active">网利动态</a><a href="/cms/notice/list/1.html" class="">网站公告</a><!-- <a href="/hiring" class="">加入我们</a> -->
        </div>
    </div>
    <div class='section'>
        <div class="page_nav">
            <a href="../list/1.html" target="_blank">网利动态</a>&gt;动态详情
        </div>
        <div class='media_box detail_box clearfix'>
            <div class='announcement_detail clearfix'>
                <h3>{!! $title !!}</h3>
                <span>{!! date('Y-m-d H:i',strtotime($release_at)) !!}</span>
            </div>

            <div class='medil_detail'>
                {!! $content !!}
            </div>
        </div>
    </div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="/js/sea.js"></script>
<script type="text/javascript" src="/js/sea-config.js"></script>
<script type="text/javascript">
seajs.use(['jquery'],function($){
    seajs.use(['public', 'template', 'header'],function(){
    });
});
</script>
</body>
</html>
