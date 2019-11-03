<?    
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = $_COOKIE['uuid']??'';
ccdb("select login($1)",$uuid);
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
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
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    #markdown ul { padding-left: 2em; }
    #markdown img { max-height: 20em; max-width: 100%; }
    #markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
    #markdown table { border-collapse: collapse; table-layout: fixed; }
    #markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
    #markdown td, .markdown th { white-space: nowrap; border: 1px solid black; padding: 0.2em; }
    #markdown blockquote {  padding-left: 0.7em;  margin-left: 0.7em; margin-right: 0; border-left: 0.3em solid #<?=$colour_mid?>; }
    #markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    #markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }

    .CodeMirror { height: 100%; border: 0.2rem solid #<?=$colour_dark?>; font-size: 1.1em; }
    .CodeMirror pre.CodeMirror-placeholder { color: #<?=$colour_mid?>; }
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
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script src="/favico.js"></script>
  <script src="codemirror/codemirror.js"></script>
  <script src="codemirror/markdown.js"></script>
  <script src="codemirror/placeholder.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr).use(window.markdownitInjectLinenumbers);
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true });
      var map;
      $('#community').change(function(){ window.location = '/ask?community='+$(this).val().toLowerCase(); });
      cm.on('change',function(){
        $('#markdown').html(md.render(cm.getValue()));
        $('#markdown table').wrap('<div class="tablewrapper">');
        map = [];
        $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        localStorage.setItem('<?=$community?>.ask',cm.getValue());
      });
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) $('#markdown').animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#markdown').animate({ scrollTop: $('#markdown').prop("scrollHeight")-$('#markdown').height() });
        else $('#markdown [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
      if(localStorage.getItem('<?=$community?>.ask')) cm.setValue(localStorage.getItem('<?=$community?>.ask'));
    });
  </script>
  <title>Ask | <?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #abc; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5em; margin-right: 0.1em;">
      <span style="color: #<?=$colour_mid?>;">TopAnswers </span>
      <select id="community">
        <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
          <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
        <?}?>
      </select>
    </div>
    <div style="display: flex; height: 100%;">
      <?if($uuid){?><a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2em; display: block; height: 2.4em;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a><?}?>
    </div>
  </header>
  <main style="display: flex; justify-content: center; flex: 1 0 0; background-color: #<?=$colour_mid?>; padding: 2vmin; overflow-y: auto;">
    <div style="flex: 0 1 60em; max-width: calc(50vw - 3vmin);">
      <textarea autofocus style="width: 100%; height: 100%;" placeholder="type question here using markdown (this is just a demo for now to test the editor, preview and scrolling sync)"></textarea>
    </div>
    <div style="flex: 0 0 2vmin;"></div>
    <div id="markdown" style="flex: 0 1 60em; max-width: calc(50vw - 3vmin); background-color: white; padding: 1em; border: 0.2rem solid #<?=$colour_dark?>; overflow-y: auto;"></div>
  </main>
</body>   
</html>   
