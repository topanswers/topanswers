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
    <?if(isset($_GET['new'])){?>localStorage.setItem($('html').css('--community')+'.ask',cm.getValue());<?}?>
  }

  $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, 'margin-top': '4px', 'margin-left': '10px' }).attr('tabindex','-1');
  $('#community').change(function(){ window.location = '?community='+$(this).val().toLowerCase(); });
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
  <?if(isset($_GET['new'])){?>$('input[name="title"]').on('input',function(){ localStorage.setItem($('html').css('--community')+'.ask.title',$(this).val()); });<?}?>
  <?if(isset($_GET['new'])&&!isset($_GET['fiddle'])){?>
    if(localStorage.getItem($('html').css('--community')+'.ask')) cm.setValue(localStorage.getItem($('html').css('--community')+'.ask'));
    if(localStorage.getItem($('html').css('--community')+'.ask.title')) $('input[name="title"]').val(localStorage.getItem($('html').css('--community')+'.ask.title'));
    if(localStorage.getItem($('html').css('--community')+'.ask.type')) $('#type').val(localStorage.getItem($('html').css('--community')+'.ask.type'));
  <?}?>
  $('#type').change(function(){
    $('#submit').val('submit '+$(this).val());
    $('input[name="type"').val($(this).children(":selected").text());
    <?if(isset($_GET['new'])){?> localStorage.setItem($('html').css('--community')+'.ask.type',$(this).val());<?}?>
  }).trigger('change');
  $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $('#imageupload').submit(); }; });
  $('#imageupload').submit(function(){
    var d = new FormData($(this)[0]);
    $.ajax({ url: "//post.topanswers.xyz/upload", type: "POST", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
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
    if(cm.somethingSelected()){ cm.replaceSelection('**'+cm.getSelection()+'**'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+2),line:selectionStart.line},{ch:(selectionEnd.ch+2),line:selectionEnd.line});
    }else{ cm.replaceSelection('**bold**'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+2),line:selectionStart.line},{ch:(selectionStart.ch+6),line:selectionStart.line}); }
  });
  $('.button.fa-italic').click(function(){
    var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
    if(cm.somethingSelected()){ cm.replaceSelection('*'+cm.getSelection()+'*'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionEnd.ch+1),line:selectionEnd.line});
    }else{ cm.replaceSelection('*italic*'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+7),line:selectionStart.line}); }
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
  $('#license a').click(function(){ $(this).parent().hide().next().show(); return false; });
  $('[name="license"],[name="codelicense"]').on('change',function(){
    if($(this).children('option:selected').data('versioned')===true){
      $(this).next().css('color','unset').find('input').prop('disabled',false);
    }else{
      $(this).next().css('color','rgb(var(--rgb-mid))').find('input').prop('checked',false).prop('disabled',true);
    }
  }).trigger('change');
  render();
  $('#join').click(function(){
    if(confirm('This will set a cookie to identify your account.\nYou must be 16 or over to join TopAnswers and post your question.')){
      $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){
        alert((r.status)===429?'Rate limit hit, please try again later':responseText);
      }).done(function(r){
        alert('This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n'+r);
        window.location = '/question?community=databases';
      });
    }
  }).click();
  $('#keyboard>span>span').click(function(){
    cm.replaceSelection($(this).text());
    cm.focus();
    return false;
  });

  try{ // tags
    function thread(){
      $('.tag[data-id]').each(function(){
        var id = $(this).data('id'), rid = id;
        function foo(b){
          $(this).addClass('t'+id);
          if(arguments.length===0 || b===false) $('.tag[data-implies='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
        }
        foo.call(this);
      });
    }
    $('.newtag').click(function(){
      $(this).addClass('hide');
      $('#taginput').removeClass('hide').focus();
    });
    $('#taginput').blur(function(){
      $(this).val('').addClass('hide');
      $('.newtag').removeClass('hide');
    });
    $('#taginput').keydown(function(e){
      if(e.which===27){
        $(this).val('').blur();
        return false;
      }
    });
    $('#taginput').on('input',function(e){
      function add(o){
        const i = $('datalist option[data-id='+o.data('implies')+']:not(:disabled)');
        const n = $('#tagbar .tag').filter(function(){ const d = $(this).data('order')||Infinity; return d>o.data('order'); }).first();
        $('<span class="tag" data-id="'+o.data('id')+'"'+(o.data('implies')?' data-implies="'+o.data('implies')+'"':'')+' data-order="'+o.data('order')+'">'+o.val()+'</span>').insertBefore(n);
        $('<input type="hidden" name="tags[]" value="'+o.data('id')+'">').prependTo($('#form'));
        o.prop('disabled',true);
        if(i.length) add(i);
      }
      const t = $(this), o = $('datalist option[value='+t.val()+']:not(:disabled)');
      if(o.length){
        add(o);
        thread();
        $(this).val('').blur();
      }
    });
    $('#tagbar').on('mouseenter','.tag[data-id]',function(){ $('.tag.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave','.tag[data-id]',function(){ $('.thread').removeClass('thread'); });
    $('#tagbar').on('click','.tag[data-id]',function(){
      $('.tag[data-id].t'+$(this).data('id')).each(function(){
        const t = $(this);
        $('datalist option[data-id='+t.data('id')+']:disabled').prop('disabled',false);
        $('input[name="tags[]"][value='+t.data('id')+']').remove();
        t.remove();
      });
    });
    thread();
  }catch(e){ console.error(e); }

});
