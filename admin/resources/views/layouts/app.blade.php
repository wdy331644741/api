<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>运营后台</title>
    <meta name="csrf-token" content="{{csrf_token()}}" />
    <link rel="stylesheet" href="/css/font-awesome.min.css">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    @yield('css')
</head>
<body>
<nav class="navbar navbar-light bg-faded">
    <a class="navbar-brand" href="{{ url('/') }}">管理后台</a>
    <ul class="nav navbar-nav">
        <li class="nav-item active">
            <a class="nav-link" href="#">首页 <span class="sr-only">(current)</span></a>
        </li>
    </ul>
    <form class="form-inline pull-xs-right">
        <input class="form-control" type="text" placeholder="Search">
        <button class="btn btn-success-outline" type="submit">Search</button>
    </form>
</nav>
<div class="container-fluid">
    <div class="row">
        <div id="left-column" class="col-sm-2">
        </div>
        <div class="col-sm-10">
            @yield('content')
        </div>
    </div>
</div>

<!-- JavaScripts -->
<script src="/js/tether.min.js"></script>
<script src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/common.js"></script>
<script>
//csrf保护
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

//生成列表
initList();

</script>

{{-- <script src="{{ elixir('js/app.js') }}"></script> --}}
@yield('js')
</body>
</html>
