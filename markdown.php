<?
include '../lang/markdown.'.($community_language??'en').'.php';
$jslang = $jslang??'en';
?>
<link rel="stylesheet" href="/lib/codemirror/codemirror.css">
<link rel="stylesheet" href="/lib/qp/qp.css">
<?if($community_name==='codegolf'||$community_name==='test'||$community_name==='apl'){?>
  <link rel="stylesheet" href="/lib/katex/katex.min.css">
<?}?>
<style>
  .summary a { color: blue; }
  .summary a:visited { color: purple; }
  .summary code { padding: 0 3px; background: rgb(var(--rgb-light)); border: 1px solid rgb(var(--rgb-mid)); overflow-wrap: break-word; }

  .markdown { overflow: auto; overflow-wrap: break-word; }
  .markdown>pre { white-space: pre; }
  .markdown>:first-child { margin-top: 0; }
  .markdown>:last-child { margin-bottom: 0; }
  .markdown ol, .markdown ul { padding-left: 32px; }
  .markdown li { margin: 3px 0; }
  .markdown a { color: blue; }
  .markdown a:visited { color: purple; }
  .markdown img { max-width: 100%; max-height: 480px; margin: 1px; }
  .markdown hr { background: rgb(var(--rgb-mid)); border: 0; height: 2px; }
  .markdown table { border-collapse: collapse; table-layout: fixed; }
  .markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
  .markdown th { background: rgb(var(--rgb-mid)); }
  .markdown td { background: rgb(var(--rgb-white)); }
  .markdown td, .markdown th { font-family: var(--markdown-table-font-family); font-size: 90%; white-space: pre; border: 1px solid rgb(var(--rgb-black)); padding: 3px 5px; text-align: left; }
  .markdown blockquote { padding: 8px 4px 8px 8px; margin: 16px 0; border-left: 4px solid rgba(var(--rgb-dark),0.6); background: rgba(var(--rgb-dark),0.1); }
  .markdown blockquote>:first-child { margin-top: 0; }
  .markdown blockquote>:last-child { margin-bottom: 0; }
  .markdown code { padding: 0 3px; background: rgb(var(--rgb-light)); border: 1px solid rgb(var(--rgb-mid)); overflow-wrap: break-word; }
  .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 6px; overflow-wrap: unset; }
  .markdown nav ol { counter-reset: list-item; }
  .markdown nav li { display: block; counter-increment: list-item; }
  .markdown nav li:before { content: counters(list-item,'.') ' '; }
  .markdown dt { font-weight: bold; }
  .markdown dd { margin-left: 16px; }
  .markdown dd>* { margin: 5px 0; }
  .markdown .header-anchor { text-decoration: none; }
  .markdown .footnote-item { font-size: smaller; }
  .markdown .footnote-ref { font-size: 70%; }
  .markdown .footnote-ref>a { text-decoration: none; }
  .markdown .quoted-message { border-radius: 2px; padding: 5px; display: grid; grid-template-columns: 22px auto 11px auto; grid-template-rows: auto auto; overflow: auto; }
  .markdown .quoted-message > p { margin: 0; font-size: 10px; grid-column: 1 / span 4; grid-row: 1 / span 1; }
  .markdown .quoted-message > p > a { color: rgb(var(--rgb-dark)); text-decoration: none; }
  .markdown .quoted-message > p em { color: rgb(var(--rgb-dark)); font-style: normal; white-space: nowrap; }
  .markdown .quoted-message > a { text-decoration: none; font-size: 10px; grid-column: 3 / span 1; grid-row: 2 / span 1; margin: 2px 0 0 1px; }
  .markdown .quoted-message > img { grid-row: 2 / span 1; }
  .markdown .quoted-message > blockquote { margin: 0; background: rgb(var(--rgb-white)); padding: 4px; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; grid-column: 2 / span 1; grid-row: 2 / span 1; justify-self: start; }
  .markdown .post { border: 3px solid rgb(var(--rgb-dark)); margin: 0; }
  .markdown .post .tag:hover i { visibility: hidden; }
  .markdown .post .title > a { color: rgb(var(--rgb-black)); }
  .markdown .expandable { overflow: hidden; position: relative; border-bottom: 1px dashed rgb(var(--rgb-mid)); }
  .markdown .expandable>code { overflow: hidden; }
  .markdown .expander { position: absolute; z-index: 1; height: 50px; top: 250px; padding-top: 34px; left: 0; right: 0; font-family: var(--regular-font-family); font-size: 12px; text-align: center; color: rgb(var(--rgb-dark));
                        border: 1px solid rgb(var(--rgb-mid)); border-style: none solid;
                        background: linear-gradient(to bottom, rgba(var(--rgb-light),0) 0%, rgb(var(--rgb-light)) 40%, rgb(var(--rgb-light)) 25%); }
  .markdown .expander:hover { text-decoration: underline; cursor: pointer; }
  .markdown .post .hover { display: none; }
  .markdown .table-of-contents ol { counter-reset: list-item-toc; }
  .markdown .table-of-contents li { display: block; counter-increment: list-item-toc; }
  .markdown .table-of-contents li:before { content: counters(list-item-toc,'.') ' '; }

  .markdown.cm-s-default .cm-comment {color: rgba(0,0,0,0.5);}
  .markdown .cm-s-default .cm-comment {color: rgba(0,0,0,0.5);}

  .markdown .youtube { position: relative; z-index: 0; }
  .markdown .youtube>svg { position: absolute; height: 50%; left: 50%; top: 50%; transform: translate(-50%, -50%); pointer-events: none; }
  .markdown .youtube>a>img { display: block; margin: 0; }
  .markdown .xkcd>div { margin-bottom: 8px; }
  .markdown .xkcd img { max-height: 180px; display: block; }
  .markdown .xkcd>a { display: block; }
  .markdown .wikipedia { display: block; margin: 4px; }
  .markdown .wikipedia>a:nth-of-type(1) { float: right; width: 50px; }
  .markdown .wikipedia>a:nth-of-type(2) { font-size: 18px; display: block; margin-bottom: 10px; }
  .markdown .wikipedia>a:nth-of-type(3) { float: left; margin-top: 4px; margin-right: 12px; margin-bottom: 0; max-width: 30%; }
  .markdown .tio { display: grid; grid-template-columns: auto auto; grid-template-rows: auto auto; gap: 4px; }
  .markdown .tio>div.CodeMirror { grid-area: 1 / 1 / 2 / 3; margin: 0; justify-self: start; }
  .markdown .tio>pre { grid-area: 2 / 1 / 3 / 2; margin: 0; justify-self: start; }
  .markdown .tio>pre>code { padding: 0 2px; }
  .markdown .tio>a { grid-area: 2 / 2 / 3 / 3; font-size: 12px; justify-self: end; align-self: end; }
  .markdown:not(:hover)) .tio>a { visibility: hidden; }
  .markdown .tio .CodeMirror { height: auto; border: 1px solid rgb(var(--rgb-dark)); font-family: var(--monospace-font-family); border-radius: 3px; }
  .markdown .tio .CodeMirror-scroll { margin-bottom: -30px; }

  .dbfiddle { padding: 8px; background: rgb(var(--rgb-light)); border-radius: 3px; }
  .dbfiddle .CodeMirror { height: auto; border: 1px solid rgb(var(--rgb-dark)); font-family: var(--monospace-font-family); border-radius: 3px; }
  .dbfiddle .CodeMirror-scroll { margin-bottom: -30px; }
  .dbfiddle .tablewrapper { margin-top: 8px; }
  .dbfiddle>div { margin-top: 8px; }
  .dbfiddle .batch.hidden { display: none; }
  .dbfiddle .batch { overflow: hidden; min-width: 0; }
  .dbfiddle .error { overflow: auto; white-space: pre; font-family: var(--monospace-font-family); background: rgba(var(--rgb-highlight),0.25); }
  .dbfiddle a { font-size: smaller; }
  .dbfiddle .qp { overflow-x: auto; overflow-y: hidden; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; margin-top: 5px; padding: 2px }
  .dbfiddle .qp-statement-header { display: none; }
  .qp-tt { z-index: 999; box-shadow: 0 0 2px 2px rgb(var(--rgb-white)); }
  .katex-block { overflow: auto; }
