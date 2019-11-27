<link rel="stylesheet" href="/highlightjs/default.css">
<style>
  .markdown { overflow: auto; overflow-wrap: break-word; }
  .markdown>:first-child { margin-top: 0; }
  .markdown>:last-child { margin-bottom: 0; }
  .markdown ul { padding-left: 2em; }
  .markdown li { margin: 0.2rem 0; }
  .markdown img { max-width: 100%; max-height: 30rem; margin: 1px; }
  .markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
  .markdown table { border-collapse: collapse; table-layout: fixed; }
  .markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
  .markdown th { background-color: #<?=$colour_mid?>; }
  .markdown td { background-color: white; }
  .markdown td, .markdown th { font-family: monospace; font-size: 1em; white-space: pre; border: 1px solid black; padding: 0.2em; text-align: left; }
  .markdown blockquote { padding: 0.5rem; margin-left: 0.7rem; margin-right: 0; border-left: 0.3rem solid #<?=$colour_mid?>; background-color: #<?=$colour_light?>40; }
  .markdown blockquote>:first-child { margin-top: 0; }
  .markdown blockquote>:last-child { margin-bottom: 0; }
  .markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; overflow-wrap: break-word; }
  .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }
  .dbfiddle { margin: 0.5rem; padding: 0.5rem; background-color: #<?=$colour_light?>; border-radius: 4px; }
  .dbfiddle .CodeMirror { height: auto; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0.2rem; }
  .dbfiddle .CodeMirror-scroll { margin-bottom: -30px; }
  .dbfiddle .tablewrapper { margin-top: 0.5rem; }
  .dbfiddle>div { margin-top: 0.5rem; }
  .dbfiddle .batch { overflow: hidden; min-width: 0; }
  .dbfiddle .error { overflow: auto; white-space: pre; font-family: monospace; background-color: #<?=$colour_highlight?>40; }
  .dbfiddle a { font-size: smaller; }
</style>
<script src="/markdown-it.js"></script>
<script src="/markdown-it-inject-linenumbers.js"></script>
<script src="/markdown-it-sup.js"></script>
<script src="/markdown-it-sub.js"></script>
<script src="/markdown-it-emoji.js"></script>
<script src="/markdown-it-footnote.js"></script>
<script src="/markdown-it-deflist.js"></script>
<script src="/markdown-it-abbr.js"></script>
<script src="/markdown-it-for-inline.js"></script>
<script src="/markdownItAnchor.js"></script>
<script src="/markdownItTocDoneRight.js"></script>
<script src="/highlightjs/highlight.js"></script>
<script>
  // we have no idea why this works but without it cs highlighting doesn't happen
  (function(){
    var script = document.createElement( 'script' );
    script.type = 'text/javascript';
    script.async = false;
    script.src = "/highlightjs/highlight.js";
    script.addEventListener("load", () => { console.log("laoded"); hljs.initHighlighting(); })
    script.addEventListener("error", () => { console.log("error"); hljs.initHighlighting(); })
    document.querySelector("head").appendChild(script);
  })();
  //hljs.initHighlightingOnLoad();

  (function($){
    var md, mdsummary;
    function fiddleMarkdown(){
      function addfiddle(o,r){
        var l = o.attr('data-source-line'), f = $(r).replaceAll(o);
        if(l) f.attr('data-source-line',l);
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

    md = window.markdownIt({ linkify: true
                           , highlight: function(str,lang){ lang = lang||'<?=$community_code_language?>'; if(lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; } })
               .use(window.markdownitSup)
               .use(window.markdownitSub)
               .use(window.markdownitEmoji)
               .use(window.markdownitDeflist)
               .use(window.markdownitFootnote)
               .use(window.markdownitAbbr)
               .use(window.markdownitInjectLinenumbers)
               .use(window.markdownItAnchor, { permalink: true, permalinkBefore: true, permalinkSymbol: '' })
               .use(window.markdownItTocDoneRight,{ level: [1,2,3] })
               .use(window.markdownitForInline,'url-fix','link_open',function(tokens,idx)
    {
      if((tokens[idx+2].type!=='link_close') || (tokens[idx+1].type!=='text')) return;
      if(tokens[idx].attrGet('href')==='http://dba.se') tokens[idx].attrSet('href','https://dba.stackexchange.com');
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

    md.linkify.tlds('kiwi',true).tlds('xyz',true);
    mdsummary = window.markdownIt('zero').enable(['replacements','smartquotes','autolink','backticks','entity','escape','linkify','reference','emphasis','link','strikethrough','backticks']).use(window.markdownitSup).use(window.markdownitSub);
    mdsummary.options.linkify =true;
    mdsummary.linkify.tlds('kiwi',true).tlds('xyz',true);
 
    $.fn.renderMarkdown = function(){
      this.filter('[data-markdown]').each(function(){
        $(this).html(md.render($(this).attr('data-markdown')));
        $(this).find('table').wrap('<div class="tablewrapper" tabindex="-1">');
        $(this).find(':not(a)>img').each(function(){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); });
        $(this).find(':not(sup.footnote-ref)>a:not(.footnote-backref):not([href^="#"])').attr({ 'rel':'nofollow', 'target':'_blank' });
        if(!$(this).hasClass('nofiddle')) fiddleMarkdown.call(this);
      });
      return this;
    };

    $.fn.renderMarkdownSummary = function(){
      this.filter('[data-markdown]').each(function(){
        $(this).html(mdsummary.render($(this).attr('data-markdown')).split('\n')[0]);
      });
      return this;
    };

  }(jQuery));
</script>
