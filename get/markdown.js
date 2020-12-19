define(['jquery'
       ,'lodash'
       ,'sparkmd5'
       ,'qp/qp'
       ,'tio'
       ,'codemirror/lib/codemirror'
       ,'codemirror/mode/meta','codemirror/addon/mode/overlay','codemirror/addon/runmode/runmode','codemirror/addon/runmode/colorize','codemirror/addon/display/placeholder'
       ,'katex'
       ,'markdown-it','markdown-it-sup','markdown-it-sub','markdown-it-emoji','markdown-it-deflist','markdown-it-footnote','markdown-it-abbr','markdown-it-errorfence','markdown-it-onebox','markdown-it-container'
       ,'markdown-it-inject-linenumbers','markdown-it-object','markdown-it-codefence','markdown-it-codeinput','markdown-it-for-inline','markdown-it-katex','markdownItAnchor','markdownItTocDoneRight'
       ,'clipboard'
       ,'promise-all-settled'
       ,'lightbox2/js/lightbox'
       ,'<?=implode(array_map(function($e){ return 'codemirror/mode/'.$e.'/'.$e; },['apl','clike','clojure','css','erlang','gfm','go','haskell','htmlmixed','javascript','julia','lua'
                                                                                   ,'markdown','mllike','php','powershell','python','shell','sql','stex','vb','xml','diff']),"','")?>'
                                                                                   ],function($,_,SparkMD5,QP,tio,CodeMirror){
  //polyfill
  if (!Promise.allSettled) Promise.allSettled = allSettled;
  
  (function(){
    var md, mdsummary, prefix, rendering, hashes, missing;
    function fiddleMarkdown(){
      var promises = [];
      function addfiddle(o,r){
        var l = o.attr('data-source-line'), f = $(r).replaceAll(o);
        if(l) f.attr('data-source-line',l);
        if(navigator.platform!=='iPhone') f.find('.qp').each(function(){ QP.showPlan($(this).get(0),$(this).attr('data-xml')); });
        f.find('textarea').each(function(){ CodeMirror.fromTextArea($(this)[0],{ viewportMargin: Infinity, mode: 'sql' }); });
        f.find('input').click(function(){
          f.css('opacity',0.5);
          $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
          $.post('https://dbfiddle.uk/run',{ rdbms: f.data('rdbms'), sample: f.data('sample'), statements: JSON.stringify(f.find('.batch>textarea').map(function(){ return $(this).next('.CodeMirror')[0].CodeMirror.getValue(); }).get()) })
              .done(function(r){
            $.get('/dbfiddle?rdbms='+f.data('rdbms')+(f.data('sample')?('&sample='+f.data('sample')):'')+'&fiddle='+r).done(function(r){
              addfiddle(f,r);
            });
          });
        });
        f.find('tfoot a').click(function(){
          const f = $(this).closest('tfoot'), n = f.parent().children('tbody.hide').eq(0);
          n.removeClass('hide');
          f.find('span:first-child').html(n.next().data('showing'));
          if(!n.next().hasClass('hide')){
            f.find('span:last-child').remove();
          }
          return false;
        });
      }
      $(this).find('a[href*="//dbfiddle.uk"]')
             .filter(function(){ return $(this).attr('href').match(/^https?:\/\/dbfiddle\.uk\/?\?.*fiddle=[0-91-f]{32}/)&&$(this).parent().is('p')&&($(this).parent().text()===('<>'+$(this).attr('href'))); })
             .each(function()
      {
        var t = $(this);
        promises.push(Promise.resolve($.get('/dbfiddle?'+t.attr('href').split('?')[1]).done(function(r){
          addfiddle(t.parent(),r);
        })));
      });
      return promises;
    }
  
    function myslugify(s){
      return 'heading-'+(prefix?prefix+'-':'')+s;
    }
  
    function shortcuts(tokens,idx){
      if((tokens[idx+2].type!=='link_close') || (tokens[idx+1].type!=='text')) return;
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.SE') tokens[idx].attrSet('href','https://dba.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.SE') tokens[idx].attrSet('href','https://tex.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://M.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.SE') tokens[idx].attrSet('href','https://unix.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://STATS.SE') tokens[idx].attrSet('href','https://stats.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.TA') tokens[idx].attrSet('href','/databases');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DATABASES.TA') tokens[idx].attrSet('href','/databases');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.TA') tokens[idx].attrSet('href','/tex');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.TA') tokens[idx].attrSet('href','/nix');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://NIX.TA') tokens[idx].attrSet('href','/nix');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.TA') tokens[idx].attrSet('href','/meta');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CODEGOLF.TA') tokens[idx].attrSet('href','/codegolf');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DOTNET.TA') tokens[idx].attrSet('href','/dotnet');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CSHARP.TA') tokens[idx].attrSet('href','/csharp');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://POWERSHELL.TA') tokens[idx].attrSet('href','/powershell');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://POSH.TA') tokens[idx].attrSet('href','/powershell');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://APL.TA') tokens[idx].attrSet('href','/apl');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://WEB.TA') tokens[idx].attrSet('href','/web');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://PHP.TA') tokens[idx].attrSet('href','/php');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://FSHARP.TA') tokens[idx].attrSet('href','/fsharp');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CPLUSPLUS.TA') tokens[idx].attrSet('href','/cplusplus');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CPP.TA') tokens[idx].attrSet('href','/cplusplus');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://PYTHON.TA') tokens[idx].attrSet('href','/python');
    };

    md = require('markdown-it')({ linkify: true, typographer: true })
               .use(require('markdown-it-sup'))
               .use(require('markdown-it-sub'))
               .use(require('markdown-it-emoji'))
               .use(require('markdown-it-deflist'))
               .use(require('markdown-it-footnote'))
               .use(require('markdown-it-abbr'))
               .use(require('markdown-it-errorfence'))
               .use(require('markdown-it-container'), 'quote', {
                 validate: function(params) {
                   return params.trim().match(/^quote ([1-9][0-9]*) (-?[1-9][0-9]*) ([1-9][0-9]*|[0-9a-f]{64}) ([1-9][0-9]{0,2},[1-9][0-9]{0,2},[1-9][0-9]{0,2}) ([1-9][0-9]{0,2},[1-9][0-9]{0,2},[1-9][0-9]{0,2})$/);
                 },
                 render: function (tokens, idx) {
                   var m = tokens[idx].info.trim()
                                      .match(/^quote ([1-9][0-9]*) (-?[1-9][0-9]*) ([1-9][0-9]*|[0-9a-f]{64}) ([1-9][0-9]{0,2},[1-9][0-9]{0,2},[1-9][0-9]{0,2}) ([1-9][0-9]{0,2},[1-9][0-9]{0,2},[1-9][0-9]{0,2})$/);
                   if (tokens[idx].nesting === 1) {
                     return '<div class="quoted-message" style="--rgb-dark: '+m[5]+'; background: rgb('+m[4]+');">\n<img class="icon" src="/'+((m[3].length===64)?'image?hash=':'identicon?id=')+m[3]+'">\n';
                   } else {
                     return '</div>\n';
                   }
                 } })
               .use(require('markdown-it-codefence'))
               .use(require('markdown-it-codefence'), { marker: '~' } )
               .use(require('markdown-it-codeinput'))
               .use(require('markdown-it-container'), 'tio', {
                 validate: function(params) {
                   return params.trim().match(/^tio [a-zA-Z0-9@\/]+$/);
                 },
                 render: function (tokens, idx) {
                   var m = tokens[idx].info.trim().match(/^tio ([a-zA-Z0-9@\/]+)$/);
                   if (tokens[idx].nesting === 1) {
                     return '<div class="tio">'+
                              '<a href="https://tio.run/##'+m[1]+'">tio</a>';
                   } else {
                     return '</div>\n';
                   }
                 } })
               .use(require('markdown-it-onebox'), 'onebox',{
                 render: function (tokens, idx) {
                   if (tokens[idx].nesting === 1) {
                     const content = tokens[idx].content, hash = SparkMD5.hash(content), m = rendering.data('onebox-'+hash), e = ( (typeof m)==='undefined' );
                     hashes.push(hash);
                     if(e) missing.push(content);
                     return '<div class="onebox"><div>'+md.render(e?content:m);
                   } else {
                     return '</div></div>\n';
                   }
                 }
               })
               .use(require('markdown-it-inject-linenumbers'))
               .use(require('markdown-it-object'),'answer',{ validate: function(p) { return p.trim().match(/^answer ([1-9][0-9]*)$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^answer ([1-9][0-9]*)$/);
                 if (tokens[idx].nesting===1) return '<div class="object-answer" data-id="'+m[1]+'">';
                 else return '</div>';
                 } })
               .use(require('markdown-it-object'),'question',{ validate: function(p) { return p.trim().match(/^question ([1-9][0-9]*)$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^question ([1-9][0-9]*)$/);
                 if (tokens[idx].nesting===1) return '<div class="object-question" data-id="'+m[1]+'">';
                 else return '</div>';
                 } })
               .use(require('markdown-it-object'),'youtube',{ validate: function(p) { return p.trim().match(/^youtube [-_0-9a-zA-Z]* [0-9a-f]{64}$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^youtube ([-_0-9a-zA-Z]*) ([0-9a-f]{64})$/);
                 if (tokens[idx].nesting===1) return '<div class="youtube">'+
                                                       '<a href="https://www.youtube.com/watch?v='+m[1]+'">'+
                                                         '<img src="/image?hash='+m[2]+'">'+
                                                       '</a>'+
                                                       '<svg viewBox="-10 -10 120 120">'+
                                                         '<mask id="m"><rect x="0" y="0" width="100" height="100" fill="white"/><polygon points="35,25 35,75 78,50" fill="black"/></mask>'+
                                                         '<circle cx="50" cy="50" r="50" fill="white" fill-opacity="0.8" mask="url(#m)"/>'+
                                                         '<polygon points="35,25 35,75 78,50" fill="black" fill-opacity="0.4"/>'+
                                                       '</svg>';
                 else return '</div>';
                 } })
               .use(require('markdown-it-object'),'xkcd',{ validate: function(p) { return p.trim().match(/^xkcd [1-9][0-9]* [0-9a-f]{64} "[^"]*" "[^"]*"$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^xkcd ([1-9][0-9]*) ([0-9a-f]{64}) "([^"]*)" "([^"]*)"$/);
                 if (tokens[idx].nesting===1) return '<div class="xkcd" title="'+md.utils.escapeHtml(m[4])+'">'+
                                                       '<div><a href="https://xkcd.com/'+m[1]+'">'+md.utils.escapeHtml(m[3])+'</a></div>'+
                                                       '<img src="/image?hash='+m[2]+'">';
                 else return '</div>';
                 } })
               .use(require('markdown-it-object'),'wikipedia',{ validate: function(p) { return p.trim().match(/^wikipedia [0-9a-f]{64} [0-9a-zA-Z]+ "[^"]+" "[^"]+"$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^wikipedia ([0-9a-f]{64}) ([0-9a-zA-Z]+) "([^"]+)" "([^"]+)"$/);
                 if (tokens[idx].nesting===1) return '<div class="wikipedia">'+
                                                       '<a href="https://en.wikipedia.org/wiki/Wikipedia_logo" class="wikipedia-logo"><img src="/image?hash=fda7e63a458c087cb49b2cb452efa8fd8c29e6de3df844e3e4043ba64efc3a11"></a>'+
                                                       '<a href="https://w.wiki/'+m[2]+'">'+md.utils.escapeHtml(m[3])+'</a> '+
                                                       ((m[1]==='0'.repeat(64))?'':'<img src="/image?hash='+m[1]+'">')+
                                                       '<span>'+md.utils.escapeHtml(m[4])+'</span>';
                 else return '</div>';
                 } })
               .use(require('markdownItAnchor'), { slugify: myslugify })
               .use(require('markdownItTocDoneRight'),{ level: [1,2,3], slugify: myslugify })
               .use(require('markdown-it-for-inline'),'url-fix','link_open',shortcuts);

    if(['test','codegolf','apl'].includes($('html').css('--community'))) md.use(require('markdown-it-katex'));
  
    md.renderer.rules.code_block = function(tokens, idx, options, env, slf){
      var token = tokens[idx], langName = rendering.css('--lang-code');
      return '<textarea class="codefence" data-mode="'+langName+'">'+md.utils.escapeHtml(token.content).replace(/\n$/,'')+"</textarea>\n";
    };
  
    md.linkify.tlds('kiwi',true).tlds('xyz',true).tlds('ta',true);
    mdsummary = require('markdown-it')('zero',{ typographer: true }).enable(['replacements','smartquotes','autolink','backticks','entity','escape','linkify','reference','emphasis','link','strikethrough','backticks'])
                      .use(require('markdown-it-sup')).use(require('markdown-it-sub'))
                      .use(require('markdown-it-for-inline'),'url-fix','link_open',shortcuts);
    mdsummary.options.linkify = true;
    mdsummary.linkify.tlds('kiwi',true).tlds('xyz',true);
   
    $.fn.renderMarkdown = function(promises = []){
      this.filter('[data-markdown]').each(function(){
        try{
          var t = $(this);
          rendering = t;
          hashes = [];
          prefix = t.closest('[data-id]').attr('id')||'';
          missing = [];
          t.html(md.render(t.attr('data-markdown'),{ docId: prefix }));
          t.data('onebox',hashes);
          t.data('missing',missing);
          //promises.push(Promise.allSettled(missing));
          t.children('pre').each(function(){ $(this).parent().addClass('cm-s-default'); });
          t.find('table').wrap('<div class="tablewrapper" tabindex="-1">');
          t.find(':not(.quoted-message):not(a)>img').each(function(){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); });
          t.find(':not(sup.footnote-ref)>a:not(.footnote-backref):not([href^="#"])').attr({ 'rel':'nofollow noopener noreferrer', 'target':'_blank' });
          t.find('.object-answer').each(function(){ var t = $(this); promises.push(Promise.resolve($.get('/duplicate?id='+t.attr('data-id')).done(function(r){ t.html(r); }))); });
          t.find('.object-question').each(function(){ var t = $(this); promises.push(Promise.resolve($.get('/questions?one&id='+t.attr('data-id')).done(function(r){ t.html(r); }))); });
          t.find('textarea.codeinput').each(function(){ var t = $(this), cm = CodeMirror.fromTextArea(t[0],{ viewportMargin: Infinity, mode: t.attr('data-mode') }); cm.on('change',_.debounce(function(){ tio(cm.getValue().replace(/\n$/,''),t.attr('data-tio')).then(function(r){ t.siblings('textarea').next()[0].CodeMirror.setValue(r.output); }); },500)); });
          t.find('textarea.codefence').each(function(){
            var t = $(this), cm = CodeMirror.fromTextArea(t[0],{ viewportMargin: Infinity, mode: t.attr('data-mode')||rendering.css('--lang-code'), readOnly: true, lineNumbers: $(this).data('numbers')!==undefined, firstLineNumber: $(this).data('numbers') });
          });
          if(!t.hasClass('noexpander')){
            t.find('.CodeMirror').each(function(){
              var t = $(this), h = t.height();
              if(h>450){
                t.css('max-height','300px').addClass('expandable');
                $('<div class="expander">'+$('html').css('--l_show_more_lines').replace(/%/,t[0].CodeMirror.lineCount().toLocaleString($('html').css('--jslang')))+'</div>').appendTo(t).click(function(){
                  t.animate({ 'max-height': h }, function(){ t.css('max-height',''); }).removeClass('expandable'); $(this).remove();
                });
              }
            });
          }
          promises.push(...fiddleMarkdown.call(this));
        }catch(e){ console.error(e); }
      });
      return this;
    };
  
    $.fn.renderMarkdownSummary = function(){
      this.filter('[data-markdown]').each(function(){
        try{
          rendering = $(this);
          $(this).html(mdsummary.renderInline($(this).attr('data-markdown')).split('\n')[0]);
        }catch(e){ console.error(e); }
      });
      return this;
    };
  
  }());


  return [$,_,CodeMirror];

});
