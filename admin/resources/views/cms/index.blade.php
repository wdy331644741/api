@extends('layouts.app')
@section('css')
@endsection
@section('content')
<div>
    <ol class="breadcrumb">
        <li><a href="/">首页</a></li>
        <li><a href="active">CMS管理</a></li>
    </ol>
</div>
<div class="container-fluid" id="cms-list">
    <table class="table">
        <thead>
            <tr>
                <th>id</th>
                <th>名称</th>
                <th>别名</th>
                <th>是否显示</th>
                <th>当前版本</th>
                <th>最新版本</th>
                <td>条目</td>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result as $key => $value)
            <tr>
                <td>{{$value['id']}}</td>
                <td>{{$value['name']}}</td>
                <td>{{$value['alias_name']}}</td>
                <td>{{$value['enable']}}</td>
                <td>{{$value['cur_version']}}</td>
                <td>{{$value['latest_version']}}</td>
                <td><a href="/cms/item/{{$value['id']}}" >{{count($value['cmsItem'])}}</a></td>
                <td>
                    <button data-pid="{{$value['id']}}" class="btn btn-sm btn-default action-add"><i class="fa fa-edit"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <button data-pid="{{$value['id']}}" class="pull-right btn btn-sm btn-default action-add"><i class="fa fa-plus"></i></button>
</div>
@endsection
@section('js')
@endsection