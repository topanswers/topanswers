<?    
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
include '../db.php';
include '../nocache.php';
$uuid = $_COOKIE['uuid']??'';
ccdb("select login($1)",$uuid);
$id = $_GET['id'];
ccdb("select count(*) from answer where answer_id=$1",$id)==='1' || die('invalid answer id');
extract(cdb("select regular_font_name,monospace_font_name,community_code_language
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
                  , community_name community
             from answer natural join (select question_id,community_id from question) q natural join community natural join my_account_community
             where answer_id=$1",$id));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$regular_font_name?>', serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/fonts/<?=$regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { margin: 0; padding: 0; scroll-behavior: smooth; }
    textarea, pre, code { font-family: '<?=$monospace_font_name?>', monospace; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .markdown, .diff { border: 1px solid #<?=$colour_dark?>; padding: 0.5rem; border-radius: 4px; }
    .separator { border-bottom: 0.3rem solid #<?=$colour_dark?>; margin: 1rem -1rem; }
    .separator:last-child { display: none; }
    .diff { background-color: #<?=$colour_mid?>; overflow-wrap: break-word; white-space: pre-wrap; font-family: monospace; }
    .diff:target { box-shadow: 0 0 3px 3px #<?=$colour_highlight?>; }

    .who, .when { white-space: nowrap; }
    .when { font-size: smaller; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 4px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/codemirror/codemirror.js"></script>
  <script src="/lib/codemirror/markdown.js"></script>
  <script src="/lib/codemirror/sql.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/moment.js"></script>
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
      setTimeout(function(){ $('.diff:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
    });
  </script>
  <title>Answer History - TopAnswers</title>
</head>
<body style="font-size: larger; background-color: #<?=$colour_light?>;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon?id=<?=ccdb("select account_id from login")?>"></a>
    </div>
  </header>
  <div style="width: 100%; display: grid; align-items: start; grid-template-columns: auto 1fr 1fr; grid-auto-rows: auto; grid-gap: 1rem; padding: 1rem;">
    <?foreach(db("select answer_history_id,account_id,account_name,answer_history_markdown
                       , to_char(answer_history_at,'YYYY-MM-DD HH24:MI:SS') answer_history_at
                       , lag(answer_history_markdown) over (order by answer_history_at) prev_markdown
                       , row_number() over (order by answer_history_at) rn
                  from answer_history natural join account
                  where answer_id=$1
                  order by answer_history_at desc",$id) as $i=>$r){ extract($r);?>
      <?$rowspan = ($rn>1)?2:1;?>
      <?$rowoffset = 3*$i;?>
      <div style="grid-area: <?=(1+$rowoffset)?> / 1 / <?=(1+$rowspan+$rowoffset)?> / 2;">
        <div class="who"><?=htmlspecialchars($account_name)?></div>
        <div class="when"><?=$answer_history_at?></div>
      </div>
      <textarea data-grid-area="<?=(1+$rowoffset)?> / 2 / span 1 / 3"><?=htmlspecialchars($answer_history_markdown)?></textarea>
      <div style="grid-area: <?=(1+$rowoffset)?> / 3 / span 1 / 4; overflow: hidden;" class="markdown"></div>
      <?if($rn>1){?>
        <div id="h<?=$answer_history_id?>" style="grid-area: <?=(2+$rowoffset)?> / 2 / span 1 / 4; overflow: hidden;" class="diff" data-from="<?=htmlspecialchars($prev_markdown)?>" data-to="<?=htmlspecialchars($answer_history_markdown)?>"></div>
      <?}?>
      <div style="grid-area: <?=(1+$rowspan+$rowoffset)?> / 1 / span 1 / 4;" class="separator"></div>
    <?}?>
  </div>
</body>   
</html>   
