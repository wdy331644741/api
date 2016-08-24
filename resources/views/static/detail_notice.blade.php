<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>月利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <link type="text/css" rel="styleSheet" href='../../css/public.css'>
    <link type="text/css" rel="styleSheet" href='../../css/announcement_detail.css'>
    <link href="https://php1.wanglibao.com/css/header-footer.css" rel="stylesheet" type="text/css"/>
    <link href="https://php1.wanglibao.com/css/ylb.css?v=@version@" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class='section'>
        <div class="page_nav">
            <a href="../list/1.html" target="_blank">网站公告</a>&gt;公告详情
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
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea.js"></script>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea-config.js"></script>
<script src='../../js/jquery.min.js'></script>
<script src='../../js/news_list.js'></script>
<script type="text/javascript">
seajs.use(['jquery'],function($){
    seajs.use(['public', 'template', 'header'],function(){
    });
});
</script>
</body>
</html>
