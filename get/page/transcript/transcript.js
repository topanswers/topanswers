define(['markdown','moment','navigation','lightbox2/js/lightbox','mark','domReady!'],function([$,_,CodeMirror],moment){
  var promises = [];
  function threadChat(){
    $('.message').each(function(){
      var id = $(this).data('id'), rid = id;
      function foo(b){
        if(arguments.length!==0) $(this).addClass('t'+id);
        if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message[data-id='+$(this).data('reply-id')+']')[0], true);
        if(arguments.length===0 || b===false) $('.message[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
      }
      foo.call(this);
    });
  }
  $('main').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
  $('.markdown').renderMarkdown(promises);
  Promise.allSettled(promises).then(() => {
    $('.message').addClass('processed');
    $('.post:not(.processed) .when').each(function(){
      $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
      $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
    });
    $('.post').addClass('processed');
  });
  if(!$('html').data('search')){ 
    threadChat();
    $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()); });
  }
  if($('html').data('search')){ $('.markdown').mark($('html').data('search'), { "separateWordSearch": false, "ignoreJoiners": true }); }
  setTimeout(function(){ $('.message:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
});
