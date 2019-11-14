<?    
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid']??'';
ccdb("select login($1)",$uuid);
$id = $_GET['id'];
ccdb("select count(*) from question where question_id=$1",$id)==='1' || die('invalid question id');
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from question natural join (select question_id,community_id from question) q natural join community
             where question_id=$1",$id));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    tr { padding: 0.5rem; }
    tbody { border-bottom: 0.3rem solid #<?=$colour_dark?>; }
    td { padding: 0.5rem; }
    td:not([rowspan]):not([colspan]) { width: 50%; vertical-align: top; }
    .markdown, .diff, .title { border: 1px solid #<?=$colour_dark?>; padding: 0.5rem; border-radius: 4px; }
    .markdown, .title { background-color: white; }
    .diff { background-color: #<?=$colour_mid?>; }

    .who, .when { white-space: nowrap; }
    .when { font-size: smaller; }

    .markdown > :first-child { margin-top: 0; }
    .markdown > :last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 2rem; }
    .markdown li { margin: 0.5rem 0; }
    .markdown img { max-height: 20rem; max-width: 100%; }
    .markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
    .markdown table { border-collapse: collapse; table-layout: fixed; }
    .markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
    .markdown td, .markdown th { white-space: nowrap; border: 1px solid black; padding: 0.2rem; }
    .markdown blockquote {  padding-left: 0.7rem;  margin-left: 0.7rem; margin-right: 0; border-left: 0.3rem solid #<?=$colour_mid?>; }
    .markdown code { padding: 0 0.2rem; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1rem; }
    .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4rem; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 4px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lodash.js"></script>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-inject-linenumbers.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/markdown-it-footnote.js"></script>
  <script src="/markdown-it-deflist.js"></script>
  <script src="/markdown-it-abbr.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/moment.js"></script>
  <script src="codemirror/codemirror.js"></script>
  <script src="codemirror/markdown.js"></script>
  <script src="codemirror/placeholder.js"></script>
  <script src="diff_match_patch.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr).use(window.markdownitInjectLinenumbers);
      var dmp = new diff_match_patch();
      $('textarea').each(function(){
        var m = $(this).closest('td').next().children(), cm = CodeMirror.fromTextArea($(this)[0],{ lineWrapping: true, readOnly: true });
        m.html(md.render(cm.getValue()));
        m.find('table').wrap('<div class="tablewrapper">');
      });
      $('.diff').each(function(){
        var d = dmp.diff_main($(this).data('from'),$(this).data('to'));
        dmp.diff_cleanupSemantic(d);
        $(this).html(dmp.diff_prettyHtml(d));
      });
    });
  </script>
  <title>History | <?=ucfirst($community)?> | Topquestions</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">Topquestions <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a>
    </div>
  </header>
  <table style="table-layout: fixed; border-collapse: collapse;">
    <?foreach(db("select account_id,account_name,question_history_markdown,question_history_title
                       , to_char(question_history_at,'YYYY-MM-DD HH24:MI:SS') question_history_at
                       , lag(question_history_markdown) over (order by question_history_at) prev_markdown
                       , lag(question_history_title) over (order by question_history_at) prev_title
                       , row_number() over (order by question_history_at) rn
                  from question_history natural join account
                  where question_id=$1
                  order by question_history_at desc",$id) as $r){ extract($r);?>
      <tbody>
        <tr>
          <td rowspan="<?=($rn>1)?4:2?>">
            <div class="who"><?=htmlspecialchars($account_name)?></div>
            <div class="when"><?=$question_history_at?></div>
          </td>
          <td colspan="2"><div class="title"><?=htmlspecialchars($question_history_title)?></div></td>
        </tr>
        <tr>
          <td><textarea><?=htmlspecialchars($question_history_markdown)?></textarea></td>
          <td><div class="markdown"></div></td>
        </tr>
        <?if($rn>1){?>
          <tr>
            <td colspan="2"><div class="diff" data-from="<?=htmlspecialchars($prev_title)?>" data-to="<?=htmlspecialchars($question_history_title)?>"></div></td>
          </tr>
          <tr>
            <td colspan="2"><div class="diff" data-from="<?=htmlspecialchars($prev_markdown)?>" data-to="<?=htmlspecialchars($question_history_markdown)?>"></div></td>
          </tr>
        <?}?>
      </tbody>
    <?}?>
  </table>
</body>   
</html>   
