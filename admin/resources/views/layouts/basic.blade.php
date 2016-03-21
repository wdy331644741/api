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
@yield('content')
<!-- JavaScripts -->
<script src="/js/tether.min.js"></script>
<script src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/common.js"></script>
{{-- <script src="{{ elixir('js/app.js') }}"></script> --}}
@yield('js')
</body>
</html>