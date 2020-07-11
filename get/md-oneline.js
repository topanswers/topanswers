define(['md-shortlinks','markdown-it','markdown-it-sup','markdown-it-sub','markdown-it-emoji','markdown-it-abbr','markdown-it-for-inline'],(shortlinks)=>{

    const md = require('markdown-it')('zero',{ typographer: true })
                 .enable(['replacements','smartquotes','autolink','backticks','entity','escape','linkify','reference','emphasis','link','strikethrough','backticks'])
                 .use(require('markdown-it-sup')).use(require('markdown-it-sub')).use(require('markdown-it-for-inline'),'url-fix','link_open',shortlinks);

    md.options.linkify = true;
    md.linkify.tlds('kiwi',true).tlds('xyz',true);

  return md;
  
});
