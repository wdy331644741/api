<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>月利宝</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="baidu-site-verification" content="iBKXouRC1a"/>
    <link type="text/css" rel="styleSheet" href='../../css/public.css'>
    <link type="text/css" rel="styleSheet" href='../../css/announcement.css'>
    <link href="https://php1.wanglibao.com/css/header-footer.css" rel="stylesheet" type="text/css"/>
    <link href="https://php1.wanglibao.com/css/ylb.css?v=@version@" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="sheader"></div>
<div class="container">
    <div class='section'>
        <div class='media_box clearfix'>
            <div class='notice_list clearfix'>
                <ul class='clearfix'>
                    @foreach($data as $notice)
                        <li class='notice_title'>
                            <a href="../detail/@if($notice->updated_at){!! $notice->id.strtotime($notice->updated_at) !!}@else{!! $notice->id !!}@endif.html" target="_blank"><i></i>{!! mb_substr($notice->title,0,40,'utf-8') !!}</a>
                            <span>{!! date('Y-m-d',strtotime($notice->release_at)) !!}</span>
                        </li>
                    @endforeach
                </ul>
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
