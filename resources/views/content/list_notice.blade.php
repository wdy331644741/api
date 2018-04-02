<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>网站公告-网利宝</title>
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
        <div class="about_sidebar_div">
            {{--<a href="/theme/about/" class="about_sidebar_a">关于我们</a>--}}
            <a href="{!! action('ContentController@getList',['type'=>'report']) !!}" class="about_sidebar_a">媒体报道</a>
            <a href="{!! action('ContentController@getList',['type'=>'trends']) !!}" class="">网利动态</a>
            {{--<a href="{!! action('ContentController@getList',['type'=>'notice']) !!}" class="about_sidebar_a active">网站公告</a>
            <a href="{!! action('ContentController@getList',['type'=>'classroom']) !!}" class="about_sidebar_a">网贷课堂</a>
            <a href="/theme/runreports/" class="">运营报告</a>--}}
        </div>
    </div>
    <div class='section'>
        <div class='media_box clearfix'>
            <div class='notice_list clearfix'>
                <ul class='clearfix'>
                    @foreach($data as $notice)
                        <li class='notice_title'>
                            <a href="{!! action('ContentController@getDetail',['type'=>$type,'id'=>$notice->id]) !!}" target="_blank"><i></i>{!! mb_substr($notice->title,0,40,'utf-8') !!}</a>
                            <span>{!! date('Y-m-d',strtotime($notice->release_at)) !!}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="pager">
                    <ul>
                        {!! $lastPage = $data->lastPage(); $currentPage = $data->currentPage() !!}
                        {!! $countPage = ($currentPage+2)< $lastPage ? $currentPage+2 : $lastPage-1 !!}
                        {!! $smCountPage = ($currentPage+3)< $lastPage ? $currentPage+3 : $lastPage-1 !!}
                        @if($currentPage <= 1 )
                            <li class="pager-prev disabled">
                                <a href="javascript:void(0)" class="pager-anchor">&lt;</a>
                            </li>
                        @else
                            <li class="pager-prev">
                                <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$currentPage-1]) !!}" class="pager-anchor">&lt;</a>
                            </li>
                        @endif
                        <li class="pager-page-number @if($currentPage == 1) active @endif">
                            <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>1]) !!}" class="pager-anchor">1</a>
                        </li>
                        @if($currentPage < 4 && $currentPage >2)
                            @for($i=$currentPage-1; $i<=$smCountPage; $i++)
                                <li class="pager-page-number @if($currentPage == $i) active @endif">
                                    <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$i]) !!}" class="pager-anchor">{!! $i !!}</a>
                                </li>
                            @endfor
                        @elseif($currentPage>=4)
                            @for($i=$data->currentPage()-2; $i<=$countPage; $i++)
                                <li class="pager-page-number @if($currentPage == $i) active @endif">
                                    <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$i]) !!}" class="pager-anchor">{!! $i !!}</a>
                                </li>
                            @endfor
                        @elseif($currentPage == 2)
                            @for($i=$currentPage; $i<=$smCountPage; $i++)
                                <li class="pager-page-number @if($currentPage == $i) active @endif">
                                    <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$i]) !!}" class="pager-anchor">{!! $i !!}</a>
                                </li>
                            @endfor
                        @else
                            @for($i=$currentPage+1; $i<=$smCountPage; $i++)
                                <li class="pager-page-number @if($currentPage == $i) active @endif">
                                    <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$i]) !!}" class="pager-anchor">{!! $i !!}</a>
                                </li>
                            @endfor
                        @endif
                        @if($lastPage>1)
                            <li class="pager-page-number @if($currentPage == $lastPage) active @endif">
                                <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$lastPage]) !!}" class="pager-anchor">{!! $lastPage !!}</a>
                            </li>
                        @endif
                        @if($lastPage == $currentPage)
                            <li class="pager-next disabled">
                                <a href="javascript:void(0)" class="pager-anchor">&gt;</a>
                            </li>
                        @else
                            <li class="pager-next ">
                                <a href="{!! action('ContentController@getList',['type'=>$type,'page'=>$currentPage+1]) !!}" class="pager-anchor">&gt;</a>
                            </li>
                        @endif

                    </ul>
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
