<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>网利宝安全吗?让您赚的盆满钵满，网利宝与您共同成长_网利宝-网利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <link type="text/css" rel="styleSheet" href='./css/public.css'>
    <link type="text/css" rel="styleSheet" href='./css/help.css'>
    <link href="https://php1.wanglibao.com/css/header-footer.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container help">
    <div class="space-vertical-m"></div>
    <div class="help-menu">
        <ul>
            <li data-target="topic_0" class=""><a href="#">常见问题</a></li>
            @foreach($types as $type)
            <li data-target="topic_{!! $type->id !!}" class=""><a href="#">{!! $type->name !!}</a></li>
            @endforeach
        </ul>
    </div>
    <div class='help-content'>
        <div data-source="topic_0" class="hot-items help-box list-items">
            <h1>常见问题</h1>
            <ul>
                @foreach($oftens as $often)
                <li><a href="#" data-topic="topic_{!! $often->type_id !!}" data-item="content_{!! $often->id !!}">{!! $often->title !!}</a></li>
                @endforeach
            </ul>
        </div>
        @foreach($data as $items)
        <div data-source="topic_{!! $items->id !!}" class="list-items help-box">
            <h1>{!! $items->name !!}</h1>
            <div class="list-container">
                @foreach($items->contents as $item)
                <div data-source="content_{!! $item->id !!}" class="list-item">
                    <div class="list-item-title"><span class="anchor">{!! $item->title !!}</span>
                        <div class="help-arrow"></div>
                    </div>
                    <div class="list-item-content">
                        {!! $item->content !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea.js"></script>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea-config.js"></script>
<script src='./js/jquery.min.js'></script>
<script src='.//js/help.js'></script>
<script type="text/javascript">
    seajs.use(['jquery'],function($){
        seajs.use(['public', 'template', 'header'],function(){
        });
    });
</script>
</body>
</html>
