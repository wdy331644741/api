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
    <div>{{$result['name']}}</div>
    <table class="table" id="item-table">
        <thead>
            <tr>
                <th>id</th>
                <th>名称</th>
                <th>别名</th>
                <th>优先级</th>
                <th>类型</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach($result['items'] as $key => $value)
            <tr>
                <td>{{$value['id']}}<input type="hidden" name="id" value="{{$value['id']}}" /></td>
                <td><input value="{{$value['name']}}" name="name" /></td>
                <td><input value="{{$value['alias_name']}}" name="alias_name" /></td>
                <td><input value="{{$value['prority']}}" name="prority" /></td>
                <td><select name="type">
                    @foreach(config('cms.types') as $type => $type_name)
                        <option @if($type == $value['type']) selected @endif value="{{$type}}">{{$type_name}}</option>
                    @endforeach
                    </select></td>
                <td>
                    <button data-pid="{{$value['id']}}" class="btn btn-sm btn-default action-save"><i class="fa fa-save"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <button data-pid="{{$value['id']}}" class="pull-right btn btn-sm btn-default action-add"><i class="fa fa-plus"></i></button>
</div>
@endsection
@section('js')
<script>
$('#item-table').delegate('.action-save', 'click', function() {
    var trObj=  $(this).closest('tr');
    $.post('{{ url('/cms/item-add') }}', commonSerialize(trObj),  function(res){
        window.location.reload();
    });
});
</script>
@endsection