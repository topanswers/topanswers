define(['markdown','moment'],function([$,_,CodeMirror],moment){
  jQuery.fn.shuffle = function () {
    var j;
    for (var i = 0; i < this.length; i++) {
      j = Math.floor(Math.random() * this.length);
      $(this[i]).before($(this[j]));
    }
    return this;
  };
  function renderQuestion(){
    $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
    $(this).find('.answers>.bar:first-child+.bar+.bar+.bar').each(function(){
      var t = $(this), h = t.nextAll('.bar').addBack();
      if(h.length>1){
        t.prev().addClass('premore');
        $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
          t.prev().removeClass('premore');
          $(this).prevAll('.bar:hidden').slideDown().end().slideUp();
          return false;
        });
        h.hide();
      }
    });
    $(this).find('.when').each(function(){
      var t = $(this);
      $(this).text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
      $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
    });
  }
  (function(){
    var promises = [];
    $('#qa .post.deleted').remove();
    $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('#qa .post:not(.processed) .question').each(renderQuestion);
      $('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
      $('#qa .post').addClass('processed');
      $('#qa .post').shuffle();
      $('#qa .post').slice(3).remove();
      $('a').attr('target','_blank');
    });
  })();
});
