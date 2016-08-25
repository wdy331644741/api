$(function(){
  $.each($('.description'),function(i,o){
        labelToStr($(o),95);
    });
    $.each($('.contentA'),function(i,o){
        labelToStr($(o),130);
    });
    function labelToStr(obj,counts){
        var str = obj.html();
        if(str.length > counts){
            obj.text($.trim(obj.text()).substring(0,counts)+'...');
        }
    }
});
