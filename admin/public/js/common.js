/**
 * Created by neil on 16/3/7.
 */

//序列化child中的表单元素
function commonSerialize(jObj) {
    var obj = {};
    jObj.find('input[name]').each(function(){
        if($(this).is('input:checkbox') && !$(this).is('input:checked')){
           return;
        }
        var name = $(this).attr('name');
        var value = $(this).val();
        obj[name] = value;
    })
    return obj;
}

//拉取列表
function initList() {
    $.get('/category/list', function(res){
        var htmlArr = [];

        if('ok' !== res.s){
          alert('获取列表列表失败');
        }

        htmlArr.push('<ul class="list-group">');
        for(var i=0; i < res['r']['length']; i++) {
            var item = res['r'][i];
            htmlArr.push('<li class="list-group-item list-group-item-info"><a href="/'+item['tag']+'">'+item['name']+'</a></li>');

            if(res['r'][i]['children'].length <= 0) {
                continue;
            }
            htmlArr.push('<ul class="list-group">');
            for(var j=0; j < res['r'][i]['children']['length']; j++) {
                htmlArr.push('<li class="list-group-item">'+res['r'][i]['children'][j]['name']+'</li>');
            }
            htmlArr.push('</ul>');
        }
        htmlArr.push('</ul>');
        $('#left-column').html(htmlArr.join(''));
    });
}
