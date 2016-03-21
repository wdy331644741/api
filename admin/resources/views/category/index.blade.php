@extends('layouts.app')
@section('css')
@endsection

@section('content')
<div>
    <ol class="breadcrumb">
        <li><a href="/">首页</a></li>
        <li><a href="active">栏目管理</a></li>
    </ol>
</div>
<div class="container-fluid" id="category-list">
    @if (session('success'))
        <div class="alert alert-success">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <table class="table">
        <thead>
            <tr>
                <th>id</th>
                <th>名称</th>
                <th>别名</th>
                <th>是否显示</th>
                <th>优先级</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result as $key => $value)
            <tr data-id="{{$value['id']}}">
                <td>{{$value['id']}}</td>
                <td>{{$value['name']}}</td>
                <td>{{$value['alias_name']}}</td>
                <td>{{$value['is_show']}}</td>
                <td>{{$value['index']}}</td>
                <th>
                    <button data-pid="{{$value['id']}}" class="btn btn-sm btn-default action-add"><i class="fa fa-plus"></i></button>
                    <button data-id="{{$value['id']}}" data-name="{{$value['name']}}" type="button" class="btn btn-sm btn-danger action-delete"><i class="fa fa-remove"></i></button>
                </th>

            </tr>
                @foreach($value['children'] as $key2 => $value2)
                <tr style="margin-left:20px;" data-id="{{$value2['id']}}">
                    <td> -- {{$value2['id']}}</td>
                    <td>{{$value2['name']}}</td>
                    <td>{{$value2['alias_name']}}</td>
                    <td>{{$value['is_show']}}</td>
                    <td>{{$value2['index']}}</td>
                    <th>
                        <button data-id="{{$value2['id']}}" data-name="{{$value2['name']}}" type="button" class="btn btn-sm btn-danger action-delete"><i class="fa fa-remove"></i></button>
                    </th>

                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <div><button data-pid="0" type="button" class="btn btn-sm btn-info action-add pull-right"><i class="fa fa-plus"> 添加</i></button></div>
</div>
@endsection

@section('js')
<script>
//添加类别
$('#category-list').delegate('.action-add', 'click', function(){
    var pid = parseInt($(this).data('pid'));
    var htmlArr = ['<tr class="new-add table-active">'];
        htmlArr.push('<td>-</td>');
        htmlArr.push('<td><input type="hidden" name="parent_id" value="'+pid+'" placeholder="名称" /><input type="text" name="name" placeholder="名称" /></td>');
        htmlArr.push('<td><input type="text" name="alias_name" placeholder="别名" /></td>');
        htmlArr.push('<td><input type="checkbox" name="is_show" value="1" /></td>');
        htmlArr.push('<td><input type="text" name="index" value="0" /></td>');
        htmlArr.push('<td><button data-pid="0" type="button" class="btn btn-sm btn-info action-save"><i class="fa fa-save"> 保存</i></button></td>');
        htmlArr.push('</tr>');
    var html = htmlArr.join('');
    if(pid == 0){
        $('#category-list > table > tbody ').append(html);
    }else{
        $(this).closest('tr').after(html);
    }

//保存添加
}).delegate('.action-save', 'click', function(){
    var trObj=  $(this).closest('tr');
    $.post('{{ url('/category/add') }}', commonSerialize(trObj),  function(res){
        window.location.reload();
    });

//删除
}).delegate('.action-delete', 'click', function(){
    var id = $(this).data('id');
    var name = $(this).data('name');
    if(!window.confirm('确定要删除 '+name+' 吗?')){
       return false;
    }

    $.post('{{ url('/category/delete') }}', {'id': id}, function(res){
        window.location.reload();
    })
});
</script>
@endsection
