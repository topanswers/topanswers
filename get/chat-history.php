<?    
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to chat_history,pg_temp");
ccdb("select login_chat(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']??'') || fail(403,'access denied');
extract(cdb("select account_id
                   ,chat_id
                   ,room_id,room_name
                   ,community_name,community_display_name,community_code_language
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,colour_dark,colour_mid,colour_light,colour_highlight
             from one"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/dist/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/lib/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { margin: 0; padding: 0; }
    textarea, pre, code, .CodeMirror, .diff { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .markdown, .diff { border: 1px solid #<?=$colour_dark?>; padding: 0.5rem; border-radius: 4px; }
    .separator { border-bottom: 0.3rem solid #<?=$colour_dark?>; margin: 1rem -1rem; }
    .separator:last-child { display: none; }
    .diff { background-color: #<?=$colour_mid?>; overflow-wrap: break-word; white-space: pre-wrap; }

    .who, .when { white-space: nowrap; }
    .when { font-size: smaller; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 4px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery/dist/jquery.min.js"></script>
  <script src="/lib/codemirror/clib/odemirror.js"></script>
  <script src="/lib/codemirror/mode/markdown/markdown.js"></script>
  <script src="/lib/codemirror/mode/sql/sql.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/moment/min/moment-with-locales.js"></script>
  <script src="/lib/diff_match_patch.js"></script>
  <script>
    $(function(){
      var dmp = new diff_match_patch();
      $('textarea').each(function(){
        var m = $(this).next(), cm = CodeMirror.fromTextArea($(this)[0],{ lineWrapping: true, readOnly: true });
        m.attr('data-markdown',cm.getValue()).renderMarkdown();
        $(cm.getWrapperElement()).css('grid-area',$(this).data('grid-area'));
      });
      $('.diff').each(function(){
        var d = dmp.diff_main($(this).attr('data-from'),$(this).attr('data-to'));
        dmp.diff_cleanupSemantic(d);
        $(this).html(dmp.diff_prettyHtml(d));
      });
    });
  </script>
  <title>Chat Message History - TopAnswers</title>
</head>
<body style="font-size: larger; background-color: #<?=$colour_light?>;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon?id=<?=$account_id?>"></a>
    </div>
  </header>
  <div style="width: 100%; display: grid; align-items: start; grid-template-columns: auto 1fr 1fr; grid-auto-rows: auto; grid-gap: 1rem; padding: 1rem;">
    <?foreach(db("select chat_history_markdown,chat_history_at,prev_markdown,rn from history order by rn desc") as $i=>$r){ extract($r);?>
      <?$rowspan = ($rn>1)?2:1;?>
      <?$rowoffset = 3*$i;?>
      <div style="grid-area: <?=(1+$rowoffset)?> / 1 / <?=(1+$rowspan+$rowoffset)?> / 2;">
        <div class="when"><?=$chat_history_at?></div>
      </div>
      <textarea data-grid-area="<?=(1+$rowoffset)?> / 2 / span 1 / 3"><?=$chat_history_markdown?></textarea>
      <div style="grid-area: <?=(1+$rowoffset)?> / 3 / span 1 / 4; overflow: hidden;" class="markdown"></div>
      <?if($rn>1){?>
        <div style="grid-area: <?=(2+$rowoffset)?> / 2 / span 1 / 4; overflow: hidden;" class="diff" data-from="<?=$prev_markdown?>" data-to="<?=$chat_history_markdown?>"></div>
      <?}?>
      <div style="grid-area: <?=(1+$rowspan+$rowoffset)?> / 1 / span 1 / 4;" class="separator"></div>
    <?}?>
  </div>
</body>   
</html>   
