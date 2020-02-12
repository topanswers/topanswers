<link rel="stylesheet" href="/lib/highlightjs/default.css">
<link rel="stylesheet" href="/lib/qp/qp.css">
<?if($community_name==='codegolf'||$community_name==='test'){?>
  <link rel="stylesheet" href="/lib/katex/katex.min.css">
<?}?>
<style>
  .summary code { padding: 0 0.2em; background-color: rgb(var(--rgb-light)); border: 1px solid rgb(var(--rgb-mid)); border-radius: 1px; overflow-wrap: break-word; }
  .markdown pre, .markdown code { -webkit-transform: translate3d(0, 0, 0); }
  .markdown { overflow: auto; overflow-wrap: break-word; }
  .markdown>pre { white-space: pre; }
  .markdown>:first-child { margin-top: 0; }
  .markdown>:last-child { margin-bottom: 0; }
  .markdown ul { padding-left: 2em; }
  .markdown li { margin: 0.2rem 0; }
  .markdown img { max-width: 100%; max-height: 30rem; margin: 1px; }
  .markdown hr { background-color: rgb(var(--rgb-mid)); border: 0; height: 2px; }
  .markdown table { border-collapse: collapse; table-layout: fixed; }
  .markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
  .markdown th { background-color: rgb(var(--rgb-mid)); }
  .markdown td { background-color: white; }
  .markdown td, .markdown th { font-family: var(--markdown-table-font-family); font-size: 90%; white-space: pre; border: 1px solid black; padding: 0.2em; text-align: left; }
  .markdown blockquote { padding: 0.5rem; margin-left: 0.7rem; margin-right: 0; border-left: 0.3rem solid rgba(var(--rgb-dark),0.6); background-color: rgb(var(--rgb-light)); }
  .markdown blockquote>:first-child { margin-top: 0; }
  .markdown blockquote>:last-child { margin-bottom: 0; }
  .markdown code { padding: 0 0.2em; background-color: rgb(var(--rgb-light)); border: 1px solid rgb(var(--rgb-mid)); border-radius: 1px; overflow-wrap: break-word; }
  .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; overflow-wrap: unset; }
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
  .markdown .quoted-message > blockquote { margin: 0; background: white; padding: 4px; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; grid-column: 2 / span 1; grid-row: 2 / span 1; justify-self: start; }
  .markdown .post { border: 3px solid rgb(var(--rgb-dark)); margin: 0; }
  .markdown .post .tag:hover i { visibility: hidden; }
  .markdown .expandable { overflow: hidden; position: relative; }
  .markdown .expandable>code { overflow: hidden; }
  .markdown .expander { position: absolute; z-index: 1; height: 50px; top: 250px; padding-top: 34px; left: 0; right: 0; font-family: var(--regular-font-family); font-size: 12px; text-align: center; color: rgb(var(--rgb-dark));
                        background: linear-gradient(to bottom, rgba(var(--rgb-light),0) 0%, rgb(var(--rgb-light)) 40%, rgb(var(--rgb-light)) 25%); }
  .markdown .expander:hover { text-decoration: underline; cursor: pointer; }
  .dbfiddle { padding: 0.5rem; background-color: rgb(var(--rgb-light)); border-radius: 3px; }
  .dbfiddle .CodeMirror { height: auto; border: 1px solid rgb(var(--rgb-dark)); font-family: var(--monospace-font-family); border-radius: 3px; }
  .dbfiddle .CodeMirror-scroll { margin-bottom: -30px; }
  .dbfiddle .tablewrapper { margin-top: 0.5rem; }
  .dbfiddle>div { margin-top: 0.5rem; }
  .dbfiddle .batch { overflow: hidden; min-width: 0; }
  .dbfiddle .error { overflow: auto; white-space: pre; font-family: var(--monospace-font-family); background-color: rgba(var(--rgb-highlight),0.25); }
  .dbfiddle a { font-size: smaller; }
  .dbfiddle .qp { overflow-x: auto; overflow-y: hidden; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; margin-top: 5px; padding: 2px }
  .dbfiddle .qp-statement-header { display: none; }
  .qp-tt { z-index: 999; box-shadow: 0 0 2px 2px white; }
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
<script src="/lib/markdown-it-for-inline.js"></script>
<script src="/lib/markdown-it-container.js"></script>
<?if($community_name==='codegolf'||$community_name==='test'){?>
  <script src="/lib/katex/katex.min.js"></script>
  <script src="/lib/markdown-it-katex.js"></script>
<?}?>
<script src="/lib/markdownItAnchor.js"></script>
<script src="/lib/markdownItTocDoneRight.js"></script>
<script src="/lib/highlightjs/highlight.js"></script>
<script src="/lib/qp/qp.js"></script>
<script>
  // we have no idea why this works but without it cs highlighting doesn't happen
  (function(){
    var script = document.createElement( 'script' );
    script.type = 'text/javascript';
    script.async = false;
    script.src = "/lib/highlightjs/highlight.js";
    script.addEventListener("load", () => { hljs.initHighlighting(); })
    script.addEventListener("error", () => { console.log("error"); hljs.initHighlighting(); })
    document.querySelector("head").appendChild(script);
  })();
  //hljs.initHighlightingOnLoad();

  (function($){
    var md, mdsummary, prefix;
    function fiddleMarkdown(){
      function addfiddle(o,r){
        var l = o.attr('data-source-line'), f = $(r).replaceAll(o);
        if(l) f.attr('data-source-line',l);
        f.find('.qp').each(function(){ QP.showPlan($(this).get(0),$(this).attr('data-xml')); });
        f.find('textarea').each(function(){ CodeMirror.fromTextArea($(this)[0],{ viewportMargin: Infinity, mode: 'sql' }); });
        f.find('input').click(function(){
          f.css('opacity',0.5);
          $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
          $.post('https://test.dbfiddle.uk/run',{ rdbms: f.data('rdbms'), statements: JSON.stringify(f.find('.batch>textarea').map(function(){ return $(this).next('.CodeMirror')[0].CodeMirror.getValue(); }).get()) })
              .done(function(r){
            $.get('/dbfiddle?rdbms='+f.data('rdbms')+'&fiddle='+r).done(function(r){
              addfiddle(f,r);
            });
          });
        });
      }
      $(this).find('a[href*="//dbfiddle.uk"]')
             .filter(function(){ return $(this).attr('href').match(/https?:\/\/dbfiddle\.uk\/?\?.*fiddle=[0-91-f]{32}/)&&$(this).parent().is('p')&&($(this).parent().text()===('<>'+$(this).attr('href'))); })
             .each(function()
      {
        var t = $(this);
        $.get('/dbfiddle?'+t.attr('href').split('?')[1]).done(function(r){
          addfiddle(t.parent(),r);
        });
      });
    }

    function myslugify(s){
      return 'heading-'+(prefix?prefix+'-':'')+s;
    }

    md = window.markdownIt({ linkify: true
                           , highlight: function(str,lang){ lang = lang||'<?=$community_code_language?>'; if(lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; } })
               .use(window.markdownitSup)
               .use(window.markdownitSub)
               .use(window.markdownitEmoji)
               .use(window.markdownitDeflist)
               .use(window.markdownitFootnote)
               .use(window.markdownitAbbr)
               .use(window.markdownitContainer, 'quote', {
                 validate: function(params) {
                   return params.trim().match(/^quote\s+(.*)$/);
                 },
                 render: function (tokens, idx) {
                   var m = tokens[idx].info.trim().match(/^quote ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+)$/);
                   if (tokens[idx].nesting === 1) {
                     return '<div class="quoted-message" style="--rgb-dark: #'+m[5]+'; background: #'+m[4]+';">\n<img class="icon" src="/identicon?id='+m[3]+'">\n<a class="fa fa-fw fa-link" style="color: #'+m[5]+';" href="/transcript?room='+m[1]+'&id='+m[2]+'#c'+m[2]+'"></a>\n';
                   } else {
                     return '</div>\n';
                   }
                 } })
               .use(window.markdownitInjectLinenumbers)
               .use(window.markdownitObject,'answer',{ validate: function(p) { return p.trim().match(/^answer ([1-9][0-9]*)$/); }, render: function (tokens,idx){
                 var m = tokens[idx].info.trim().match(/^answer ([1-9][0-9]*)$/);
                 if (tokens[idx].nesting===1) return '<div class="object-answer" data-id="'+m[1]+'">';
                 else return '</div>';
               } })
               <?if($community_name==='codegolf'||$community_name==='test'){?>
                 .use(window.markdownitKatex)
               <?}?>
               .use(window.markdownItAnchor, { slugify: myslugify })
               .use(window.markdownItTocDoneRight,{ level: [1,2,3], slugify: myslugify })
               .use(window.markdownitForInline,'url-fix','link_open',function(tokens,idx)
    {
      if((tokens[idx+2].type!=='link_close') || (tokens[idx+1].type!=='text')) return;
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.SE') tokens[idx].attrSet('href','https://dba.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.SE') tokens[idx].attrSet('href','https://tex.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://M.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.SE') tokens[idx].attrSet('href','https://meta.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.SE') tokens[idx].attrSet('href','https://unit.stackexchange.com');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DBA.TA') tokens[idx].attrSet('href','/databases');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://DATABASES.TA') tokens[idx].attrSet('href','/databases');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://TEX.TA') tokens[idx].attrSet('href','/tex');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://UNIX.TA') tokens[idx].attrSet('href','/nix');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://NIX.TA') tokens[idx].attrSet('href','/nix');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://META.TA') tokens[idx].attrSet('href','/meta');
      if(tokens[idx].attrGet('href').toUpperCase()==='HTTP://CODEGOLF.TA') tokens[idx].attrSet('href','/codegolf');
    });

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
                      .use(window.markdownitSup).use(window.markdownitSub);
    mdsummary.options.linkify =true;
    mdsummary.linkify.tlds('kiwi',true).tlds('xyz',true);
 
    $.fn.renderMarkdown = function(callback){
      this.filter('[data-markdown]').each(function(){
        var t = $(this), m = t.attr('data-markdown');
        prefix = t.closest('[data-id]').attr('id')||'';
        //if(m.length>10000) md.enable('anchor'); else md.disable('anchor');
        t.html(md.render(m,{ docId: prefix }));
        t.find('table').wrap('<div class="tablewrapper" tabindex="-1">');
        t.find(':not(.quoted-message):not(a)>img').each(function(){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); });
        t.find(':not(sup.footnote-ref)>a:not(.footnote-backref):not([href^="#"])').attr({ 'rel':'nofollow', 'target':'_blank' });
        t.find('.object-answer').each(function(){ var t = $(this); $.get('/duplicate?community=<?=$community_name?>&id='+t.attr('data-id')).done(function(r){ t.html(r); typeof callback==='function' && callback(); }); });
        t.children('pre').each(function(){
          var t = $(this), h = t.height();
          if(h>450){
            t.innerHeight(300).addClass('expandable');
            $('<div class="expander">show '+Math.round((h-300)/14.4)+' more lines</div>').prependTo(t).click(function(){ t.animate({ height: h }, function(){ t.height(''); }).removeClass('expandable'); $(this).remove(); });
          }
        });
        if(!t.hasClass('nofiddle')) fiddleMarkdown.call(this);
      });
      typeof callback==='function' && callback();
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
