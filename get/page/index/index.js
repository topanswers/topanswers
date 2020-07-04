const SITE_DOMAIN = location.hostname;

define(['markdown','navigation'],function([$,_]){
  $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');
  $('#join').click(function(){
    $.post({ url: `//post.${SITE_DOMAIN}/profile`, data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
      location.reload(true);
    }).fail(function(r){
      alert((r.status)===429?'Rate limit hit, please try again later':responseText);
      location.reload(true);
    });
  });
  $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: `//post.${SITE_DOMAIN}/profile`, data: { action: 'link', link: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
  $('#community').change(function(){ window.location = '/'+$(this).find(':selected').attr('data-name'); });
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
  }
  (function(){
    var promises = [];
    $('#qa .post.deleted').remove();
    $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('#qa .post:not(.processed).question').each(renderQuestion);
      //$('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
      $('#qa .post').addClass('processed');
      $('#qa .post').slice(7).remove();
    });
  })();
});
