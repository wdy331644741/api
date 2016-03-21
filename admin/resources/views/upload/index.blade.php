@extends('layouts.basic')
@section('css')
<style>
html .file-custom::before {content: "浏览"}
html .file-custom::after {content: "选择文件..."}
</style>
@endsection
@section('content')
<label class="file">
    <input type="file" data-url="/upload/image" id="fileupload" data-form-data='{"a": "b"}'  name="img"  />
    <span class="file-custom"></span>
</label>
@endsection
@section('js')
<script src="/js/jquery.ui.widget.js"></script>
<script src="/js/jquery.fileupload.js"></script>
<script>
$('#fileupload').fileupload({
    dataType: 'json',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    done: function (e, data) {
        data.context.text('Upload finished.');
    }
});
</script>
@endsection