const ANSWER_IS_NEW = $('html').data('answer-is-new');
const QUESTION_ID = $('html').data('question-id');

define(['markdown','moment','navigation','lightbox2/js/lightbox'],function([$,_,CodeMirror],moment){
  var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, mode: { name: 'gfm', gitHubSpice: false, taskLists: false, defaultLang: $('html').css('--lang-code') }, inputStyle: 'contenteditable', spellcheck: true, extraKeys: {
    Home: "goLineLeft",
    End: "goLineRight",
    'Ctrl-B': function(){ $('.button.fa-bold').click(); },
    'Ctrl-I': function(){ $('.button.fa-italic').click(); },
    'Ctrl-L': function(){ $('.button.fa-link').click(); },
    'Ctrl-Q': function(){ $('.button.fa-quote-left').click(); },
    'Ctrl-K': function(){ $('.button.fa-code').click(); },
    'Ctrl-G': function(){ $('.button.fa-picture-o').click(); },
    'Ctrl-D': function(){ $('.button.fa-database').click(); },
    'Ctrl-O': function(){ $('.button.fa-list-ol').click(); },
    'Ctrl-U': function(){ $('.button.fa-list-ul').click(); },
    'Ctrl-Z': function(){ $('.button.fa-undo').click(); },
    'Ctrl-Y': function(){ $('.button.fa-repeat').click(); }
  } });
  var map;

  $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');

  function render(){
    var promises = [];
    $('#markdown').attr('data-markdown',cm.getValue()).renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('.post:not(.processed) .when').each(function(){
        $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
        $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
      });
      $('.post').addClass('processed');
    });
    map = [];
    $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
    if (ANSWER_IS_NEW) {
      localStorage.setItem($('html').css('--community') + '.answer.' + QUESTION_ID, cm.getValue());
    }
  }

  $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, top: '4px', left: '10px' }).attr('tabindex','-1');
  cm.on('change',_.debounce(function(){
    render();
    $('textarea[name="markdown"]').val(cm.getValue()).show();
  },500));
  cm.on('scroll', _.throttle(function(){
    var rect = cm.getWrapperElement().getBoundingClientRect();
    var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
    if(cm.getScrollInfo().top<10) $('#markdown').animate({ scrollTop: 0 });
    else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#markdown').animate({ scrollTop: $('#markdown').prop("scrollHeight")-$('#markdown').height() });
    else $('#markdown [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
  },200));
  if (ANSWER_IS_NEW) {
    if (localStorage.getItem($('html').css('--community') + '.answer.' + QUESTION_ID)) cm.setValue(localStorage.getItem($('html').css('--community') + '.answer.' + QUESTION_ID));
  }
  $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $('#imageupload').submit(); }; });
  $('#imageupload').submit(function(){
    var d = new FormData($(this)[0]);
    $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
      var selectionStart = cm.getCursor(), selectionEnd = cm.getCursor();
      cm.replaceSelection('!['+d.get('image').name+'](/image?hash='+r+')');
      cm.focus();
      cm.setSelection({ ch: selectionStart.ch, line: selectionStart.line },{ ch: selectionEnd.ch, line: selectionEnd.line });
      $('#imageupload').trigger('reset');
    }).fail(function(r){
      alert(r.status+' '+r.statusText+'\n'+r.responseText);
    });
    return false;
  });
  $('.button.fa-bold').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){ cm.replaceSelection('**'+cm.getSelection()+'**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionEnd.ch+2), line: selectionEnd.line });
    }else{ cm.replaceSelection('**bold**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionStart.ch+6), line: selectionStart.line }); }
  });
  $('.button.fa-italic').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){ cm.replaceSelection('*'+cm.getSelection()+'*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionEnd.ch+1), line: selectionEnd.line });
    }else{ cm.replaceSelection('*italic*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionStart.ch+7), line: selectionStart.line }); }
  });
  $('.button.fa-link').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false), selectionLength = cm.getSelection().length;
    if(cm.somethingSelected()){
      if(cm.getSelection().indexOf('.')===-1){ cm.replaceSelection('['+cm.getSelection()+'](https://topanswers.xyz)'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+3+selectionLength),line:selectionStart.line},{ch:(selectionStart.ch+25+selectionLength),line:selectionStart.line});
      }else{ cm.replaceSelection('[enter link description here]('+cm.getSelection()+')'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+28),line:selectionStart.line}); }
    }else{ cm.replaceSelection('[enter link description here](https://topanswers.xyz)'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+28),line:selectionStart.line}); }
  });
  $('.button.fa-quote-left').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){ cm.replaceSelection('\n> '+cm.getSelection().replace(/\n(?!$)/g,'  \n> ')+'\n'+(((cm.getSelection().length)>=cm.getLine(selectionStart.line).length)?'':'\n')); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+2),line:(selectionEnd.line+1)});
    }else{ cm.setSelection({ch: 0,line:selectionStart.line},{line:selectionStart.line}); cm.replaceSelection('> '+cm.getSelection()+'\n'); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{line:selectionStart.line}); }
  });
  $('.button.fa-code').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){
      if(cm.getSelection().search(/\n(?=$)/g)!==-1||(cm.getCursor().ch!==0&&cm.getSelection().indexOf('\n')!==-1)){ cm.replaceSelection(((cm.getCursor().ch!==0)?'\n':'')+'```\n'+cm.getSelection().replace(/\n(?=$)/g,'')+'\n```\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+3),line:(selectionStart.line+1) });
      }else if(cm.getSelection().length===cm.getLine(selectionStart.line).length){ cm.replaceSelection('```\n'+cm.getSelection()+'\n```'); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:(selectionEnd.ch+3),line:selectionStart.line});
      }else{ cm.replaceSelection('`'+cm.getSelection()+'`'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionEnd.ch+1),line:selectionEnd.line}); }
    }else if(cm.getCursor().ch===0&&cm.getLine(selectionStart.line).length===0){ cm.replaceSelection('```\ncode\n```'); cm.focus(); cm.setSelection({ch:0,line:(selectionStart.line+1)},{ch:4,line:(selectionStart.line+1)}); 
    }else{ cm.replaceSelection('`code`'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+5),line:selectionStart.line}); }
  });
  $('.button.fa-picture-o').click(function(){ $('#uploadfile').click(); cm.focus(); });
  $('.button.fa-database').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){ cm.replaceSelection(cm.getSelection()+'\n\n<> dbfiddle url\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+2)},{ch:15,line:(selectionStart.line+2)});
    }else{ cm.replaceSelection('\n\n<> dbfiddle url\n\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+2)},{ch:15,line:(selectionStart.line+2)}); }
  });
  $('.button.fa-list-ol').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){
      if(selectionStart.ch===0){ cm.replaceSelection('1. '+cm.getSelection()); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:(selectionEnd.ch+3),line:selectionStart.line});
      }else{ cm.replaceSelection('\n1. '+cm.getSelection()); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+3),line:(selectionStart.line+1)}); }
    }else{
      if(selectionStart.ch===0){ cm.replaceSelection('1. ordered list item'); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:20,line:selectionStart.line});
      }else{ cm.replaceSelection('\n1. ordered list item\n\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:20,line:(selectionStart.line+1)}); }
    }
  });
  $('.button.fa-list-ul').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){
      if(selectionStart.ch===0){ cm.replaceSelection('* '+cm.getSelection()); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{ch:(selectionEnd.ch+2),line:selectionStart.line});
      }else{ cm.replaceSelection('\n* '+cm.getSelection()); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+2),line:(selectionStart.line+1)}); }
    }else{
      if(selectionStart.ch===0){ cm.replaceSelection('* unordered list item'); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{ch:21,line:selectionStart.line});
      }else{ cm.replaceSelection('\n* unordered list item\n\n'); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:21,line:(selectionStart.line+1)}); }
    }
  });
  $('.button.fa-undo').click(function(){ cm.undo(); cm.focus(); });
  $('.button.fa-repeat').click(function(){ cm.redo(); cm.focus(); });
  $('[name="license"],[name="codelicense"]').on('change',function(){
    if($(this).children('option:selected').data('versioned')===true){
      $(this).next().css('color','unset').find('input').prop('disabled',false);
    }else{
      $(this).next().css('color','rgb(var(--rgb-mid))').find('input').prop('checked',false).prop('disabled',true);
    }
  }).trigger('change');
  render();
  $('#question .markdown').renderMarkdown();
  $('#license a').click(function(){ $(this).parent().hide().next().show(); return false; });
  $('#keyboard>span>span').click(function(){
    cm.replaceSelection($(this).text());
    cm.focus();
    return false;
  });
});
