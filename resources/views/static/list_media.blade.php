<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>媒体报道-网利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <meta name="apple-itunes-app" content="app-id=881326898 affiliate-data=, app-argument="/>
    <meta name="keywords" content="网利宝,网利宝官网,p2p金融,p2p理财,p2p网贷,P2p贷款,p2p平台,P2P网贷平台,互联网金融">
    <meta name="description" content="网利宝（www.wanglibao.com）是中国领先的互联网金融p2p理财、p2p网贷平台，网利宝官网提供安全、精准的网贷平台投资及互联网金融服务，大型机构100%本息担保，保障投资人资金安全，P2P投资理财网贷客户首选的互联网金融平台。">
    <link rel="shortcut icon" href="/favicon.ico"/>
    <link type="text/css" rel="styleSheet" href='/css/cms/public.css'>
    <link type="text/css" rel="styleSheet" href='/css/cms/news_list.css'>
    <link href="/css/icon/iconfont.css" rel="stylesheet" type="text/css"/>
    <link href="/theme/about/css/pc.css" rel="stylesheet" type="text/css"/>
    <link href="/css/header-footer.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class="about_sidebar clearfix">
        <div class="about_sidebar_div"><a href="/theme/about/" class="about_sidebar_a">关于我们</a><a href="/cms/news/list/1.html" class="about_sidebar_a active">媒体报道</a><a href="/cms/dynamic/list/1.html" class="about_sidebar_a">网利动态</a><a href="/cms/notice/list/1.html" class="">网站公告</a><!-- <a href="/hiring" class="">加入我们</a> -->
        </div>
    </div>
    <div class='section'>
    <div class='media_box clearfix'>
        <div class='media_list'>
            @foreach($data as $media)
                <dl class='clearfix'>
                    <dt>
                        <a href="../detail/@if($media->updated_at){!! $media->id.strtotime($media->updated_at) !!}@else{!! $media->id !!}@endif.html" target="_blank">
                            <img src="{!! $media->cover !!}">
                        </a>
                    </dt>
                    <dd class='media_title'>
                        <a href="../detail/@if($media->updated_at){!! $media->id.strtotime($media->updated_at) !!}@else{!! $media->id !!}@endif.html" target="_blank">
                            {!! $media->title !!}
                        </a>
                    </dd>
                    <dd class='media_content postion_dd'>
                        <a href="../detail/@if($media->updated_at){!! $media->id.strtotime($media->updated_at) !!}@else{!! $media->id !!}@endif.html" target="_blank" class='description'>{!! mb_substr(strip_tags($media->content),0,99,'utf-8') !!}</a>
                        <a href="../detail/@if($media->updated_at){!! $media->id.strtotime($media->updated_at) !!}@else{!! $media->id !!}@endif.html" target="_blank" class='link'>[详细介绍]</a>
                    </dd>
                    <dd class="media_time">{!! date('Y-m-d H:i',strtotime($media->release_at)) !!}</dd>
                </dl>
            @endforeach
        </div>
        <div class="pager">
            <ul>
                {!! $lastPage = $data->lastPage(); $currentPage = $data->currentPage() !!}
                {!! $countPage = ($currentPage+5)< $lastPage ? $currentPage+5 : $lastPage-1 !!}
                @if($currentPage <= 1 )
                    <li class="pager-prev disabled">
                        <a href="javascript:void(0)" class="pager-anchor">&lt;</a>
                    </li>
                @else
                    <li class="pager-prev">
                        <a href="./{!! $currentPage-1 !!}.html" class="pager-anchor">&lt;</a>
                    </li>
                @endif
                <li class="pager-page-number @if($currentPage == 1) active @endif">
                    <a href="./1.html" class="pager-anchor">1</a>
                </li>
                @if($currentPage > 2)
                    @for($i=$data->currentPage(); $i<=$countPage; $i++)
                    <li class="pager-page-number @if($currentPage == $i) active @endif">
                        <a href="./{!! $i !!}.html" class="pager-anchor">{!! $i !!}</a>
                    </li>
                    @endfor
                @endif
                @if($lastPage>1)
                    <li class="pager-page-number @if($currentPage == $lastPage) active @endif">
                        <a href="./{!! $lastPage  !!}.html" class="pager-anchor">{!! $lastPage !!}</a>
                    </li>
                @endif
                @if($lastPage == $currentPage)
                    <li class="pager-next disabled">
                        <a href="javascript:void(0)" class="pager-anchor">&gt;</a>
                    </li>
                @else
                    <li class="pager-next ">
                        <a href="./{!! $currentPage+1 !!}.html" class="pager-anchor">&gt;</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
</div>
<div id="sfooter"></div>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea.js"></script>
<script type="text/javascript" src="https://php1.wanglibao.com/js/sea-config.js"></script>
<script type="text/javascript">
seajs.use(['jquery'],function($){
    seajs.use(['public', 'template', 'header'],function(){
    });
});
</script>
</body>
</html>
