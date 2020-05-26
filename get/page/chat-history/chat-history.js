define(['markdown','diff_match_patch','navigation'],function([$,_,CodeMirror],dmp){
  var dmp = new diff_match_patch();
  $('textarea').each(function(){
    var m = $(this).next(), cm = CodeMirror.fromTextArea($(this)[0],{ lineWrapping: true, readOnly: true, mode: { name: 'gfm', gitHubSpice: false, taskLists: false, defaultLang: $('html').css('--lang-code') } });
    m.attr('data-markdown',cm.getValue()).renderMarkdown();
    $(cm.getWrapperElement()).css('grid-area',$(this).data('grid-area'));
  });
  $('.diff').each(function(){
    var d = dmp.diff_main($(this).attr('data-from'),$(this).attr('data-to'));
    dmp.diff_cleanupSemantic(d);
    $(this).html(dmp.diff_prettyHtml(d));
  });
});
