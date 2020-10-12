<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to question_history,pg_temp");
ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']??'') || fail(403,'access denied');
extract(cdb("select account_id,account_image_url
                   ,question_id,question_title,question_is_imported,question_can_purge_drafts
                   ,community_name,community_display_name,community_code_language,community_tables_are_monospace,community_image_url
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,my_community_regular_font_name,my_community_monospace_font_name
                  , (select jsonb_agg(z)
                     from (select account_id,account_name,account_image_url
                                , to_char(history_at,'YYYY-MM-DD HH24:MI:SS') history_at
                                , question_published_at=history_at history_is_publish
                                , case when question_history_id is not null then 'h' when question_flag_history_id is not null then 'f' else 't' end item_type
                                , case when question_history_id is not null then (row_number() over (partition by question_history_id is null order by history_at)) end rn
                                , case when question_history_id is not null then (count(1) over (partition by question_history_id is null)) end cnt
                                , coalesce(question_history_id,question_flag_history_id,mark_history_id,0) id
                                , coalesce((select to_jsonb(a) from question_history a where a.question_history_id=h.question_history_id)
                                         , (select to_jsonb(f) from question_flag_history f where f.question_flag_history_id=h.question_flag_history_id)
                                         , (select to_jsonb(t) from mark_history t where t.mark_history_id=h.mark_history_id)) item_data
                           from history h
                           order by history_at desc, item_type desc) z) items
             from one"));
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             --lang-code:<?=$community_code_language?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             --font-table:<?=$community_tables_are_monospace?$my_community_monospace_font_name:$my_community_regular_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/lightbox2/css/lightbox.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/fouc.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/question-history/question-history.css")?>">
  <link rel="stylesheet" href="<?=h("/markdown.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/codemirror/codemirror.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/qp/qp.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/katex/katex.min.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title>Question History - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/question-history/question-history.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div class="container">
      <span class="element">question history for: <a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></span>
    </div>
    <div>
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a>
    </div>
  </header>
  <main>
    <div id="revisions">
      <?foreach($items as $i=>$r){ extract($r,EXTR_PREFIX_ALL,'h'); extract($h_item_data,EXTR_PREFIX_ALL,'d');?>
        <div id="<?=$h_item_type.$h_id?>" data-bar="<?=($h_item_type==='h')?'visible':'hidden'?>" data-rev="<?=( ($h_item_type==='h') && ($h_rn>1) )?('Revision '.($h_rn-1).' of '.($h_cnt-1)):''?>">
          <div>
            <?if($h_item_type==='h'){?>
              <?$action = ($h_rn===1)?($question_is_imported?'Imported':'Posted'):($h_history_is_publish?'Published':'Edited')?>
            <?}else if($h_item_type==='f'){?>
              <?if($d_question_flag_history_direction===1) $action = 'Flagged'; else if($d_question_flag_history_direction===0) $action = 'Unflagged'; else $action = 'Counterflagged';?>
            <?}else{?>
              <?$action = '['.$d_tag_name.'] '.($d_mark_history_is_removal?'removed':'added')?>
            <?}?>
            <?=$action?> by <?=$h_account_name?>
            <div class="when"><?=$h_history_at?></div>
          </div>
          <img class="icon" data-id="<?=$h_account_id?>" src="<?=$h_account_image_url?>">
        </div>
      <?}?>
      <?if($question_can_purge_drafts){?>
        <form method="post" action="//post.topanswers.xyz/question-history">
          <input type="hidden" name="action" value="purge">
          <input type="hidden" name="id" value="<?=$question_id?>">
          <div><a id="purge" href="." class="button">purge drafts</a></div>
        </form>
      <?}?>
    </div>
    <div id="history-bar">
      <div>
        <a href="." class="panel" data-panel="before-container">before</a> / <a class="panel" data-panel="diff-container">diff</a> / <a href="." class="panel" data-panel="after-container">after</a>
      </div>
      <div>
        <span></span>
      </div>
    </div>
    <div id="content">
      <?foreach($items as $i=>$r){ extract($r,EXTR_PREFIX_ALL,'h'); extract($h_item_data,EXTR_PREFIX_ALL,'d');?>
        <div class="<?=$h_item_type.$h_id?>">
          <?if($h_item_type==='h'){?>
            <?if($h_rn>1){?>
              <div class="panel before-container">
                <div class="title"><?=$d_prev_title?></div>
                <textarea><?=$d_prev_markdown?></textarea>
                <div class="markdown"></div>
              </div>
            <?}?>
            <div class="panel diff-container">
              <div class="diff diff-title" data-from="<?=$d_prev_title?>" data-to="<?=$d_question_history_title?>"></div>
              <div class="diff diff-body" data-from="<?=$d_prev_markdown?>" data-to="<?=$d_question_history_markdown?>"></div>
            </div>
            <div class="panel after-container">
              <div class="title"><?=$d_question_history_title?></div>
              <textarea><?=$d_question_history_markdown?></textarea>
              <div class="markdown"></div>
            </div>
          <?}?>
        </div>
      <?}?>
    </div>
  </main>
</body>   
</html>   