</style>
<script src="/lib/markdown-it.js"></script>
<script src="/lib/markdown-it-inject-linenumbers.js"></script>
<script src="/lib/markdown-it-sup.js"></script>
<script src="/lib/markdown-it-sub.js"></script>
<script src="/lib/markdown-it-emoji.js"></script>
<script src="/lib/markdown-it-footnote.js"></script>
<script src="/lib/markdown-it-deflist.js"></script>
<script src="/lib/markdown-it-abbr.js"></script>
<script src="/lib/markdown-it-container.js"></script>
<script src="/lib/markdown-it-object.js"></script>
<script src="/lib/markdown-it-codeinput.js"></script>
<script src="/lib/markdown-it-for-inline.js"></script>
<script src="/lib/markdown-it-container.js"></script>
<?if($community_name==='codegolf'||$community_name==='test'||$community_name==='apl'){?>
  <script src="/lib/katex/katex.min.js"></script>
  <script src="/lib/markdown-it-katex.js"></script>
<?}?>
<script src="/lib/markdownItAnchor.js"></script>
<script src="/lib/markdownItTocDoneRight.js"></script>
<script src="/lib/codemirror/codemirror.js"></script>
<script src="/lib/codemirror/runmode.js"></script>
<script src="/lib/codemirror/colorize.js"></script>
<script src="/lib/codemirror/placeholder.js"></script>
<?foreach(['apl','clike','clojure','css','erlang','gfm','go','haskell','htmlmixed','javascript','julia','markdown','mllike','php','powershell','python','shell','sql','stex','vb'] as $l){?>
  <script src="/lib/codemirror/mode/<?=$l?>.min.js"></script>
<?}?>
<script src="/lib/clipboard.js"></script>
<script src="/lib/qp/qp.js"></script>
<script src="/lib/promise-all-settled.js"></script>
<script>
  //polyfill
  if (!Promise.allSettled) Promise.allSettled = allSettled;

  (function($){
    var md, mdsummary, prefix;
    function fiddleMarkdown(){
      var promises = [];
      function addfiddle(o,r){
        var l = o.attr('data-source-line'), f = $(r).replaceAll(o);
        if(l) f.attr('data-source-line',l);
        f.find('.qp').each(function(){ QP.showPlan($(this).get(0),$(this).attr('data-xml')); });
        f.find('textarea').each(function(){ CodeMirror.fromTextArea($(this)[0],{ viewportMargin: Infinity, mode: 'sql' }); });
        f.find('input').click(function(){
          f.css('opacity',0.5);
          $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
          $.post('https://dbfiddle.uk/run',{ rdbms: f.data('rdbms'), statements: JSON.stringify(f.find('.batch>textarea').map(function(){ return $(this).next('.CodeMirror')[0].CodeMirror.getValue(); }).get()) })
              .done(function(r){
            $.get('/dbfiddle?rdbms='+f.data('rdbms')+'&fiddle='+r).done(function(r){
              addfiddle(f,r);
            });
          });
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
    };

    md = window.markdownIt({ linkify: true
      , highlight: (code, lang) => {
        let lastStyle
        let sDom = ''
        CodeMirror.runMode(code, lang||'<?=$community_code_language?>', (token, style) => {
          if (lastStyle !== style) {
            if (lastStyle !== undefined) sDom += '</span>'
            if (style !== undefined) sDom += '<span class="cm-'+style+'">'
            lastStyle = style
          }
          sDom += md.utils.escapeHtml(token)
        })
        if (lastStyle !== undefined) sDom += '</span>'
        return sDom
      }
      
       })
               .use(window.markdownitSup)
               .use(window.markdownitSub)
               .use(window.markdownitEmoji)
               .use(window.markdownitDeflist)
               .use(window.markdownitFootnote)
               .use(window.markdownitAbbr)
               .use(window.markdownitContainer, 'quote', {
                 validate: function(params) {
                   return params.trim().match(/^quote ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+)$/);
                 },
                 render: function (tokens, idx) {
                   var m = tokens[idx].info.trim().match(/^quote ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+)$/);
                   if (tokens[idx].nesting === 1) {
                     return '<div class="quoted-message" style="--rgb-dark: '+m[5]+'; background: rgb('+m[4]+');">\n<img class="icon" src="/identicon?id='+m[3]+'">\n<a class="fa fa-fw fa-link" style="color: rgb('+m[5]+');" href="/transcript?room='+m[1]+'&id='+m[2]+'#c'+m[2]+'"></a>\n';
                   } else {
                     return '</div>\n';
                   }
                 } })
               .use(window.markdownitCodeInput)
             <?if($community_name==='test'||$community_name==='apl'){?>
               .use(window.markdownitContainer, 'tio', {
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
             <?}?>
               .use(window.markdownitInjectLinenumbers)
               .use(window.markdownitObject,'answer',{ validate: function(p) { return p.trim().match(/^answer ([1-9][0-9]*)$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^answer ([1-9][0-9]*)$/);
                 if (tokens[idx].nesting===1) return '<div class="object-answer" data-id="'+m[1]+'">';
                 else return '</div>';
                 } })
               .use(window.markdownitObject,'question',{ validate: function(p) { return p.trim().match(/^question ([1-9][0-9]*)$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^question ([1-9][0-9]*)$/);
                 if (tokens[idx].nesting===1) return '<div class="object-question" data-id="'+m[1]+'">';
                 else return '</div>';
                 } })
               .use(window.markdownitObject,'youtube',{ validate: function(p) { return p.trim().match(/^youtube [-_0-9a-zA-Z]* [0-9a-f]{64}$/); }, render: function (tokens,idx){
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
               .use(window.markdownitObject,'xkcd',{ validate: function(p) { return p.trim().match(/^xkcd [1-9][0-9]* [0-9a-f]{64} "[^"]*" "[^"]*"$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^xkcd ([1-9][0-9]*) ([0-9a-f]{64}) "([^"]*)" "([^"]*)"$/);
                 if (tokens[idx].nesting===1) return '<div class="xkcd" title="'+md.utils.escapeHtml(m[4])+'">'+
                                                       '<div><a href="https://xkcd.com/'+m[1]+'">'+md.utils.escapeHtml(m[3])+'</a></div>'+
                                                       '<img src="/image?hash='+m[2]+'">';
                 else return '</div>';
                 } })
               .use(window.markdownitObject,'wikipedia',{ validate: function(p) { return p.trim().match(/^wikipedia [0-9a-f]{64} [0-9a-zA-Z]+ "[^"]+" "[^"]+"$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^wikipedia ([0-9a-f]{64}) ([0-9a-zA-Z]+) "([^"]+)" "([^"]+)"$/);
                 if (tokens[idx].nesting===1) return '<div class="wikipedia">'+
                                                       '<a href="https://en.wikipedia.org/wiki/Wikipedia_logo" class="wikipedia-logo"><img src="/image?hash=fda7e63a458c087cb49b2cb452efa8fd8c29e6de3df844e3e4043ba64efc3a11"></a>'+
                                                       '<a href="https://w.wiki/'+m[2]+'">'+md.utils.escapeHtml(m[3])+'</a> '+
                                                       ((m[1]==='0'.repeat(64))?'':'<img src="/image?hash='+m[1]+'">')+
                                                       '<span>'+md.utils.escapeHtml(m[4])+'</span>';
                 else return '</div>';
                 } })
               <?if($community_name==='codegolf'||$community_name==='test'||$community_name==='apl'){?>.use(window.markdownitKatex)<?}?>
               .use(window.markdownItAnchor, { slugify: myslugify })
               .use(window.markdownItTocDoneRight,{ level: [1,2,3], slugify: myslugify })
               .use(window.markdownitForInline,'url-fix','link_open',shortcuts);

    md.renderer.rules.code_block = function(tokens, idx, options, env, slf){
      var token = tokens[idx], langName = '<?=$community_code_language?>', highlighted, i, tmpAttrs, tmpToken;

      if (options.highlight) {
        highlighted = options.highlight(token.content, langName) || md.utils.escapeHtml(token.content);
      } else {
        highlighted = md.utils.escapeHtml(token.content);
      }

      if (highlighted.indexOf('<pre') === 0) return highlighted + '\n';
      return '<pre><code' + slf.renderAttrs(token) + '>' + highlighted + '</code></pre>\n';
    };

    md.linkify.tlds('kiwi',true).tlds('xyz',true).tlds('ta',true);
    mdsummary = window.markdownIt('zero').enable(['replacements','smartquotes','autolink','backticks','entity','escape','linkify','reference','emphasis','link','strikethrough','backticks'])
                      .use(window.markdownitSup).use(window.markdownitSub)
                      .use(window.markdownitForInline,'url-fix','link_open',shortcuts);
    mdsummary.options.linkify = true;
    mdsummary.linkify.tlds('kiwi',true).tlds('xyz',true);
 
    $.fn.renderMarkdown = function(promises = []){
      this.filter('[data-markdown]').each(function(){
        var t = $(this), m = t.attr('data-markdown');
        prefix = t.closest('[data-id]').attr('id')||'';
        t.html(md.render(m,{ docId: prefix }));
        t.children('pre').each(function(){ $(this).parent().addClass('cm-s-default'); });
        t.find('table').wrap('<div class="tablewrapper" tabindex="-1">');
        t.find(':not(.quoted-message):not(a)>img').each(function(){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); });
        t.find(':not(sup.footnote-ref)>a:not(.footnote-backref):not([href^="#"])').attr({ 'rel':'nofollow', 'target':'_blank' });
        t.find('.object-answer').each(function(){ var t = $(this); promises.push(Promise.resolve($.get('/duplicate?id='+t.attr('data-id')).done(function(r){ t.html(r); }))); });
        t.find('.object-question').each(function(){ var t = $(this); promises.push(Promise.resolve($.get('/questions?one&id='+t.attr('data-id')).done(function(r){ t.html(r); }))); });
      <?if($community_name==='test'||$community_name==='apl'){?>
        t.find('textarea.code').each(function(){ var t = $(this), cm = CodeMirror.fromTextArea(t[0],{ viewportMargin: Infinity, mode: 'apl' }); cm.on('change',_.debounce(function(){ tioRequest(cm.getValue().trim()).then(function(r){ t.siblings('pre').children('code').text(r.output); }); },500)); });
      <?}?>
        if(!t.hasClass('noexpander')){
          t.children('pre').each(function(){
            var t = $(this), h = t.height();
            if(h>450){
              t.innerHeight(300).addClass('expandable');
              $('<div class="expander"><?=$l_showmorelines?></div>'.replace(/%/,Math.round((h-300)/14.4).toLocaleString('<?=$jslang?>'))).prependTo(t).click(function(){ t.animate({ height: h }, function(){ t.height(''); }).removeClass('expandable'); $(this).remove(); });
            }
          });
        }
        promises.push(...fiddleMarkdown.call(this));
      });
      return this;
    };

    $.fn.renderMarkdownSummary = function(){
      this.filter('[data-markdown]').each(function(){
        $(this).html(mdsummary.renderInline($(this).attr('data-markdown')).split('\n')[0]);
      });
      return this;
    };

  }(jQuery));
</script>
