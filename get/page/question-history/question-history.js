define(['markdown','moment','diff_match_patch','navigation','lightbox2/js/lightbox','domReady!'],function([$,_,CodeMirror],moment,dmp){
  var dmp = new diff_match_patch();

  function render(){
    var promises = [];
    $(this).find('.diff').each(function(){
      var d = dmp.diff_main($(this).attr('data-from'),$(this).attr('data-to'));
      dmp.diff_cleanupSemantic(d);
      $(this).html(dmp.diff_prettyHtml(d));
    });
    $(this).children('div').children('textarea').each(function(){
      $(this).wrap('<div class="editor-wrapper"></div>');
      var markdown = $(this).parent().next(), cm = CodeMirror.fromTextArea($(this)[0],{ lineWrapping: true, readOnly: true, mode: { name: 'gfm', gitHubSpice: false, taskLists: false, defaultLang: $('html').css('--lang-code') } }), map = [];
      markdown.attr('data-markdown',cm.getValue()).renderMarkdown(promises);
      markdown.find('[data-source-line]').each(function(){ map.push($(this).data('source-line')); });
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) markdown.animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) markdown.animate({ scrollTop: markdown.prop("scrollHeight")-markdown.height() });
        else markdown.find('[data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
    });
    Promise.allSettled(promises).then(() => {
      $(this).find('.post:not(.processed) .when').each(function(){
        $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
        $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
      });
      $(this).find('.post').addClass('processed');
    });
    $(this).addClass('rendered');
  }

  $('#revisions > div').click(function(){
    $('#history-bar span').text($(this).data('rev'));
    $('#history-bar').toggle($(this).data('bar')==='visible');
    $('.active').removeClass('active');
    $(this).addClass('active');
    $('#content > div.'+$(this).attr('id')).addClass('active');
    $('#content > div.'+$(this).attr('id')+':not(.rendered)').each(render);
    history.replaceState(null,null,'#'+$(this).attr('id'));
    return false;
  });
  $('#purge').click(function(){
    $(this).closest('form').submit();
    return false;
  });
  $('#history-bar a.panel').click(function(){
    var panels = $('#content div.panel'), panel = $('#content div.panel.'+$(this).data('panel'));
    $('#history-bar a.panel:not([href])').attr('href','.');
    $(this).removeAttr('href');
    panels.css('visibility','hidden');
    panel.css('visibility','visible');
    return false;
  });
  if($('#revisions > div:target').length){ $('#revisions > div:target').click(); }else{ $('#revisions > div[id^="h"]')[0].click(); }
});
