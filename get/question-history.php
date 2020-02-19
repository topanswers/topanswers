<?    
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to question_history,pg_temp");
ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']??'') || fail(403,'access denied');
extract(cdb("select account_id
                   ,question_id,question_title,question_is_imported
                   ,community_name,community_display_name,community_code_language,community_tables_are_monospace
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
             from one"));
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
?>
<!doctype html>
<html style="--rgb-dark: <?=$community_rgb_dark?>;
             --rgb-mid: <?=$community_rgb_mid?>;
             --rgb-light: <?=$community_rgb_light?>;
             --rgb-highlight: <?=$community_rgb_highlight?>;
             --rgb-warning: <?=$community_rgb_warning?>;
             --regular-font-family: '<?=$my_community_regular_font_name?>', serif;
             --monospace-font-family: '<?=$my_community_monospace_font_name?>', monospace;
             --markdown-table-font-family: <?=$community_tables_are_monospace?"'".$my_community_monospace_font_name."', monospace":"'".$my_community_regular_font_name."', serif;"?>
             ">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 14px; }
    html, body { margin: 0; padding: 0; scroll-behavior: smooth; }
    textarea, pre, code, .CodeMirror, .diff { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    [data-rz-handle] { flex: 0 0 2px; background: black; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }

    .markdown, .title { background: white; padding: 8px; font-size: 16px; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; }
    .diff { background: rgb(var(--rgb-mid)); overflow-wrap: break-word; white-space: pre-wrap; font-family: monospace; padding: 8px; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; }
    .diff:target, .diff:target+div { box-shadow: 0 0 3px 3px rgb(var(--rgb-highlight)); }
    .separator { border-bottom: 4px solid rgb(var(--rgb-dark)); margin: 14px -14px; }
    .separator:last-child { display: none; }

    .who, .when { white-space: nowrap; }
    .when { font-size: smaller; color: rgb(var(--rgb-dark)); }

    .CodeMirror { height: 100%; border: 1px solid rgb(var(--rgb-dark)); font-size: 16px; border-radius: 3px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/js.cookie.js"></script>
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
        m.attr('data-markdown',cm.getValue()).renderMarkdown(function(){
          $('.post:not(.processed) .when').each(function(){
            $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
            $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
          });
          $('.post').addClass('processed');
        });
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
  <title>Question History - TopAnswers</title>
</head>
<body style="font-size: larger; background: rgb(var(--rgb-light));">
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div class="container">
      <span class="element">question history for: <a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></span>
    </div>
    <div style="display: flex; align-items: center;">
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
    </div>
  </header>
  <div style="width: 100%; display: grid; align-items: start; grid-template-columns: auto 1fr 1fr; grid-auto-rows: auto; grid-gap: 1rem; padding: 1rem;">
    <?foreach(db("select question_history_id,account_id,account_name,question_history_markdown,question_history_title,question_history_at,prev_markdown,prev_title,rn from history order by rn desc") as $i=>$r){ extract($r);?>
      <?$rowspan = ($rn>1)?4:2;?>
      <?$rowoffset = 5*$i;?>
      <div style="grid-area: <?=(1+$rowoffset)?> / 1 / <?=(1+$rowspan+$rowoffset)?> / 2;">
        <div class="who"><?=$account_name?></div>
        <div><?=($rn===1)?($question_is_imported?'imported':'asked'):'edited'?></div>
        <div class="when"><?=$question_history_at?></div>
      </div>
      <div style="grid-area: <?=(1+$rowoffset)?> / 2 / span 1 / 4;" class="title"><?=$question_history_title?></div>
      <textarea data-grid-area="<?=(2+$rowoffset)?> / 2 / span 1 / 3"><?=$question_history_markdown?></textarea>
      <div style="grid-area: <?=(2+$rowoffset)?> / 3 / span 1 / 4; overflow: hidden;" class="markdown"></div>
      <?if($rn>1){?>
        <div id="h<?=$question_history_id?>" style="grid-area: <?=(3+$rowoffset)?> / 2 / span 1 / 4;" class="diff" data-from="<?=$prev_title?>" data-to="<?=$question_history_title?>"></div>
        <div style="grid-area: <?=(4+$rowoffset)?> / 2 / span 1 / 4; overflow: hidden;" class="diff" data-from="<?=$prev_markdown?>" data-to="<?=$question_history_markdown?>"></div>
      <?}?>
      <div style="grid-area: <?=(1+$rowspan+$rowoffset)?> / 1 / span 1 / 4;" class="separator"></div>
    <?}?>
  </div>
</body>   
</html>   
