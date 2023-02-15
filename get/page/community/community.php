<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
include '../../../config.php';
include '../../../db.php';
include '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to community,pg_temp");
if(isset($_COOKIE['uuid'])){ ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied'); }
if(ccdb("select exists(select 1 from private where community_name=$1)",$_GET['community'])) { header('Location: //topanswers.xyz/private?community='.$_GET['community']); exit; }
$pagesize = $_COOKIE['pagesize']??'10';
$hidepreview = ($_COOKIE['hidepreview']??'false') === 'true';
$hidekeyboard = ($_COOKIE['hidekeyboard']??'true') === 'true';
$clearlocal = $_COOKIE['clearlocal']??'';
$environment = $_COOKIE['environment']??'prod';
if(!isset($_GET['room'])&&!isset($_GET['q'])){
  $auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
}elseif(isset($_GET['room'])&&!isset($_GET['q'])){
  $auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
}elseif(isset($_GET['q'])&&!isset($_GET['room'])){
  $auth = ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['q']);
}else{
  fail(400,"exactly one of 'q' or 'room' must be set");
}
if($auth) setcookie("uuid",$_COOKIE['uuid'],['expires'=>2147483647,'path'=>'/','domain'=>'.'.config("SITE_DOMAIN"),'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
extract(cdb("select login_resizer_percent,login_chat_resizer_percent
                   ,account_id,account_is_dev,account_notification_id,account_image_url
                   ,community_id,community_name,community_language,community_display_name,community_my_power,community_code_language,community_tio_language,community_about_question_id
                   ,community_ask_button_text,community_banner_markdown,community_image_url,community_tables_are_monospace
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,communicant_is_post_flag_crew,communicant_can_import,communicant_keyboard
                   ,room_id,room_name,room_can_chat,room_has_chat,room_can_mute,room_can_listen,room_is_pinned,room_image_url,room_show_minimap
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,sesite_url
                   ,question_id,question_title,question_markdown,question_votes,question_license_name,question_se_question_id,question_crew_flags,question_active_flags
                   ,question_has_history,question_is_deleted,question_votes_from_me,question_answered_by_me,question_is_answered,question_answer_count
                   ,question_i_subscribed,question_i_flagged,question_i_counterflagged,question_is_published
                   ,question_when
                   ,question_account_id,question_account_is_me,question_account_name,question_account_is_imported,question_account_image_url
                   ,question_selink_user_id,question_communicant_votes
                   ,question_license_href,question_has_codelicense,question_codelicense_name,question_license_description,question_codelicense_description
                   ,tag_code_language
                  , to_char(coalesce(question_published_at,question_at),'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                   ,sanction_short_description,kind_can_all_edit,kind_has_answers,kind_has_question_votes,kind_has_answer_votes,kind_minimum_votes_to_answer,kind_allows_question_multivotes,kind_allows_answer_multivotes
                   ,kind_show_answer_summary_toc
             from one"));
if(!$communicant_keyboard) $hidekeyboard = true;
$communicant_keyboard = htmlspecialchars_decode($communicant_keyboard);
$dev = $account_is_dev;
$_GET['community']===$community_name || fail(400,'invalid community');
include '../../../lang/community.'.$community_language.'.php';
$jslang = substr($community_language,0,1).substr(strtok($community_language,'-'),-1);
$question = $_GET['q']??'0';
$room = $room_id;
$canchat = $room_can_chat;
$cookies = 'Cookie: '.(isset($_COOKIE['uuid'])?'uuid='.$_COOKIE['uuid'].'; ':'').(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':'').(isset($_COOKIE['pagesize'])?'pagesize='.$_COOKIE['pagesize'].'; ':'');
//ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             --jslang:<?=$jslang?>;
             --lang-code:<?=$tag_code_language?:$community_code_language?>;
             --lang-tio:<?=$community_tio_language?>;
             --l_preview:<?=$l_preview?>;
             --l_mute:<?=$l_mute?>;
             --l_listen:<?=$l_listen?>;
             --l_pin:<?=$l_pin?>;
             --l_unpin:<?=$l_unpin?>;
             --l_show_more_lines:<?=$l_show_more_lines?>;
             <?if($question_id){?>--question:<?=$question_id?>;<?}?>
             --notification:<?=$auth?$account_notification_id:'0'?>;
             --room:<?=$room_id?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --power:<?=$community_my_power?>;
             --required:<?=$kind_minimum_votes_to_answer?>;
             --resizer:<?=$login_resizer_percent?>;
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             --font-table:<?=$community_tables_are_monospace?$my_community_monospace_font_name:$my_community_regular_font_name?>;
             "
      <?="data-l-map='$l_map'"?>
      <?="data-l-people='$l_people'"?>
      <?="data-l-dismissing='$l_dismissing'"?>
      <?="data-community='$community_name'"?>
      <?="data-room='$room_id'"?>
      <?="data-room-chat-count='$room_chat_count'"?>
      <?="data-room-chat-age='$room_chat_age'"?>
      <?=$auth?'data-auth':''?>
      <?=$dev?'data-dev':''?>
      <?=$room_show_minimap?'data-minimap':''?>
      <?=$communicant_is_post_flag_crew?'data-crew':''?>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <noscript><link rel="stylesheet" href="<?=h("/noscript.css")?>"></noscript>
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/lightbox2/css/lightbox.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/vex/vex.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/vex/vex-theme-topanswers.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/fouc.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/community/community.css")?>">
  <? if ($question) { ?>
    <link rel="stylesheet" href="<?=h('/page/community/community_question.css')?>">
  <? } ?>
  <link rel="stylesheet" href="<?=h("/markdown.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/codemirror/codemirror.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/qp/qp.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/katex/katex.min.css")?>">
  <?if($question_id){?>
    <link rel="stylesheet" href="<?=h("/lib/starrr.css")?>">
  <?}?>
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <?if($question_id){?>
    <link rel="alternate" type="application/rss+xml" href="/feedq.rss?q=<?=$question_id?>" />
  <?}else{?>
    <link rel="alternate" type="application/rss+xml" href="/feed.rss?community=<?=$community_name?>" />
  <?}?>
  <title><?=isset($_GET['room']) ? ($room_name.' - ') : (isset($_GET['q'])?$question_title.' - ':'')?><?=$community_display_name?> - <?=$l_topanswers?></title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/community/community.js").preg_replace('/^&/','?',($clearlocal?'&clearlocal':''))?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <main class="pane">
    <header>
      <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
      <div class="container shrink">
        <?if(!$question){?>
          <input class="element" type="search" id="search" value="<?=$_GET['search']??''?>" placeholder="🔍&#xFE0E; <?=$l_search_placeholder?>" autocomplete="off">
          <div class="element fa fa-fw fa-spinner fa-pulse"></div>
        <?}?>
        <?if($dev){?>
          <select id="environment" class="element" style="margin: 6px;">
            <?foreach(db("select environment_name from environment") as $r){ extract($r);?>
              <option<?=($environment===$environment_name)?' selected':''?>><?=$environment_name?></option>
            <?}?>
          </select>
          <input id="poll" class="element" type="button" value="poll">
        <?}?>
      </div>
      <div>
        <?if(!$auth){?><span class="element"><input id="link" type="button" value="log in"><input id="join" type="button" value="join (sets cookie)"></span><?}?>
        <?if($auth){?>
          <?if($auth&&$communicant_can_import){?>
            <form method="post" action="//post.topanswers.xyz/import">
              <input type="hidden" name="action" value="new">
              <input type="hidden" name="community" value="<?=$community_name?>">
              <input type="hidden" name="sesiteid" value="">
              <input type="hidden" name="seids" value="">
              <a id="import" href="." class="button">Import</a>
            </form>
          <?}?>
          <a href="/question?community=<?=$community_name?>" class="button"><?=$community_ask_button_text?></a>
        <?}?>
        <?if($community_about_question_id){?>
          <a href="/<?=$community_name?>?q=<?=$community_about_question_id?>" class="frame" title="about">
            <img class="icon" src="/image?hash=55c47c43ffec88bee33f88fe6bdffbec31d6ed1dc78779a7c4e820461ff46a6b">
          </a>
        <?}?>
        <?if($auth){?><a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a><?}?>
        <div class="panecontrol fa fa-angle-double-right"></div>
      </div>
    </header>
    <div id="qa">
      <?if($question){?>
        <div id="question" data-id="<?=$question?>" class="post<?=$question_i_subscribed?' subscribed':''?><?
                                                             ?><?=$question_i_flagged?' flagged':''?><?
                                                             ?><?=$question_i_counterflagged?' counterflagged':''?><?
                                                             ?><?=$question_is_deleted?' deleted':''?>">
          <div class="title">
            <a title="<?=$question_title?>"><?=$question_is_published?'':'DRAFT: '?><?=$question_title?></a>
          </div>
          <div class="bar">
            <div class="element container shrink tags">
              <?if($sanction_short_description){?><span class="kind element"><?=$sanction_short_description?></span><?}?>
              <div id="tagbar" class="tags">
                <?foreach(db("select tag_id,tag_name,tag_implies_id from tag where tag_is order by tag_question_count desc, tag_name") as $r){ extract($r);?>
                  <a href="/tags?community=<?=$community_name?>#t<?=$tag_id?>" class="tag element" data-id="<?=$tag_id?>"><?=$tag_name?></a>
                <?}?>
                <span class="tag newtag">add tag</span>
                <input list="taglist" id="taginput" class="tag hide" autocomplete="off">
                <datalist id="taglist">
                  <?foreach(db("select tag_id,tag_name from tag where not tag_is order by tag_name") as $r){ extract($r);?>
                    <option value="<?=$tag_name?>" data-id="<?=$tag_id?>">
                  <?}?>
                </datalist>
              </div>
            </div>
            <div>
              <span class="when element" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"></span>
              <span class="element">
                <?if($question_se_question_id){?>
                  <?if($question_account_is_imported&&$question_selink_user_id>0){?>
                    <a href="<?=$sesite_url.'/users/'.$question_selink_user_id?>"><?=$question_account_name?></a>
                  <?}else{?>
                    <?=$question_account_name?>
                  <?}?>
                  (imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id?>">from SE</a>)
                <?}else{?>
                  <?=$question_account_name?>
                <?}?>
              </span>
              <a href="/user?id=<?=$question_account_id?>&community=<?=$community_name?>"><img title="Stars: <?=$question_communicant_votes?>" class="icon" src="<?=$question_account_image_url?>"></a>
            </div>
          </div>
          <div id="markdown" class="markdown" data-markdown="<?=$question_markdown?>"><pre class='noscript'><?=$question_markdown?></pre></div>
          <div class="bar">
            <div>
              <?if($kind_has_question_votes){?>
                <span class="element" data-total="<?=$question_votes?>"<?=$kind_minimum_votes_to_answer?' data-required="'.($kind_minimum_votes_to_answer-$question_votes).'"':''?>></span>
                <?if(!$question_account_is_me){?><div class="starrr element" data-id="<?=$question?>" data-type="question" data-votes="<?=$question_votes_from_me?>" title="rate this question"></div><?}?>
              <?}?>
              <?if($auth){?>
                <?if($question_account_is_me||$kind_can_all_edit){?><a class="element" href="/question?id=<?=$question?>"><?=$l_edit?></a><?}?>
                <?if($question_has_history){?><a class="element" href="/question-history?id=<?=$question?>"><?=$l_history?></a><?}?>
                <?if(!$question_account_is_me){?><a class="element comment pingable" href="." data-id="<?=$question_account_id?>" data-name="<?=explode(' ',$question_account_name)[0]?>" data-fullname="<?=$question_account_name?>">comment</a><?}?>
                <a class="element license" href="."><?=$l_license?></a>
                <span class="element" style="display: none;">
                  <a href="<?=$question_license_href?>" title="<?=$question_license_description?>"><?=$question_license_name?></a>
                  <?if($question_has_codelicense){?>
                    <span> + </span>
                    <a href="/meta?q=24" title="<?=$question_codelicense_description?>"><?=$question_codelicense_name?> for original code</a>
                  <?}?>
                </span>
              <?}?>
            </div>
            <?if($auth){?>
              <div class="shrink">
                <?if(($question_crew_flags===0)||$communicant_is_post_flag_crew){?>
                  <?if($question_active_flags<>0){?>
                    <div class="element container shrink">
                      <span>flagged by:</span>
                      <div class="container shrink">
                        <?foreach(db("select question_flag_account_id,question_flag_account_name,question_flag_is_crew,question_flag_direction,question_flag_account_image_url
                                      from question_flag
                                      where question_flag_account_id<>$1
                                      order by question_flag_is_crew, question_flag_at",$account_id) as $i=>$r){ extract($r);?>
                          <img class="icon pingable"
                               title="<?=$question_flag_account_name?><?=$question_flag_is_crew?(($question_flag_direction===1)?' (crew)':' (crew, counter-flagged)'):''?>"
                               data-id="<?=$question_flag_account_id?>"
                               data-name="<?=explode(' ',$question_flag_account_name)[0]?>"
                               data-fullname="<?=$question_flag_account_name?>"
                               src="<?=$question_flag_account_image_url?>">
                        <?}?>
                      </div>
                    </div>
                  <?}?>
                  <?if($question_is_published){?>
                    <div class="element fa fw fa-flag" title="unflag this question"></div>
                    <div class="element fa fw fa-flag-o" title="flag this question (n.b. flags are public)"></div>
                    <?if($communicant_is_post_flag_crew&&($question_active_flags>0)){?>
                      <div class="element fa fw fa-flag-checkered" title="counterflag"></div>
                    <?}?>
                  <?}?>
                <?}?>
                <a href="/<?=$community_name?>?q=<?=$question_id?>" class="element fa fw fa-link" title="permalink"></a>
                <div class="element fa fw fa-bell" title="unsubscribe from this question"></div>
                <div class="element fa fw fa-bell-o" title="subscribe to this question"></div>
              </div>
            <?}?>
          </div>
          <?if($kind_show_answer_summary_toc&&$question_is_answered){?>
            <div style="height: 3px; background: rgba(var(--rgb-dark),0.6);"></div>
            <div class="answers">
              <?foreach(db("select answer_id,answer_change,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_account_name,answer_is_deleted,answer_communicant_votes
                                  ,answer_summary,answer_account_image_url
                                  ,label_name,label_url
                                 , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                                 , to_char(answer_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_change_at_iso
                                 , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                                 , extract('epoch' from current_timestamp-answer_change_at)::bigint answer_change_when
                            from answer
                            order by answer_votes desc, answer_communicant_votes desc, answer_id desc") as $i=>$r){ extract($r);?>
                <div class="bar<?=$answer_is_deleted?' deleted':''?>">
                  <div>
                    <?if($label_name){?>
                      <?if($label_url){?>
                        <a href="<?=$label_url?>" class="label element"><?=$label_name?></a>
                      <?}else{?>
                        <span class="label element"><?=$label_name?></span>
                      <?}?>
                    <?}?>
                    <a href="/<?=$community_name?>?q=<?=$question?>#a<?=$answer_id?>" class="element summary shrink"><span data-markdown="<?=$answer_summary?>"><?=$answer_summary?></span></a>
                  </div>
                  <div>
                    <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
                    <?if($answer_votes){?>
                      <span class="element">
                        <i class="fa fa-star<?=(($answer_account_id!==$account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>" data-count="<?=$answer_votes?>"></i>
                      </span>
                    <?}?>
                    <span class="element"><?=$answer_account_name?></span>
                    <img title="Stars: <?=$answer_communicant_votes?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="<?=$answer_account_image_url?>">
                  </div>
                </div>
              <?}?>
            </div>
          <?}?>
        </div>
        <?if($kind_has_answers&&$question_is_published){?>
          <div class="banner">
            <h3><?=$question_answer_count?> Answer<?=($question_answer_count!==1)?'s':''?></h3>
            <div style="flex: 1 1 0;"></div>
            <a <?=($auth&&( $question_votes>=$kind_minimum_votes_to_answer ))?'href="/answer?question='.$question.'"':'title="requires '.($kind_minimum_votes_to_answer-$question_votes).' more stars"'?> class="button"><?=$question_answered_by_me?$l_provide_another_answer:$l_provide_answer?></a>
          </div>
        <?}?>
        <?foreach(db("select answer_id,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_has_history,answer_account_image_url
                            ,answer_license_href,answer_license_name,answer_codelicense_name,answer_license_description,answer_codelicense_description,answer_account_name,answer_account_is_imported
                            ,answer_communicant_votes,answer_selink_user_id,answer_se_answer_id,answer_i_flagged,answer_i_counterflagged,answer_crew_flags,answer_active_flags,label_name,label_url,label_code_language
                           , answer_account_id=$1 answer_account_is_me
                           , answer_crew_flags>0 answer_is_deleted
                           , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                           , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                           , answer_codelicense_id<>1 and answer_codelicense_name<>answer_license_name answer_has_codelicense
                           , answer_active_flags>(answer_i_flagged::integer) answer_other_flags
                      from answer
                      order by answer_votes desc, answer_communicant_votes desc, answer_id desc",$account_id) as $i=>$r){ extract($r);?>
          <div id="a<?=$answer_id?>" data-id="<?=$answer_id?>"<?if($label_code_language){?> style="--lang-code:<?=$label_code_language?>"<?}?>
               class="post answer<?=$answer_i_flagged?' flagged':''?><?=$answer_i_counterflagged?' counterflagged':''?><?=$answer_is_deleted?' deleted':''?>">
            <div class="bar">
              <div>
                <span class="element"><?=($i===0)?'Top Answer':('Answer #'.($i+1))?></span>
                <?if($label_name){?>
                  <?if($label_url){?>
                    <a href="<?=$label_url?>" class="label element"><?=$label_name?></a>
                  <?}else{?>
                    <span class="label element"><?=$label_name?></span>
                  <?}?>
                <?}?>
              </div>
              <div>
                <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
                <span class="element">
                  <?if($answer_se_answer_id){?>
                    <?if($answer_account_is_imported&&$answer_selink_user_id>0){?>
                      <a href="<?=$sesite_url.'/users/'.$answer_selink_user_id?>"><?=$answer_account_name?></a>
                    <?}else{?>
                      <?=$answer_account_name?>
                    <?}?>
                    (imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id.'/'.$answer_se_answer_id.'/#'.$answer_se_answer_id?>">from SE</a>)
                  <?}else{?>
                    <?=$answer_account_name?>
                  <?}?>
                </span>
                <a href="/user?id=<?=$answer_account_id?>&community=<?=$community_name?>"><img title="Stars: <?=$answer_communicant_votes?>" class="icon" src="<?=$answer_account_image_url?>"></a>
              </div>
            </div>
            <div class="markdown" data-markdown="<?=$answer_markdown?>"><pre class='noscript'><?=$answer_markdown?></pre></div>
            <div class="bar">
              <div>
                <?if($kind_has_answer_votes){?>
                  <span class="element" data-total="<?=$answer_votes?>"></span>
                  <?if(!$answer_account_is_me){?>
                    <div class="element starrr" data-id="<?=$answer_id?>" data-type="answer" data-votes="<?=$answer_votes_from_me?>" title="rate this answer"></div>
                  <?}?>
                <?}?>
                <?if($auth){?>
                  <a class="element" href="/answer?id=<?=$answer_id?>"><?=$l_edit?></a>
                  <?if($answer_has_history){?><a class="element" href="/answer-history?id=<?=$answer_id?>"><?=$l_history?></a><?}?>
                  <?if(!$answer_account_is_me){?><a class="element comment pingable" href="." data-id="<?=$answer_account_id?>" data-name="<?=explode(' ',$answer_account_name)[0]?>" data-fullname="<?=$answer_account_name?>">comment</a><?}?>
                  <a class="element license" href="."><?=$l_license?></a>
                  <span class="element" style="display: none;">
                    <a href="<?=$answer_license_href?>" title="<?=$answer_license_description?>"><?=$answer_license_name?></a>
                    <?if($answer_has_codelicense){?>
                      <span> + </span>
                      <a href="/meta?q=24" title="<?=$answer_codelicense_description?>"><?=$answer_codelicense_name?> for original code</a></span>
                    <?}?>
                  </span>
                <?}?>
              </div>
              <?if($auth){?>
                <div class="shrink">
                  <?if(($answer_crew_flags===0)||$communicant_is_post_flag_crew){?>
                    <?if($answer_other_flags){?>
                      <div class="element container shrink">
                        <span>flagged by:</span>
                        <div class="container shrink">
                          <?foreach(db("select answer_flag_is_crew,answer_flag_direction,answer_flag_account_id,answer_flag_account_name,answer_flag_account_image_url
                                        from answer_flag
                                        where answer_id=$1 and answer_flag_account_id<>$2
                                        order by answer_flag_is_crew, answer_flag_at",$answer_id,$account_id) as $i=>$r){ extract($r);?>
                            <img class="icon pingable"
                                 title="<?=$answer_flag_account_name?><?=$answer_flag_is_crew?(($answer_flag_direction===1)?' (crew)':' (crew, counter-flagged)'):''?>"
                                 data-id="<?=$answer_flag_account_id?>"
                                 data-name="<?=explode(' ',$answer_flag_account_name)[0]?>"
                                 data-fullname="<?=$answer_flag_account_name?>"
                                 src="<?=$answer_flag_account_image_url?>">
                          <?}?>
                        </div>
                      </div>
                    <?}?>
                    <div class="element fa fw fa-flag" title="unflag this answer"></div>
                    <div class="element fa fw fa-flag-o" title="flag this answer (n.b. flags are public)"></div>
                    <?if($communicant_is_post_flag_crew&&$answer_other_flags){?>
                      <div class="element fa fw fa-flag-checkered" title="counterflag"></div>
                    <?}?>
                  <?}?>
                  <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" class="element fa fw fa-link" title="permalink"></a>
                </div>
              <?}?>
            </div>
          </div>
        <?}?>
      <?}else{?>
        <?if($community_banner_markdown){?>
          <div id="info">
            <div class="markdown" data-markdown="<?=htmlspecialchars($community_banner_markdown)?>">&nbsp;</div>
          </div>
        <?}?>
        <div class="pages"><div></div></div>
        <div id="questions">
          <?$ch = curl_init('http://127.0.0.1/questions?community='.$community_name.(isset($_GET['page'])?'&page='.$_GET['page']:'').(isset($_GET['search'])?'&search='.urlencode($_GET['search']):'')); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
        </div>
        <div class="pages"><div></div></div>
      <?}?>
    </div>
  </main>
  <div id="dummyresizer"></div>
  <div id="chat-wrapper" class="pane hidepane">
    <footer>
      <div id="community-rooms">
        <div>
          <div class="panecontrol fa fa-angle-double-left hidepane"></div>
          <a class="frame this"<?=$dev?' href="/room?id='.$room.'" title="room settings"':''?> title="<?=$room_name?>" data-id="<?=$room?>"><img class="icon roomicon" src="<?=$room_image_url?>"></a>
          <div class="element shrink" title="<?=$room_name?>"><?=$room_name?></div>
        </div>
        <div>
          <?if($auth){?>
            <?$ch = curl_init('http://127.0.0.1/pinnedrooms?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
            <a id="more-rooms" class="frame none" href="." title="more rooms"><img class="icon roomicon" src="/image?hash=560e3af97ebebc1189b630f64012ae2adca14ecedb6d86e51823f5f180786f8f"></a>
          <?}?>
        </div>
      </div>
      <div id="active-rooms">
      </div>
    </footer>
    <div id="chat-bar" class="label container">
      <div class="element">
        <a class="panel" data-panel="messages-wrapper"><?=$question?$l_comments:$l_chat?></a>
        <?if($auth){?> / <a class="panel" data-panel="starboard" href="."><?=$l_starred?></a><?}?>
        <?if($auth){?> / <a class="panel" data-panel="notifications" href="."><?=$l_notifications?></a><?}?>
      </div>
      <div class="element">
        <a href="/transcript?room=<?=$room?>"><?=$l_transcript?></a>
      </div>
      <div class="element">
        <?if($auth){?>
          <?if($room_can_listen){?><a id="listen" href="."><?=$l_listen?></a><?}?>
          <?if($room_can_mute){?><a id="mute" href="."><?=$l_mute?></a><?}?>
          <?if($room_is_pinned){?><a id="unpin" href="."><?=$l_unpin?></a><?}else{?><a id="pin" href="."><?=$l_pin?></a><?}?>
          <?if($room_show_minimap){?><a id="map" href="."><?=$l_map?></a><?}?>
        <?}?>
      </div>
    </div>
    <div id="chat-panels">
      <div id="messages-wrapper" class="panel">
        <div id="chat">
          <div class="firefoxwrapper">
            <div id="messages">
              <?if(!$room_has_chat){?>
                <?if($question){?>
                  <div style="padding: 10vh 20%; flex: 1 0 auto;">
                    <?if($auth){?>
                      <?if($question_se_question_id){?>
                        <p>This room is for discussion about this imported question.</p>
                      <?}else{?>
                        <p>This room is for discussion about this question.</p>
                        <p>You can direct a comment to any contributor via the 'comment' link under their post.</p>
                      <?}?>
                    <?}else{?>
                      <p>This room is for discussion about this question.</p>
                      <p>Once logged in you can direct comments to any contributor here.</p>
                    <?}?>
                  </div>
                <?}?>
              <?}?>
            </div>
          </div>
          <?if($room_show_minimap){?>
            <div id="minimap-wrapper">
              <?include '../../../icons/calendar.html';?>
              <div class="divider"></div>
              <?include '../../../icons/step-up.html';?>
              <a href='.' id="minimap"><div></div><img src="/chat?room=<?=$room?>&minimap" ismap></a>
              <?include '../../../icons/step-down.html';?>
            </div>
          <?}?>
          <?if($auth){?>
            <div id="active-users"><?$ch = curl_init('http://127.0.0.1/activeusers?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?></div>
          <?}?>
        </div>
        <?if($canchat){?>
          <div id="canchat-wrapper" class="<?=$hidepreview?'':'previewing '?><?=$hidekeyboard?'':'keyboard '?>">
            <div id="status" data-replyid="" data-replyname="" data-editid="">
              <span><?=$l_preview?>:</span>
              <i id="cancel" class="fa fa-fw fa-times" style="display: none; cursor: pointer;"></i>
            </div>
            <div id="preview" class="message processed"><div class="markdown" data-markdown="">&nbsp;</div></div>
            <form action="/upload" method="post" enctype="multipart/form-data"><input id="chatuploadfile" name="image" type="file" accept="image/*"></form>
            <div id="chattext-wrapper">
              <textarea id="chattext" rows="1" placeholder="<?=$l_chattext_placeholder?>" maxlength="5000"></textarea>
              <div id="chatbuttons">
                <i id="chatshowpreview" class="fa fa-fw fa-eye" title="<?=$l_show_preview?>"></i>
                <i id="chathidepreview" class="fa fa-fw fa-eye-slash" title="<?=$l_hide_preview?>"></i>
                <i id="chatupload" class="fa fa-fw fa-picture-o" title="<?=$l_embed_image?>"></i>
                <?if($communicant_keyboard){?>
                  <i id="chatshowkeyboard" class="fa fa-fw fa-keyboard-o" title="<?=$l_show_keyboard?>"></i>
                  <i id="chathidekeyboard" class="fa fa-fw fa-keyboard-o" title="<?=$l_hide_keyboard?>"></i>
                <?}?>
              </div>
            </div>
            <div id="keyboard">
              <?foreach(explode(' ',$communicant_keyboard) as $group){?>
                <span>
                  <?foreach(preg_split('//u',$group,-1,PREG_SPLIT_NO_EMPTY) as $c){?><span><?=$c?></span><?}?>
                </span>
              <?}?>
            </div>
          </div>
        <?}?>
      </div>
      <div id="scrollup" class="panel" style="visibility: hidden; z-index: -1;"></div>
      <div id="scrolldown" class="panel" style="visibility: hidden; z-index: -1;"></div>
      <div id="jumpchat" class="panel" style="visibility: hidden; z-index: -1;"></div>
      <div id="newchat" class="panel" style="visibility: hidden; z-index: -1;">
        <?if($room_has_chat){?>
          <?$ch = curl_init('http://127.0.0.1/chat?room='.$room.'&limit=50'); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
        <?}?>
      </div>
      <?if($auth){?>
        <div class="firefoxwrapper" class="panel" style="visibility: hidden; z-index: -1;">
          <div id="starboard">
            <?$ch = curl_init('http://127.0.0.1/starboard?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
            <div style="flex: 1 0 0;"></div>
          </div>
        </div>
        <div id="notifications" class="panel" style="visibility: hidden; z-index: -1;">
          <?$ch = curl_init('http://127.0.0.1/notification?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
        </div>
      <?}?>
    </div>
  </div>
  <dialog style="display: none;">
    <p>Enter question or answer id or url (and optionally further answer ids/urls from the same question) from
       <select name="site"><?foreach(db("select sesite_id,sesite_url,source_is_default from sesite") as $r){extract($r,EXTR_PREFIX_ALL,'s');?><option value="<?=$s_sesite_id?>"<?=$s_source_is_default?" selected":""?>><?=$s_sesite_url?></option><?}?></select>
    </p>
    <p>Separate each id/url with a space. No need to list your own answers; they will be imported automatically.</p>
    <input name="ids">
  </dialog>
</body>
</html>
<?//ob_end_flush();
