$(function(){
  $('.list-container').on('click', '.list-item-title', function(e) {
    var item;
    item = $(this).parent();
    if (item.hasClass('active')) {
      item.removeClass('active');
      return;
    }
    $('.list-item').removeClass('active');
    item.addClass('active');
  });
  $('.list-container').on('click', '.list-item-title', function(e) {
    var item;
    item = $(this).parents('.list-item');
    if (item.hasClass('active')) {
      location.hash = '#' + item.attr('data-source');
    }
  });
  $('.help-menu').on('click', 'li', function(e) {
    var source, tar;
    e.preventDefault();
    if ($(this).hasClass('current')) {
      return;
    }
    $('.help-menu li.current').removeClass('current');
    $(this).addClass('current');
    $('.help-box').removeClass('active');
    $('.list-item').removeClass('active');
    tar = $(this);
    source = $('.help-box[data-source="' + tar.attr('data-target') + '"]');
    return source.addClass('active');
  });
  $('.hot-items').on('click', 'li', function(e) {
    var contentId, item, menu, topic, topicId;
    e.preventDefault();
    topicId = $('a', $(this)).attr('data-topic');
    contentId = $('a', $(this)).attr('data-item');
    topic = $('.help-box[data-source="' + topicId + '"]');
    item = $('.list-item[data-source="' + contentId + '"]', topic);
    menu = $('.help-menu li[data-target="' + topicId + '"]');
    $('.help-menu li.current').removeClass('current');
    menu.addClass('current');
    $('.help-box').removeClass('active');
    $('.list-item').removeClass('active');
    topic.addClass('active');
    return item.addClass('active');
  });
  return $(window).load(function(e) {
    var anchor, item, menu, pattern, source, tar;
    pattern = /#([^#]+)$/ig.exec(location.hash);
    if (pattern && pattern[1]) {
      anchor = pattern[1];
    }
    if (anchor && $('div[data-source=' + anchor + ']').size() > 0) {
      item = $('div[data-source=' + anchor + ']');
      $('.hot-items.active').removeClass('active');
      menu = $('.help-menu li[data-target=' + item.parents('.list-items').addClass('active').attr('data-source') + ']');
      menu.addClass('current');
      return item.addClass('active');
    } else {
      tar = $('.help-menu li:eq(0)');
      tar.addClass('current');
      source = $('.help-box[data-source="' + tar.attr('data-target') + '"]');
      if (!source.hasClass('active')) {
        return source.addClass('active');
      }
    }
  });
});
