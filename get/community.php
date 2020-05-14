<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to community,pg_temp");
if(isset($_COOKIE['uuid'])){ ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied'); }
if(ccdb("select exists(select 1 from private where community_name=$1)",$_GET['community'])) { header('Location: //topanswers.xyz/private?community='.$_GET['community']); exit; }
$pagesize = $_COOKIE['pagesize']??'10';
$hidepreview = ($_COOKIE['hidepreview']??'false') === 'true';
$clearlocal = $_COOKIE['clearlocal']??'';
$environment = $_COOKIE['environment']??'prod';
setcookie('clearlocal','',0,'/','topanswers.xyz',true,true);
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
                   ,account_id,account_is_dev,account_notification_id
                   ,community_id,community_name,community_language,community_display_name,community_my_power,community_code_language,community_tio_language,community_about_question_id,community_ask_button_text,community_banner_markdown
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_tables_are_monospace
                   ,communicant_is_post_flag_crew,communicant_can_import
                   ,room_id,room_name,room_can_chat,room_has_chat,room_can_mute,room_can_listen,room_is_pinned
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,sesite_url
                   ,question_id,question_title,question_markdown,question_votes,question_license_name,question_se_question_id,question_crew_flags,question_active_flags
                   ,question_has_history,question_is_deleted,question_votes_from_me,question_answered_by_me,question_is_answered,question_answer_count,question_i_subscribed,question_i_flagged,question_i_counterflagged
                   ,question_when
                   ,question_account_id,question_account_is_me,question_account_name,question_account_is_imported
                   ,question_selink_user_id,question_communicant_votes
                   ,question_license_href,question_has_codelicense,question_codelicense_name,question_license_description,question_codelicense_description
                  , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                   ,kind_short_description,kind_can_all_edit,kind_has_answers,kind_has_question_votes,kind_has_answer_votes,kind_minimum_votes_to_answer,kind_allows_question_multivotes,kind_allows_answer_multivotes
                   ,kind_show_answer_summary_toc
             from one"));
$dev = $account_is_dev;
$_GET['community']===$community_name || fail(400,'invalid community');
include '../lang/community.'.$community_language.'.php';
$jslang = substr($community_language,0,1).substr(strtok($community_language,'-'),-1);
$question = $_GET['q']??'0';
$room = $room_id;
$canchat = $room_can_chat;
$cookies = (isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; ':'').(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':'');
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--rgb-dark: <?=$community_rgb_dark?>;
             --rgb-mid: <?=$community_rgb_mid?>;
             --rgb-light: <?=$community_rgb_light?>;
             --rgb-highlight: <?=$community_rgb_highlight?>;
             --rgb-warning: <?=$community_rgb_warning?>;
             --rgb-white: 255, 255, 255;
             --rgb-black: 0, 0, 0;
             --regular-font-family: '<?=$my_community_regular_font_name?>', serif;
             --monospace-font-family: '<?=$my_community_monospace_font_name?>', monospace;
             --markdown-table-font-family: <?=$community_tables_are_monospace?"'".$my_community_monospace_font_name."', monospace":"'".$my_community_regular_font_name."', serif;"?>
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/select2.css">
  <link rel="stylesheet" href="/lib/starrr.css">
  <link rel="stylesheet" href="/lib/vex/vex.css">
  <link rel="stylesheet" href="/lib/vex/vex-theme-topanswers.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="icon" href="/communityicon?community=<?=$community_name?>" type="image/png">
  <noscript>
    <style>
      .message:not(.processed) { opacity: unset !important; }
      .notification:not(.processed) { opacity: unset !important; }
      #qa .post:not(.processed) { opacity: unset !important; }
      .markdown>pre.noscript { white-space: pre-wrap; }
    </style>
  </noscript>
  <style>
    html { box-sizing: border-box; font-family: var(--regular-font-family); font-size: 16px; }
    body { display: flex; background: rgb(var(--rgb-mid)); }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { flex-direction: column; flex: 1 1 <?=$login_resizer_percent?>%; overflow: hidden; }

    footer { min-height: 30px; flex: 0 0 auto; font-size: 14px; padding-right: 2px; background: rgb(var(--rgb-dark)); color: rgb(var(--rgb-light)); white-space: nowrap; margin: 0 -1px; position: relative; }
    footer .icon { height: 24px; width: 24px; margin: 0; }
    #community-rooms { display: flex; padding: 1px 0; }
    #community-rooms>div:first-child { flex: 1 1 auto; display: flex; align-items: center; height: 100%; overflow: hidden; }
    #community-rooms>div:first-child>div:last-child { overflow: hidden; text-overflow: ellipsis; }
    #community-rooms>div:last-child { flex: 0 0 auto; display: flex; align-items: center; height: 100%; }
    #community-rooms>div:last-child a.this{ pointer-events: none; opacity: 0.3; }
    #active-rooms { display: none; position: absolute; top: calc(100% - 1px); right: 0; left: 0; background: rgb(var(--rgb-dark)); z-index: 1; padding: 0 2px 1px 0; }
    #active-rooms>div { display: flex; flex-direction: row-reverse; overflow-y: hidden; overflow-x: auto; }
    footer a.frame { position: relative; }
    footer a.frame[data-unread]:after { content:attr(data-unread-lang); position: absolute; bottom: 1px; right: 1px; font-family: sans-serif; font-size: 9px; background: rgb(var(--rgb-highlight));
                                                         color: rgb(var(--rgb-black)); width: 12px; height: 12px; text-align: center; line-height: 13px; border-radius: 30%; pointer-events: none;
                                                         box-shadow: -1px -1px 1px 1px #fffe; }
    #more-rooms.none { pointer-events: none; opacity: 0.5; }

    textarea, pre, code, .CodeMirror { font-family: var(--monospace-font-family); }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; }
    .icon:not(.roomicon) { border-radius: 2px; }
    .icon.pingable:not(.ping):hover { box-shadow: 0 0 0 1px rgb(var(--rgb-dark)); cursor: pointer; transition: box-shadow linear 0.1s; }
    .icon.ping { box-shadow: 0 0 0 1px rgb(var(--rgb-highlight)); }
    <?if($dev){?>.changed { outline: 2px solid orange; }<?}?>
    .spacer { flex: 0 0 auto; min-height: 13px; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: rgba(var(--rgb-dark),0.6); pointer-events: none; }

    .select2-dropdown { border: 1px solid rgb(var(--rgb-dark)) !important; box-shadow: 0 0 0.2rem 0.3rem rgb(var(--rgb-white)); }
    a[data-lightbox] img { cursor: zoom-in; }

    #qa { overflow: auto; scroll-behavior: smooth; }
    #qa #info { color: rgb(var(--rgb-mid)); padding: 6px; background: rgb(var(--rgb-mid)); font-size: 12px; }
    #qa .banner { display: flex; margin: 10px; align-items: center; }
    #qa .banner .button:last-child { margin-right: 0; }
    #qa .banner h3 { color: rgb(var(--rgb-dark)); font-weight: normal; margin: 0; }
    @supports (-webkit-touch-callout: none) { #qa * { -webkit-transform: translate3d(0, 0, 0); } }
    .pages { margin: 9px; display: flex; justify-content: center; }
    .pages select { height: 38px; margin: 2px; margin-left: 8px; padding: 0 8px; background: rgb(var(--rgb-light)); outline: 1px solid rgb(var(--rgb-dark));
                    font-family: var(--regular-font-family); font-size: 16px;
                    border: none; text-align: center; text-align-last: center; -moz-appearance: none; appearance: none; -webkit-appearance: none; }
    .pages select:focus { outline-offset: 0; }
    .pages option { height: 38px; margin: 2px; background: rgb(var(--rgb-light)); font-family: var(--regular-font-family); font-size: 16px; text-align: center; -moz-appearance: none; }

    <?if($question){?>
      <?if($auth){?>.tag:hover i { visibility: visible; }<?}?>
      .newtag { position: relative; cursor: pointer; margin-left: 0; }
      .newtag .tag { opacity: 0.4; margin: 0; }
      .newtag:hover .tag { opacity: 1; }
      .newtag>div { position: absolute; top: -4px; left: 1px; z-index: 1; visibility: hidden; }

      #qa .post:target { box-shadow: 0 0 1px 2px rgb(var(--rgb-highlight)); }
      #qa .post.answer:not(:last-child) { margin-bottom: 40px; }
      #qa .markdown { border: 1px solid rgb(var(--rgb-dark),0.6); border-width: 1px 0; padding: 8px; }
      #qa .bar .element.fa { cursor: pointer; font-size: 16px; }
      #qa .bar .element.fa-bell { color: rgb(var(--rgb-highlight)); }
      #qa .bar .element.fa-flag { color: rgb(var(--rgb-warning)); }
      #qa .bar .starrr { display: flex; }
      #qa .bar .starrr a.fa-star { color: rgb(var(--rgb-highlight)); }
      #qa .bar .starrr a.fa-star-o { color: rgb(var(--rgb-dark)); }
      #qa .bar [data-total]::after { content: attr(data-total) ' stars'; }
      #qa .bar [data-total="1"]::after { content: attr(data-total) ' star'; margin-right: 0.4em; }
      #qa .bar [data-total][data-required]:not([data-required="0"]):not([data-required^="-"])::after { content: attr(data-total) ' stars (' attr(data-required) ' more required to launch)'; }
      #qa .bar [data-total="1"][data-required]:not([data-required="0"]):not([data-required^="-"])::after { content: attr(data-total) ' star (' attr(data-required) ' more required to launch)'; margin-right: 0.4em; }
      #qa .post:not(.subscribed) .bar .element.fa-bell { display: none; }
      #qa .post.subscribed .bar .element.fa-bell-o { display: none; }
      #qa .post:not(.flagged) .bar .element.fa-flag { display: none; }
      #qa .post.flagged .bar .element.fa-flag-o { display: none; }
      #qa .post.counterflagged .bar .element.fa-flag-checkered { color: rgb(var(--rgb-highlight)); }
    <?}?>

    #chat-wrapper { font-size: 14px; flex: 1 1 <?=100-$login_resizer_percent?>%; flex-direction: column; justify-content: flex-start; min-width: 0; overflow: hidden; }
    #chat-wrapper .label { font-size: 12px; padding: 2px 0 1px 0; border-bottom: 1px solid rgb(var(--rgb-dark)); }
    #chat-wrapper .roomtitle { flex: 0 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    #chat { display: flex; flex: 1 0 0; min-height: 0; }
    #chat-panels { display: flex; position: relative; flex: 1 1 auto; flex-direction: column; overflow: hidden; background: rgb(var(--rgb-light)); }
    #chat-panels>div { position: absolute; width: 100%; height: 100%; top: 0; left: 0; }

    #notifications { overflow-x: hidden; overflow-y: auto; flex: 1 1 auto; display: flex; visibility: hidden; flex-direction: column; scroll-behavior: smooth; }
    #notifications>hr { margin: 14px 6px; border: 1px solid rgb(var(--rgb-dark)); }
    #more-notifications { display: block; text-align: center; font-size: 12px; margin: 10px;}
    #messages-wrapper { overflow: hidden; flex: 1 1 auto; display: flex; flex-direction: column; }
    #messages { display: flex; flex-direction: column-reverse; overflow-x: hidden; overflow-y: auto; background: rgb(var(--rgb-mid)); padding: 4px; }
    .newscroll { border-bottom: 3px solid rgb(var(--rgb-highlight)); }
    .firefoxwrapper { overflow-y: auto; overflow-x: hidden; height: 100%; flex: 1 1 0; }
    .firefoxwrapper>* { min-height: 100%; }
    #messages .message .who { top: -1.3em; }
    #messages .message:not(:hover) .when { opacity: 0; }
    #starboard { background: rgb(var(--rgb-mid)); overflow-x: hidden; overflow-y: auto; display: flex; flex-direction: column-reverse; }
    #starboard .message { padding: 4px; padding-top: 1.3em; }
    #starboard .message:not(:first-child) { border-bottom: 1px solid rgba(var(--rgb-dark),0.6); }
    #starboard .message .who { top: 0.2rem; font-size: 12px; }
    #starboard .message .button-group:not(:first-child) .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    #starboard .message .button-group:first-child { display: none; }
    #starboard .message:not(:hover) .button-group:not(:first-child) { display: grid; }
    #starboard .message:not(:hover) .button-group:not(:first-child) .fa-link { display: none; }

    #preview { display: flex; width: 100%; background: rgb(var(--rgb-light)); cursor: default; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; }
    #canchat-wrapper:not(.previewing) #preview { display: none; }
    #canchat-wrapper.previewing #chatshowpreview { display: none; }
    #canchat-wrapper:not(.previewing) #chathidepreview { display: none; }
    #preview .markdown { margin: 4px; }
    #canchat-wrapper:not(.previewing) #preview { display: none; }
    #canchat-wrapper:not(.chatting) #preview { visibility: hidden; }
    #canchat-wrapper:not(.chatting):not(.pinging) #status>span { visibility: hidden; }
    #canchat-wrapper:not(.previewing):not(.pinging) #status>span { visibility: hidden; }
    #canchat-wrapper { flex: 0 0 auto; }
    #chattext-wrapper { display: flex; border-top: 1px solid rgb(var(--rgb-dark)); background: rgb(var(--rgb-white)); }
    #chatuploadfile { display: none; }
    #chatbuttons { display: flex; flex-wrap: wrap; background: white; align-items: center; align-content: center; width: 26px; }
    #chatbuttons>i { width: 26px; font-size: 22px; color: rgb(var(--rgb-dark)); cursor: pointer; }
    #chatbuttons>i:active { color: rgb(var(--rgb-mid)); }
    #chattext { flex: 1 1 auto; font-family: inherit; font-size: 14px; resize: none; outline: none; border: none; padding: 4px; margin: 0; background: rgb(var(--rgb-white)); color: rgb(var(--rgb-black)); }
    #status { padding: 2px; width: 100%; line-height: 13px; font-size: 12px; border-top: 1px solid rgb(var(--rgb-dark)); }

    #chat-bar a.panel { pointer-events: none; }
    #chat-bar a[href].panel { pointer-events: auto; }
    #chat-bar a.panel[data-unread]:not([data-unread^="0"])::after { display: inline-block; vertical-align: middle; content:attr(data-unread-lang); margin-left: 2px; font-family: sans-serif; font-size: 9px; background: rgb(var(--rgb-highlight)); color: rgb(var(--rgb-black));
                                                               width: 12px; height: 12px; text-align: center; line-height: 13px; border-radius: 30%; pointer-events: none; box-shadow: 0 0 2px 2px #fffd; text-shadow: 0 0 1px rgb(var(--rgb-white)); }

    .message { position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message:not(.processed) { opacity: 0; }
    .message .who { white-space: nowrap; font-size: 10px; position: absolute; }
    .message .who>a { color: rgb(var(--rgb-dark)); }
    .message .who>a[href^='#'] { text-decoration: none; }
    .message .when { color: rgb(var(--rgb-dark)); transition: opacity linear 0.1s; }
    .message .markdown { flex: 0 1 auto; max-height: 30vh; padding: 4px; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; background: rgb(var(--rgb-white)); overflow: auto; transition: background linear 0.1s; }
    .message .markdown img { max-height: 120px; }
    .message .button-group { display: grid; grid-template: 11px 11px / 12px 12px; align-items: center; justify-items: start; font-size: 11px; margin-top: 1px; margin-left: 1px; }
    .message .button-group:first-child { grid-template: 11px 11px / 22px 2px; }
    .message .button-group .fa { color: rgb(var(--rgb-dark)); cursor: pointer; text-decoration: none; }
    .message .button-group .fa-star.me { color: rgb(var(--rgb-highlight)); }
    .message .button-group .fa-flag.me { color: rgb(var(--rgb-warning)); }
    .message:hover .button-group:first-child { display: none; }
    .message .button-group:not(.show) { display: none; }
    .message:not(:hover) .button-group:not(:first-child) { display: none; }
    .message .button-group:first-child .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    .message .button-group:first-child .fa[data-count][data-count="0"] { visibility: hidden; }
    .message.merged>.markdown { margin-top: -1px; }
    .message.merged>.who, .message.merged>.icon { visibility: hidden; }
    .message:target .markdown { box-shadow: 0 0 2px 2px rgb(var(--rgb-highlight)) inset; }
    .message.thread .markdown { background: linear-gradient(rgba(var(--rgb-highlight),0.25),rgba(var(--rgb-highlight),0.25)), rgb(var(--rgb-white)); }
    .message:not(:target).notify .markdown { box-shadow: 0 0 2px 2px rgb(var(--rgb-dark)) inset; }

    .notification { flex: 0 0 auto; padding: 4px; border-radius: 3px; margin: 3px; border: 1px solid rgba(var(--rgb-dark),0.6); }
    .notification:not(.processed) { opacity: 0; }
    .notification:not(.message) { display: flex; overflow: hidden; font-size: 12px; white-space: nowrap; }
    .notification.message { padding-top: 1.3em; }
    .notification.message .who { display: flex; width: calc(100% - 8px); overflow: hidden; top: 0.2rem; font-size: 12px; }
    .notification.message .who > span { color: rgb(var(--rgb-dark)); }
    .notification.message .who > a['href'^='#'] { text-decoration: none; }
    .notification .when { color: rgb(var(--rgb-dark)); }
    .notification .ellipsis { overflow: hidden; text-overflow: ellipsis; }
    .notification .fa.fa-times-circle { color: rgb(var(--rgb-warning)); cursor: pointer; margin: 0 3px 0 1px; }
    .notification .fa.fa-spinner { color: rgb(var(--rgb-dark)); }
    .notification>a { color: rgb(var(--rgb-dark)); }
    .notification + .notification { margin-top: 0; }

    #active-users { flex: 0 0 auto; display: flex; flex-direction: column-reverse; overflow-y: hidden; }

    .simple-pagination { list-style: none; display: block; overflow: hidden; padding: 0 5px 5px 0; margin: 0; list-style: none; padding: 0; margin: 0; }
    .simple-pagination ul { display: flex; padding: 0; margin: 2px; }
    .simple-pagination li { position:relative; flex: 0 0 auto; list-style: none; outline-left: 1px solid rgb(var(--rgb-dark)); }
    .simple-pagination li>span { user-select: none; }
    .simple-pagination li>* { display: block; height: 38px; width: 38px; line-height: 38px; text-decoration: none; color: rgb(var(--rgb-black)); text-align: center; background: rgb(var(--rgb-light)); outline: 1px solid rgb(var(--rgb-dark)); }
    .simple-pagination li+li>* { border-left: 0; }
    .simple-pagination li.disabled>* { color: rgba(0,0,0,0.4); }
    .simple-pagination li:not(.disabled):not(.active):hover>* { background: rgb(var(--rgb-mid)); }
    .simple-pagination li>.current:not(.prev):not(.next) { position: relative; z-index: 1; outline: 2px solid rgb(var(--rgb-highlight)); }
    .simple-pagination li>.ellipse { padding: 0 10px; user-select: none; }
    .simple-pagination li>.prev { border-radius: 3px 0 0 3px; }
    .simple-pagination li>.next { border-radius: 0 3px 3px 0; }

    #search { flex: 0 1 300px; min-width: 0; background: rgba(var(--rgb-light)); border: 1px solid rgb(var(--rgb-mid)); border-radius: 3px; padding: 4px; }
    #search:focus { background: rgb(var(--rgb-white)); }
    #search+div { display: none; }

    #dummyresizerx { background: rgb(var(--rgb-black)); flex: 0 0 6px; }

    .pane { display: flex; }
    .panecontrol { display: none; width: 28px; font-size: 24px; text-align: center; flex: 0 0 auto; }
    @media (max-width: 576px){
      .hidepane { display: none; }
      .panecontrol { display: unset; }
      textarea,select,input { font-size: 16px !important; }
      #search { line-height: 1; align-self: start; height: 26px; padding: 0 4px; }
      #chattext-wrapper:not(:hover) button { display: unset; }
      #poll { display: none; }
      #se { display: none; }
      #chat-wrapper { margin: 0; }
      .simple-pagination li>* { height: 22px; width: 22px; line-height: 22px; font-size: 12px; }
      .pages select { display: none; }
      [data-rz-handle] { display: none; }
      #search { flex: 0 1 570px; }
    }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/jquery.waitforimages.js"></script>
  <script src="/lib/vex/vex.combined.min.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
  <script src="/lib/resizer.js"></script>
  <script src="/lib/select2.js"></script>
  <script src="/lib/starrr.js"></script>
  <script src="/lib/jquery.simplePagination.js"></script>
  <script src="/lib/paste.js"></script>
  <script>
    moment.locale('<?=$jslang?>');
    $(function(){
      var title = document.title, latestChatId;
      var chatTimer, maxChatChangeID = 0, maxActiveRoomChatID = 0, maxNotificationID = <?=$auth?$account_notification_id:'0'?>, numNewChats = 0;
      var maxQuestionPollMajorID = 0, maxQuestionPollMinorID = 0;
      var dismissed = 0;

      vex.defaultOptions.className = 'vex-theme-topanswers';

      setTimeout(function(){ window.scrollTo(0,0); }, 100);
      $('#chattext').blur(function(){ window.scrollTo(0,0); });
      $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); setTimeout(function(){ window.scrollTo(0,0); },100); })).trigger('resize');

      <?if($clearlocal){?>
        localStorage.removeItem('<?=$clearlocal?>');
        localStorage.removeItem('<?=$clearlocal?>.title');
        localStorage.removeItem('<?=$clearlocal?>.type');
      <?}?>

      function setFinalSpacer(){
        var scroller = $('#messages').parent(), frst = Math.round((Date.now() - (new Date($('#messages>.message').first().data('at'))))/1000) || 300, finalspacer = $('#messages .spacer:first-child');
        if(frst>600) finalspacer.css('min-height','1em').css('line-height',(Math.round(100*Math.log10(1+frst)/4)/100).toString()+'em').addClass('bigspacer').text(moment.duration(frst,'seconds').humanize()+' later');
        if(scroller.hasClass('follow')) scroller.scrollTop(1000000);
      }
      function setChatPollTimeout(){
        <?if($auth){?>
          var chatPollInterval, chatLastChange = Math.round((Date.now() - (new Date($('#messages>.message').first().data('at'))))/1000) || 300;
          if(chatLastChange<10) chatPollInterval = 1000;
          else if(chatLastChange<30) chatPollInterval = 3000;
          else if(chatLastChange<120) chatPollInterval = 5000;
          else if(chatLastChange<600) chatPollInterval = 10000;
          else if(chatLastChange<3600) chatPollInterval = 30000;
          else chatPollInterval = 60000;
          <?if($dev){?>console.log('set poll interval to '+chatPollInterval);<?}?>
          clearTimeout(chatTimer);
          setFinalSpacer();
          chatTimer = setTimeout(checkChat,chatPollInterval);
        <?}?>
      }
      function renderQuestion(){
        $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
        $(this).find('.answers>.bar:first-child+.bar+.bar+.bar').each(function(){
          var t = $(this), h = t.nextAll('.bar').addBack();
          if(h.length>1){
            t.prev().addClass('premore');
            $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
              t.prev().removeClass('premore');
              $(this).prevAll('.bar:hidden').slideDown().end().slideUp();
              return false;
            });
            h.hide();
          }
        });
        $(this).find('.when').each(function(){
          var t = $(this);
          $(this).text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
          $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
        });
      }
      function processNewQuestions(scroll){
        var newquestions = $('#qa .question:not(.processed)');
        <?if($dev){?>console.log('processing '+newquestions.length+' questions');<?}?>
        if($('#qa').scrollTop()<100) scroll = true;
        newquestions.each(renderQuestion);
        newquestions.each(function(){
          if($(this).data('poll-major-id')>maxQuestionPollMajorID) maxQuestionPollMajorID = $(this).data('poll-major-id');
          if($(this).data('poll-minor-id')>maxQuestionPollMinorID) maxQuestionPollMinorID = $(this).data('poll-minor-id');
        });
        if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
        newquestions.addClass('processed');
        setChatPollTimeout();
      }
      function paginateQuestions(){
        var u = new URLSearchParams(window.location.search)
          , n = u.has('page')?+u.get('page'):1
          , s = u.has('search')?+u.get('search'):''
          , m = $('#questions').children('.question').data('of')
          , i = Cookies.get('pagesize')||10
          , p = Math.ceil(m/i)
          , d = (n<7)?[8,8,8,8,7,6][n-1]:((n>(p-6))?[8,8,8,8,7,5][p-n]:5)
          , o = { items: m
                , itemsOnPage: i
                , currentPage: n
                , prevText: '«'
                , nextText: '»'
                , ellipsePageSet: false
                , displayedPages: d
                , onPageClick: function(n){
                    var u = new URLSearchParams(window.location.search);
                    u.set('page',n);
                    if(n===1) u.delete('page');
                    window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
                    loadQuestions();
                    return false;
                } };
        if(m>i){
          $('.pages').html('<div></div><?if($auth){?><select><option value="10">10/page</option><option value="25">25/page</option><option value="100">100/page</option></select><?}?>')
                     .children('div').pagination(o);
          $('.pages select').val(i).change(function(){
            var u = new URLSearchParams(window.location.search);
            u.delete('page');
            window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
            Cookies.set('pagesize',$(this).val(),{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
            loadQuestions();
            return false;
          });
        }
        $('#qa>div.banner').show();
      }
      function loadQuestions(){
        $('#questions').children('.question').remove();
        $('.pages').empty();
        $.get('/questions?community=<?=$community_name?>'+window.location.search.replace('?','&'),function(data) {
          var newquestions = $(data).filter('.question').prependTo($('#questions'));
          processNewQuestions();
          paginateQuestions();
          $('#qa').scrollTop(0);
          $('#search+div').hide();
          if($('#search').val()) $('#search').focus();
        },'html');
      }
      function updateQuestions(){
        var maxQuestion = $('#questions>:first-child').data('poll-major-id');
        //if($('#qa').scrollTop()<100) scroll = true;
        $.get('/questions?community=<?=$community_name?>'+window.location.search.replace('?','&'),function(data) {
          if($('#questions>:first-child').data('poll-major-id')===maxQuestion){
            var newquestions = $(data).filter('.question').filter(function(){ return $(this).data('poll-major-id')>maxQuestion; });
            newquestions.each(function(){ $('#'+$(this).attr('id')).removeAttr('id').slideUp({ complete: function(){ $(this).remove(); } }); });
            newquestions.prependTo($('#questions')).hide().slideDown();
            $('#questions .question').slice(11).slideUp({ complete: function(){ $(this).remove(); } });
            processNewQuestions();
            paginateQuestions();
            //if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
          }
        },'html').fail(setChatPollTimeout);
      }
      function searchQuestions(){
        var u = new URLSearchParams(window.location.search);
        u.set('search',$('#search').val());
        if($('#search').val()==='') u.delete('search');
        u.delete('page');
        window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
        $('#search+div').show();
        loadQuestions()
      }
      function processStarboard(scroll){
        var t = $(this), promises = [] , scroller = $('#starboard').parent()
        $('#starboard .markdown').renderMarkdown(promises);
        $('#starboard .when').each(function(){
          $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
        });
        Promise.allSettled(promises).then(() => {
          $('#starboard>.message').addClass('processed').find('.question:not(.processed)').each(renderQuestion).addClass('processed');
          if(scroll===true) setTimeout(function(){ scroller.scrollTop(1000000); },0);
        });
      }
      function updateStarboard(){
        $.get('/starboard?room=<?=$room_id?>',function(r){
          $('#starboard').replaceWith(r);
          processStarboard();
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function renderChat(){
        var t = $(this), promises = [];
        t.find('.markdown').renderMarkdown(promises);
        Promise.allSettled(promises).then( () => t.find('.question:not(.processed)').each(renderQuestion).addClass('processed') );
        return promises;
      }
      function processNewChat(scroll){
        var newchat = $('#messages>*:not(.processed)')
          , scroller = $('#messages').parent()
          , promises = [];
        newchat.filter('.message').each(function(){ promises.push(...renderChat.call(this)); }).find('.when').each(function(){
          $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
        });

        newchat.find('img').each(function(){ promises.push(new Promise(r => { const i = new Image(); i.onload = () => r(); i.onerror = () => r(); i.src = $(this).attr('src'); })); });
        if(typeof document.fonts === 'undefined'){ // for BB
          if(scroll===true){
            setTimeout(function(){ scroller.scrollTop(1000000); },1000);
          }else if(scroll===false){
            if(!scroller.hasClass('follow')) scroller.addClass('newscroll');
          }
          newchat.addClass('processed');
        }else{
          promises.push(document.fonts.ready);
          Promise.allSettled(promises).then(() => {
            if(scroll===true){
              setTimeout(function(){ scroller.scrollTop(1000000); },0);
            }else if(scroll===false){
              if(!scroller.hasClass('follow')) scroller.addClass('newscroll');
            }
            newchat.addClass('processed');
          });
        }

        $('.message').each(function(){
          var id = $(this).data('id'), rid = id;
          function foo(b){
            if(arguments.length!==0) $(this).addClass('t'+id);
            if(arguments.length===0 || b===true) if($(this).data('reply-id')) $('.message[data-id='+$(this).data('reply-id')+']').each(function(){ foo.call(this,true) });
            if(arguments.length===0 || b===false) $('.message[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
          }
          foo.call(this);
        });
        newchat.filter('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
        setFinalSpacer();
        newchat.filter('.message').find('.who a').filter(function(){ return !$(this).closest('div').hasClass('t'+$(this).attr('href').substring(2)); }).each(function(){
          var id = $(this).attr('href').substring(2);
          $(this).attr('href','/transcript?room=<?=$room?>&id='+id+'#c'+id);
        });
        if(!maxChatChangeID) $('#messages').children().last().next().filter('.spacer').remove();
        if(!maxChatChangeID) $('#messages>.message').last().removeClass('merged');
        $('#messages>.message').each(function(){ if($(this).data('change-id')>maxChatChangeID) maxChatChangeID = $(this).data('change-id'); });
      }
      function updateRoomLatest(){
        var read, count = 0, m;
        read = localStorage.getItem('read')?JSON.parse(localStorage.getItem('read')):{};
        $('#active-rooms a:not([data-unread]):not(.processed)').each(function(){
          delete read[$(this).attr('data-room')];
          $(this).addClass('processed');
        });
        $('#active-rooms a[data-unread]:not(.processed)').each(function(){
          var r = $(this).attr('data-room'), l = $(this).data('latest');
          if(r==='<?=$room?>') read['<?=$room?>'] = _.union(read['<?=$room?>']||[],$('#messages>.message').map(function(){ var id = +this.id.substring(1); return (id>l)?id:null; }).get().reverse()).sort((a,b) => a-b);
          if(read[r]){
            read[r] = $.map(read[r],function(v){ return (v>l)?v:null; });
            $(this).attr('data-unread',Math.max(0,$(this).attr('data-unread')-read[r].length));
            $(this).attr('data-unread-lang',$(this).attr('data-unread').toLocaleString('<?=$jslang?>'));
            if($(this).attr('data-unread')==='0') $(this).removeAttr('data-unread').removeAttr('data-unread-lang');
          }
          $(this).addClass('processed');
        });
        localStorage.setItem('read',JSON.stringify(read));
        _.forEach(read,function(e){ count += e.length; });
        localStorage.setItem('readCount',count);
        $('#active-rooms>div').show().children().show();
        $('#community-rooms a').each(function(){
          var t = $(this);
          $('#active-rooms a[data-room="'+t.data('id')+'"]').each(function(){
            var u = $(this);
            if(!t.hasClass('this')){
              t.attr('data-unread',u.attr('data-unread'));
              t.attr('data-unread-lang',u.attr('data-unread-lang'));
              t.attr('title',u.attr('title-lang'));
            }
            u.removeAttr('data-unread').removeAttr('data-unread-lang');
            if(u.siblings().length===0) u.parent().hide(); else u.hide();
          });
        });
        m = $('#active-rooms a[data-unread]').length;
        $('#more-rooms').removeAttr('data-unread').removeAttr('data-unread-lang');
        if(m) $('#more-rooms').attr('data-unread',m).attr('data-unread-lang',m.toLocaleString('<?=$jslang?>'));
        $('#more-rooms').toggleClass('none',$('#active-rooms a').length===0);
      }
      function updateActiveRooms(){
        $.get('/activerooms?community=<?=$community_name?>').done(function(r){
          $('#active-rooms').html(r);
          updateRoomLatest();
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function updateChat(scroll){
        var maxChat = $('#messages>.message').first().data('id')
          , scroller = $('#messages').parent()
        if(typeof scroll==='undefined') scroll = false;
        if(scroller.hasClass('follow')) scroll = true;
        $.get('/chat?room=<?=$room?>'+(($('#messages>.message').length===0)?'':'&id='+maxChat),function(data) {
          if($('#messages>.message').first().data('id')===maxChat){
            var newchat;
            $('#messages>.spacer:first-child').remove();
            newchat = $(data).prependTo($('#messages'));
            if(maxChatChangeID) numNewChats += newchat.filter('.message:not(.mine)').length;
            if(maxChatChangeID && (document.visibilityState==='hidden') && numNewChats !== 0){ document.title = '('+numNewChats+') '+title; }
            newchat.filter('.message[data-reply-id]').each(function(){ $('#c'+$(this).attr('data-reply-id')).removeAttr('data-notification-id').removeClass('notify'); });
            processNewChat(scroll);
            <?if($auth){?>
              $.get('/chat?room='+<?=$room?>+'&activeusers').done(function(r){
                var savepings = $('#active-users .ping').map(function(){ return $(this).data('id'); }).get();
                $('#active-users').html(r);
                $.each(savepings,function(){ $('#active-users .icon[data-id='+this+']').addClass('ping'); });
              });
              $.get('/activerooms?community=<?=$community_name?>').done(function(r){
                $('#active-rooms').html(r);
                updateRoomLatest();
              });
              updateActiveRooms();
            <?}?>
          }
          setChatPollTimeout();
        },'html').fail(setChatPollTimeout);
      }
      function updateChatChangeIDs(){
        $.get('/chat?changes&room='+<?=$room?>+'&fromid='+maxChatChangeID,function(r){
          _(JSON.parse(r)).forEach(function(e){ $('#c'+e[0]).each(function(){ if(e[1]>$(this).data('change-id')) $(this).addClass('changed'); }); });
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function updateQuestionPollIDs(){
        $.get('/questions?changes&community=<?=$community_name?>&fromid='+maxQuestionPollMinorID,function(r){
          _(JSON.parse(r)).forEach(function(e){ $('#q'+e[0]).each(function(){ if(e[1]>$(this).data('poll-minor-id')) $(this).addClass('changed'); }); });
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function actionChatChange(id){
        $('#c'+id).css('opacity',0.5);
        $.get('/chat?one&room=<?=$room?>&id='+id,function(r){
          var merged = $('#c'+id).hasClass('merged');
          $('#c'+id).replaceWith(r);
          if(merged) $('#c'+id).addClass('merged');
          processNewChat(false);
          $('#c'+id).css('opacity',1);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function actionQuestionChange(id){
        $('#q'+id).css('opacity',0.5);
        $.get('/questions?one&community=<?=$community_name?>&id='+id,function(r){
          $('#q'+id).replaceWith(r);
          processNewQuestions()
          $('#q'+id).css('opacity',1);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function processNotifications(){
        var t = $(this), promises = [];
        $('#notifications .markdown').renderMarkdown(promises);
        $('#notifications .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
        Promise.allSettled(promises).then(() => {
          $('#notifications .markdown').find('.question:not(.processed)').each(renderQuestion).addClass('processed');
          $('#notifications>.notification').addClass('processed');
          $('#chat-bar .panel[data-panel="notifications"]').attr('data-unread',$('#notifications>.notification:not(.dismissed)').length).attr('data-unread-lang',$('#notifications>.notification:not(.dismissed)').length.toLocaleString('<?=$jslang?>'));
        });
      }
      function updateNotifications(){
        return Promise.resolve($.get('/notification?room=<?=$room_id?>'+(dismissed?'&dismissed='+dismissed:''),function(r){
          $('#notifications').children().remove();
          $('#notifications').append(r);
          $('#messages>.notify').removeAttr('data-notification-id').removeClass('notify');
          $('#notifications>.message').each(function(){ $('#c'+$(this).attr('data-chat-id')).attr('data-notification-id',$(this).attr('data-id')).addClass('notify'); });
          processNotifications();
          setChatPollTimeout();
        }).fail(setChatPollTimeout));
      }
      function checkChat(){
        var query = new URLSearchParams(window.location.search)
          , page = query.has('page')?+query.get('page'):1
          , srch = query.has('search')?query.get('search'):''
        $.get('/poll?room=<?=$room?>').done(function(r){
          var j = JSON.parse(r);
          if(j.c>+$('#messages>.message').first().data('id')){
            <?if($dev){?>console.log('updating chat');<?}?>
            updateChat();
          }else if(j.n>maxNotificationID){
            <?if($dev){?>console.log('updating notifications');<?}?>
            updateNotifications();
            maxNotificationID = j.n;
          <?if(!$question){?>
            }else if((j.Q>maxQuestionPollMajorID)&&(page===1)&&(srch.replace(/!|{[^}]*}|\[[^\]]+\]/g,'').trim()==='')){
              <?if($dev){?>console.log('updating questions because poll ('+j.Q+') > max ('+maxQuestionPollMajorID+')');<?}?>
              updateQuestions();
              maxQuestionPollMajorID = j.Q
          <?}?>
          }else if(j.cc>maxChatChangeID){
            <?if($dev){?>console.log('updating chat change flag statuses');<?}?>
            updateChatChangeIDs();
            maxChatChangeID = j.cc;
          }else if($('.message.changed').length){
            <?if($dev){?>console.log('updating chat '+$('.message.changed').first().data('id'));<?}?>
            actionChatChange($('.message.changed').first().data('id'));
          <?if(!$question){?>
            }else if((j.q>maxQuestionPollMinorID)&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating guestion change flag statuses');<?}?>
              updateQuestionPollIDs();
              maxQuestionPollMinorID = j.q;
            }else if($('.question.changed').length&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating question '+$('.question.changed').first().data('id'));<?}?>
              actionQuestionChange($('.question.changed').first().data('id'));
          <?}?>
          }else if(j.a>maxActiveRoomChatID){
            <?if($dev){?>console.log('updating active room list');<?}?>
            updateActiveRooms();
            maxActiveRoomChatID = j.a;
          }else if(+localStorage.getItem('readCount')>99){
            $.post({ url: '//post.topanswers.xyz/chat', data: { action: 'read', room: <?=$room?>, read: $.map(JSON.parse(localStorage.getItem('read')), function(v){ return _.last(v); }) }, xhrFields: { withCredentials: true } }).done(function(){
              setChatPollTimeout();
              localStorage.removeItem('read');
              localStorage.removeItem('readCount');
            });
          }else{
            setChatPollTimeout();
          }
        }).fail(setChatPollTimeout);
      }
      function textareaInsertTextAtCursor(e,t) {
        var v = e.val(), s = e.prop('selectionStart')+t.length;
        e.val(v.substring(0,e.prop('selectionStart'))+t+v.substring(e.prop('selectionEnd'),v.length));
        e.prop('selectionStart',s).prop('selectionEnd',s);
        e.trigger('input');
      }

      if(localStorage.getItem('chat')) $('.pane').toggleClass('hidepane');
      $('#join').click(function(){
        $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
          location.reload(true);
        }).fail(function(r){
          alert((r.status)===429?'Rate limit hit, please try again later':responseText);
          location.reload(true);
        });
      });
      $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'link', link: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#chat-wrapper').on('click','.fa-reply', function(){
        var m = $(this).closest('.message'), url = location.href;
        $('#status').attr('data-replyid',m.data('chat-id')).attr('data-replyname',m.data('name')).data('update')();
        $('#chat-bar a.panel[href][data-panel="messages-wrapper"]').click();
        window.location.hash = "#c"+m.data('chat-id');
        history.replaceState(null,null,url);
        $('#chattext').focus();
        return false;
      });
      $('#chat-wrapper').on('click','.fa-ellipsis-h', function(){
        if($(this).closest('.button-group').is(':last-child')) $(this).closest('.button-group').removeClass('show').parent().children('.button-group:nth-child(2)').addClass('show');
        else $(this).closest('.button-group').removeClass('show').next().addClass('show');
        return false;
      });
      $('#chat-wrapper').on('click','.fa-edit', function(){
        var m = $(this).closest('.message');
        $('.ping').removeClass('ping locked');
        $.each(m.data('pings'),function(k,v){ $('.icon.pingable[data-id="'+v+'"]').addClass('ping locked'); });
        $('#status').attr('data-editid',m.data('chat-id')).attr('data-replyid',m.attr('data-reply-id')).attr('data-replyname',m.attr('data-reply-name')).data('update')();
        $('#chattext').val(m.find('.markdown').attr('data-markdown')).focus().trigger('input');
        return false;
      });
      function starflag(t,action,direction){
        var id = t.closest('.message').data('id'), m = $('#c'+id+',#n'+id+',#s'+id).find('.button-group:not(:first-child) .fa-'+action+((direction===-1)?'':'-o'));
        m.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/chat', data: { action: ((direction===-1)?'un':'')+action, room: <?=$room?>, id: id }, xhrFields: { withCredentials: true } }).done(function(r){
          t.closest('.buttons').find('.fa.fa-'+action+((direction===-1)?'':'-o')).toggleClass('me fa-'+action+' fa-'+action+'-o');
          m.css({ 'opacity':'1','pointer-events':'auto' }).closest('.buttons').find('.button-group .'+action+'s[data-count]').each(function(){ $(this).attr('data-count',+$(this).attr('data-count')+direction); });
        });
      };
      $('#chat-wrapper').on('click','.fa-star-o', function(){ starflag($(this),'star',1); return false; });
      $('#chat-wrapper').on('click','.fa-star', function(){ starflag($(this),'star',-1); return false; });
      $('#chat-wrapper').on('click','.fa-flag-o', function(){ starflag($(this),'flag',1); return false; });
      $('#chat-wrapper').on('click','.fa-flag', function(){ starflag($(this),'flag',-1); return false; });
      $('#chat-wrapper').on('click','.notify', function(){
        var t = $(this);
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss', id: t.attr('data-notification-id') }, xhrFields: { withCredentials: true } }).done(function(){
          t.removeAttr('data-notification-id').removeClass('notify');
          updateNotifications();
        });
        return false;
      });
      function subscribe(state){
        var b = $('#question .fa-bell, #question .fa-bell-o');
        b.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/question', data: { action: (state?'':'un')+'subscribe', id: <?=$question?> }, xhrFields: { withCredentials: true } }).done(function(r){
          b.css({ 'opacity':'1','pointer-events':'auto' });
          $('#question').toggleClass('subscribed');
        });
      }
      $('.fa-bell').click(function(){ subscribe(false); });
      $('.fa-bell-o').click(function(){ subscribe(true); });
      function flag(direction){
        var t = $(this), b = t.parent().find('.fa-flag, .fa-flag-o, .fa-flag-checkered'), p = t.closest('.post');
        b.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/'+(p.is('#question')?'question':'answer'), data: { action: 'flag', id: p.data('id'), direction: direction }, xhrFields: { withCredentials: true } }).done(function(r){
          b.css({ 'opacity':'1','pointer-events':'auto' });
          p.removeClass('flagged counterflagged');
          if(direction===1) p.addClass('flagged');
          if(direction===-1) p.addClass('counterflagged');
        });
      }
      $('.post .fa-flag').click(function(){ flag.call(this,0); });
      $('.post .fa-flag-o').click(function(){ flag.call(this,1); });
      $('.post .fa-flag-checkered').click(function(){ flag.call(this,$('#question').is('.counterflagged')?0:-1); });
      $('body').on('click','.icon.pingable:not(.locked)', function(){
        var t = $(this);
        if(t.hasClass('ping')){
          $('.icon.pingable[data-id="'+t.data('id')+'"]').removeClass('ping');
        }else{
          $('.icon.pingable[data-id="'+t.data('id')+'"]').addClass('ping');
          textareaInsertTextAtCursor($('#chattext'),'@'+t.data('name')+' ');
        }
        $('#chattext').focus();
        $('#status').data('update')();
      });
      $('#status').data('update',function(){
        var strings = [];
        if($('#status').attr('data-editid')) strings.push('editing');
        if($('#status').attr('data-replyid')) strings.push('replying to: '+$('#status').attr('data-replyname'));
        console.debug(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }));
        console.debug(_.map(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }),function(e){ return e[1]; }));
        if($('.ping').length) strings.push('pinging: '+_.map(_.uniqBy($('.ping').map(function(){ return { 'id': $(this).data('id'), 'name': $(this).data('fullname') }; }).get(),function(e){ return e.id; }),function(e){ return e.name; }).join(', '));
        if(strings.length){
          $('#canchat-wrapper').addClass('pinging');
          $('#status').children('span').text(strings.join(', '));
          $('#cancel').show();
        }else{
          $('#canchat-wrapper').removeClass('pinging');
          $('#status').children('span').text('<?=$l_preview?>:');
          $('#cancel').hide();
        }
      });
      $('#cancel').click(function(){
        var url = location.href;
        $('.ping').removeClass('ping locked');
        $('#status').attr('data-editid','').attr('data-replyid','').attr('data-replyname','').data('update')();
        window.location.hash='';
        history.replaceState(null,null,url);
      });
      $('#chatshowpreview').on('mousedown',function(){ return false; }).click(function(){
        $('#canchat-wrapper').addClass('previewing');
        $('#preview .CodeMirror').each(function(){ $(this).get(0).CodeMirror.refresh(); });
        Cookies.set('hidepreview','false',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
        if($('#messages').parent().hasClass('follow')) $('#messages').parent().scrollTop(1000000);
        return false;
      });
      $('#chathidepreview').on('mousedown',function(){ return false; }).click(function(){
        $('#canchat-wrapper').removeClass('previewing');
        Cookies.set('hidepreview','true',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
        return false;
      });
      $('#community').change(function(){
        window.location = '/'+$(this).find(':selected').attr('data-name');
      });
      $('#tags').select2({ placeholder: "select a tag" });
      function tagdrop(){ $('#tags').select2('open'); };
      $('#tags').on('select2:close', function (e) { setTimeout(function(){ $('.newtag').one('click',tagdrop); },200); });
      $('#tags').change(function(){ $.post({ url: '//post.topanswers.xyz/question', data: { id: $(this).data('question-id'), tagid: $(this).val(), action: 'new-tag' }, xhrFields: { withCredentials: true } }).done(function(){ window.location.reload(); }); });
      $('.newtag').one('click',tagdrop);
      $('.tag i').click(function(){ $.post({ url: '//post.topanswers.xyz/question', data: { id: $(this).parent().data('question-id'), tagid: $(this).parent().data('tag-id'), action: 'remove-tag' }, xhrFields: { withCredentials: true } }).done(function(){ window.location.reload(); }); });
      $('#room').change(function(){
        window.location = '/<?=$community_name?>?room='+$(this).val();
      });
      function renderPreview(sync){
        var m = $('#chattext').val(), s
          , scroller = $('#messages').parent()
          , scroll = (scroller.scrollTop()+scroller.innerHeight()+40) > scroller.prop("scrollHeight")
          , promises = []
          , onebox = false;
        sync = typeof sync !== 'undefined' ? sync : false;
        $('#canchat-wrapper').toggleClass('chatting',m?true:false);
        $('#preview .markdown').html('&nbsp;');
        if(!onebox){
          s = m.match(/^https:\/\/topanswers.xyz\/transcript\?room=([1-9][0-9]*)&id=(-?[1-9][0-9]*)?[^#]*(#c(-?[1-9][0-9]*))?$/);
          if(s&&(s[2]===s[4])){
            $.get({ url: '/chat?quote&room=<?=$room?>&id='+s[2], async: !sync }).done(function(r){
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',r.replace(/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/m,function(match){ return ' *— '+(moment(match).fromNow())+'*'; })).renderMarkdown(promises);
              }
            }).fail(function(){
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown(promises);
              }
            });
            return;
          }
        }
        if(!onebox){
          s = m.match(/^https:\/\/(?:www.youtube.com\/watch\?v=|youtu.be\/)([-_0-9a-zA-Z]*)$/);
          if(s){
            $.post({ url: '//post.topanswers.xyz/onebox/youtube', data: { id: s[1] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
              }
            });
            onebox = true;
          }
        }
        if(!onebox){
          s = m.match(/^https:\/\/xkcd.com\/([0-9]*)\/?$/);
          if(s){
            $.post({ url: '//post.topanswers.xyz/onebox/xkcd', data: { id: s[1] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
              }
            });
            onebox = true;
          }
        }
        if(!onebox){
          s = m.match(/^https:\/\/[a-z]+.wikipedia.org\/wiki\/.*$/);
          if(s){
            $.post({ url: '//post.topanswers.xyz/onebox/wikipedia', data: { url: s[0] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
              }
            });
            onebox = true;
          }
        }
        if(!onebox){
          s = m.match(/^(?:https?:\/\/)?(?:www\.)?topanswers.xyz\/[^\?\/]+\?q=([1-9][0-9]*)$/);
          if(s){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown','@@@ question '+s[1]).renderMarkdown(promises);
            onebox = true;
          }
        }
        if(!onebox){
          s = m.match(/^(?:https?:\/\/)?(?:www\.)?topanswers.xyz\/[^\?\/]+\?q=[1-9][0-9]*#a([1-9][0-9]*)$/);
          if(s){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown','@@@ answer '+s[1]).renderMarkdown(promises);
            onebox = true;
          }
        }
        if(!onebox){
          s = m.match(/^(``?)(?: ([^\n]+)| ?([-a-z0-9]+)? *\n([\s\S]+))$/);
          if(s){
            const live = s[1].length===1, codelang = s[3]?s[3].split('-')[0]:'<?=$community_code_language?>'||'none', tiolang = s[3]||'<?=$community_tio_language?>', code = s[2]||s[4];
            if(tiolang){
              let f = ':::', c = '§§§', o = '```';
              while((new RegExp('^'+f,'m')).test(code)) f+=':';
              while((new RegExp('^'+c,'m')).test(code)) c+='§';
              while((new RegExp('^'+o,'m')).test(code)) o+='`';
              if(live){
                tioRequest(code,tiolang).then(function(r){
                  while((new RegExp('^'+f,'m')).test(r.output)) f+=':';
                  while((new RegExp('^'+c,'m')).test(r.output)) c+='§';
                  while((new RegExp('^'+o,'m')).test(r.output)) o+='`';
                  if($('#chattext').val()===m){
                    $('#preview .markdown').css('visibility','visible').attr('data-markdown',f+' tio '+r.req+'\n'+c+' '+codelang+' '+tiolang+'\n'+code+'\n'+c+'\n'+o+' none\n'+r.output+'\n'+o+'\n'+f).renderMarkdown(promises);
                  }
                });
              }else{
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',o+' '+codelang+'\n'+code+'\n'+o).renderMarkdown(promises);
              }
              onebox = true;
            }
          }
        }
        if(!onebox) $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('#preview .question:not(.processed)').each(renderQuestion).addClass('processed');
          if(scroll) scroller.scrollTop(1000000);
        });
        if(scroll) scroller.scrollTop(1000000);
      }
      var renderPreviewThrottle;
      renderPreviewThrottle = _.throttle(renderPreview,100);
      $('#chattext').each(function(){ $(this).css('height',this.scrollHeight).data('initialheight',this.scrollHeight); }).on('input', function(){
        if(this.scrollHeight>$(this).outerHeight()) $(this).css('height',this.scrollHeight);
        renderPreviewThrottle();
      }).trigger('input');
      $('#chattext').keydown(function(e){
        var t = $(this), msg = t.val(),  replyid = $('#status').attr('data-replyid'), c = $('#c'+replyid), edit = $('#status').attr('data-editid')!=='', editid = $('#status').attr('data-editid'), post, arr = [];
        if(e.which===13) {
          if(!e.shiftKey) {
            if(msg.trim()){
              clearTimeout(chatTimer);
              renderPreview(true);
              $('.ping').each(function(){ arr.push($(this).data('id')); });
              if(edit){
                post = { msg: $('#preview>.markdown').attr('data-markdown'), room: <?=$room?>, editid: editid, replyid: replyid, pings: arr, action: 'edit' };
                $('#c'+editid).css('opacity',0.5);
              }else{
                post = { room: <?=$room?>
                       , msg: $('#preview>.markdown').attr('data-markdown')
                       , replyid: replyid
                       , pings: arr
                       , action: 'new'
                       , read: $.map(JSON.parse(localStorage.getItem('read')), function(v){ return _.last(v); }) };
              }
              $.post({ url: '//post.topanswers.xyz/chat', data: post, xhrFields: { withCredentials: true } }).done(function(){
                localStorage.removeItem('read');
                localStorage.removeItem('readCount');
                if(edit){
                  $('#c'+editid).css('opacity',1).find('.markdown').attr('data-markdown',msg).attr('data-reply-id',replyid).end().each(renderChat);
                  checkChat();
                }else{
                  if(replyid) $('#notifications .message[data-id='+replyid+']').remove();
                  updateChat(true);
                }
                $('#cancel').click();
                t.val('').prop('disabled',false).css('height',t.data('initialheight')).focus().trigger('input');
                $('#listen').html('<?=$l_mute?>').attr('id','mute');
              }).fail(function(r){
                alert(r.status+' '+r.statusText+'\n'+r.responseText);
                t.prop('disabled',false).focus();
              });
              $('.ping').removeClass('ping locked');
              $(this).prop('disabled',true);
            }
            return false;
          }else{
            textareaInsertTextAtCursor($(this),'  ');
          }
        }else if(e.which===38){
          if(msg===''){
            $('#messages .message.mine').first().find('.fa-edit').click()
            return false;
          }
        }else if(e.which===27){
          $('#cancel').click();
          t.val('').css('height',$(this).data('initialheight')).css('min-height',0).focus().trigger('input');
          return false;
        }
      });
      $('#chattext').pastableTextarea().on('pasteImage', function(e,v){
        var d = new FormData();
        d.append('image',v.blob);
        $('#chattext').prop('disabled',true);
        $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
          $('#chattext').prop('disabled',false).focus();
          textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')');
          $('#chatuploadfile').closest('form').trigger('reset');
        }).fail(function(r){
          alert(r.status+' '+r.statusText+'\n'+r.responseText);
          $('#chattext').prop('disabled',false).focus();
        });
        return false;
      });
      document.addEventListener('visibilitychange', function(){ numNewChats = 0; if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
      $('#dummyresizerx').remove();
      const qaAndChat = new Resizer('body', { width: 6
                                            , colour: 'rgb(var(--rgb-black))'
                                            , full_length: true
                              <?if($auth){?>, callback: function(w) { $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'resizer', position: Math.round(w) }, xhrFields: { withCredentials: true } }); }<?}?> });
      $('#chatupload').click(function(){ $('#chatuploadfile').click(); });
      $('#chatuploadfile').change(function() {
        if(this.files[0].size > 2097152){
          alert("File is too big — maximum 2MB");
          $(this).val('');
        }else{
          $(this).closest('form').submit();
        };
      });
      $('#chatuploadfile').closest('form').submit(function(){
        var d = new FormData($(this)[0]);
        $('#chattext').prop('disabled',true);
        $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
          $('#chattext').prop('disabled',false).focus();
          textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')');
          $('#chatuploadfile').closest('form').trigger('reset');
        }).fail(function(r){
          alert(r.status+' '+r.statusText+'\n'+r.responseText);
          $('#chattext').prop('disabled',false).focus();
        });
        return false;
      });
      $('#notifications .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
      <?if($auth){?>
        $('#question .starrr, #qa .answer .starrr').each(function(){
          var t = $(this), v = t.data('votes'), vv = t.prev().data('total');
          t.starrr({
            rating: v,
            max: (t.data('type')==='question')?<?=$kind_allows_question_multivotes?$community_my_power:1?>:<?=$kind_allows_answer_multivotes?$community_my_power:1?>,
            change: function(e,n){
              n = n||0;
              if(n!==v){
                t.css({'opacity':'0.3','pointer-events':'none'});
                $.post({ url: '//post.topanswers.xyz/'+t.data('type'), data: { action: 'vote', id: t.data('id'), votes: n }, xhrFields: { withCredentials: true } }).done(function(r){
                  var req;
                  vv = vv-v+n;
                  v = n;
                  t.css({'opacity':'1','pointer-events':'auto'}).prev().attr('data-total',vv);
                  if(t.data('type')==='question'){
                    req = <?=$kind_minimum_votes_to_answer?>-vv;
                    t.prev().attr('data-required',req);
                    $('#provide').prop('disabled',req>0)
                  }
                }).fail(function(r){ alert((r.status)===429?'Rate limit hit, please try again later':r.responseText); });
              }
            }
          });
          t.find('a').removeAttr('href');
        });
      <?}?>
      processNewQuestions(true);
      paginateQuestions();
      (function(){
        var promises = [];
        $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('#qa .post:not(.processed) .question').each(renderQuestion);
          $('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
          $('#qa .post:not(.processed) .when').each(function(){
            var t = $(this);
            t.text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
            t.attr('title',moment(t.data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
          });
          $('#qa .post').addClass('processed');
        });
      })();
      setTimeout(function(){ $('.firefoxwrapper').css('scroll-behavior','smooth'); },2000);
      processNewChat(true);
      updateActiveRooms();
      processNotifications();
      setChatPollTimeout();
      (function(){
        var promises = [];
        $('#info').find('.markdown[data-markdown]').renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('#info').css('color','rgb(var(--rgb-dark)');
        });
      })();
      $('#se').click(function(){
        var t = $(this), f = t.closest('form');
        vex.dialog.open({
          input: ['<p>Enter question or answer id or url (and optionally further answer ids/urls from the same question) from ',
                    '<select name="site"><?foreach(db("select sesite_id,sesite_url,source_is_default from sesite") as $r){extract($r,EXTR_PREFIX_ALL,'s');?><option value="<?=$s_sesite_id?>"<?=$s_source_is_default?" selected":""?>><?=$s_sesite_url?></option><?}?></select>.',
                  '</p>',
                  '<p>Separate each id/url with a space. No need to list your own answers; they will be imported automatically.</p>',
                  '<input name="ids">'].join('')
         ,callback: function(v){
            if(v){
              t.hide().after('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
              f.find('[name=sesiteid]').attr('value',v.site);
              f.find('[name=seids]').attr('value',v.ids);
              f.submit();
            }
          }
        });
        return false;
      });
      <?if($question){?>
        setTimeout(function(){ $('.answer:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
      <?}?>
      $(window).on('hashchange',function(){ if($(':target').length) $(':target')[0].scrollIntoView(); });
      $('#chat-wrapper').on('click','#mute', function(){
        var t = $(this);
        $.post({ url: '//post.topanswers.xyz/room', data: { action: 'mute', id: <?=$room?> }, xhrFields: { withCredentials: true } }).done(function(){
          t.html('<?=$l_listen?>').attr('id','listen');
          $('#listen').show();
          updateActiveRooms();
        });
        t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','#listen', function(){
        var t = $(this);
        $.post({ url: '//post.topanswers.xyz/room', data: { action: 'listen', id: <?=$room?> }, xhrFields: { withCredentials: true } }).done(function(){
          t.html('<?=$l_mute?>').attr('id','mute');
          updateActiveRooms();
        });
        t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','#pin', function(){
        var t = $(this);
        $.post({ url: '//post.topanswers.xyz/room', data: { action: 'pin', id: <?=$room?> }, xhrFields: { withCredentials: true } }).done(function(){
          t.html('<?=$l_unpin?>').attr('id','unpin');
          $('#unpin').show();
          updateActiveRooms();
        });
        t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','#unpin', function(){
        var t = $(this);
        $.post({ url: '//post.topanswers.xyz/room', data: { action: 'unpin', id: <?=$room?> }, xhrFields: { withCredentials: true } }).done(function(){
          t.html('<?=$l_pin?>').attr('id','pin');
          updateActiveRooms();
        });
        t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification .fa.fa-times-circle', function(){
        var n = $(this).closest('.notification').attr('data-id');
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss', id: n }, xhrFields: { withCredentials: true } }).done(function(){
          $('#messages>.message.notify[data-notification-id='+n+']').removeAttr('data-notification-id').removeClass('notify');
          updateNotifications().then(() => {
            if(!$('#notifications').children('div').length) $('#chat-bar a.panel[href][data-panel="messages-wrapper"]').click();
            <?if($dev){?>console.log($('#notifications').children().length);<?}?>
          });
        });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','#more-notifications', function(){
        dismissed = $(this).data('dismissed');
        $(this).html('<a class="fa fa-fw fa-spinner fa-pulse"></i>');
        updateNotifications();
        return false;
      });
      $('#search').on('input',()=>{ $('#questions>.question').remove(); $('.pages').empty(); });
      $('#search').on('input',_.debounce(searchQuestions,1000));
      $('#search').keydown(function(e){
        if(e.which===27){
          $(this).val('').trigger('input');
          return false;
        }
      });
      $('#chat-bar a.panel').click(function(){
        var panels = $('#chat-panels>div'), panel = $('#'+$(this).data('panel'));
        if(!panel.hasClass('panel')) panel = panel.parent();
        $('#chat-bar a.panel:not([href])').attr('href','.');
        $(this).removeAttr('href');
        panels.css('visibility','hidden');
        panel.css('visibility','visible');
        return false;
      });
      processStarboard(true);
      $('#more-rooms').click(function(){
        $('#active-rooms').slideToggle(200);
        return false;
      });
      $('.firefoxwrapper').on('scroll',_.debounce(function(){
        var t = $(this), s = (t.scrollTop()-t[0].scrollHeight+t[0].offsetHeight) > -5;
        t.toggleClass('follow',s);
        if(s) t.removeClass('newscroll');
      }));

    });
  </script>
  <title><?=isset($_GET['room']) ? ($room_name.' - ') : (isset($_GET['q'])?$question_title.' - ':'')?><?=$community_display_name?> - <?=$l_topanswers?></title>
</head>
<body>
  <main class="pane">
    <header>
      <?$ch = curl_init('http://127.0.0.1/navigationx?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
      <?if(!$question){?>
        <div class="container shrink"><input class="element" type="search" id="search" value="<?=$_GET['search']??''?>" placeholder="🔍&#xFE0E; <?=$l_search_placeholder?>" autocomplete="off"><div class="element fa fa-fw fa-spinner fa-pulse"></div></div>
      <?}?>
      <div>
        <?if(!$auth){?><span class="element"><input id="link" type="button" value="log in"><input id="join" type="button" value="join (sets cookie)"></span><?}?>
        <?if($auth){?>
          <?if($dev){?><input id="poll" class="element" type="button" value="poll"><?}?>
          <?if($community_about_question_id){?><a href="/<?=$community_name?>?q=<?=$community_about_question_id?>" class="button wideonly">About</a><?}?>
          <?if($auth&&$communicant_can_import){?>
            <form method="post" action="//post.topanswers.xyz/import">
              <input type="hidden" name="action" value="new">
              <input type="hidden" name="community" value="<?=$community_name?>">
              <input type="hidden" name="sesiteid" value="">
              <input type="hidden" name="seids" value="">
              <a id="se" href="." class="button">Import</a>
            </form>
          <?}?>
          <a href="/question?community=<?=$community_name?>" class="button"><?=$community_ask_button_text?></a>
          <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
        <?}?>
        <div class="panecontrol fa fa-angle-double-right" onclick="localStorage.setItem('chat','chat'); $('.pane').toggleClass('hidepane'); $('#chattext').trigger('input').blur();"></div>
      </div>
    </header>
    <div id="qa">
      <?if($question){?>
        <div id="question" data-id="<?=$question?>" class="post<?=$question_i_subscribed?' subscribed':''?><?
                                                             ?><?=$question_i_flagged?' flagged':''?><?
                                                             ?><?=$question_i_counterflagged?' counterflagged':''?><?
                                                             ?><?=$question_is_deleted?' deleted':''?>">
          <div class="title">
            <a title="<?=$question_title?>"><?=$question_title?></a>
          </div>
          <div class="bar">
            <div class="element container shrink">
              <?if($kind_short_description){?><span class="kind element"><?=$kind_short_description?></span><?}?>
              <?foreach(db("select tag_id,tag_name from tag where tag_is") as $r){ extract($r);?>
                <span class="tag element" data-question-id="<?=$question?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
              <?}?>
              <?if($auth){?>
                <span class="newtag element">
                  <div>
                    <select id="tags" data-question-id="<?=$question?>">
                      <option></option>
                      <?foreach(db("select tag_id,tag_name from tag where not tag_is order by tag_question_count desc,tag_name") as $r){ extract($r);?>
                        <option value="<?=$tag_id?>"><?=$tag_name?></option>
                      <?}?>
                    </select>
                  </div>
                  <span class="tag element">&#65291;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </span>
              <?}?>
            </div>
            <div>
              <span class="when element" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"></span>
              <span class="element">
                <?if($question_account_is_imported){?>
                  <span><?if($question_selink_user_id>0){?><a href="<?=$sesite_url.'/users/'.$question_selink_user_id?>"><?=$question_account_name?></a> <?}?>imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id?>">from SE</a></span>
                <?}else{?>
                  <span><?=$question_account_name?></span>
                <?}?>
              </span>
              <img title="Stars: <?=$question_communicant_votes?>" class="icon<?=($auth&&!$question_account_is_me)?' pingable':''?>" data-id="<?=$question_account_id?>" data-name="<?=explode(' ',$question_account_name)[0]?>" data-fullname="<?=$question_account_name?>" src="/identicon?id=<?=$question_account_id?>">
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
                <?if(!$question_account_is_me){?><a class="element" href="." onclick="$('#question .icon').click(); return false;">comment</a><?}?>
                <a class="element" href="." onclick="$(this).hide().next('.element').show(); return false;"><?=$l_license?></a>
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
                        <?foreach(db("select question_flag_account_id,question_flag_account_name,question_flag_is_crew,question_flag_direction
                                      from question_flag
                                      where question_flag_account_id<>$1
                                      order by question_flag_is_crew, question_flag_at",$account_id) as $i=>$r){ extract($r);?>
                          <img class="icon pingable"
                               title="<?=$question_flag_account_name?><?=$question_flag_is_crew?(($question_flag_direction===1)?' (crew)':' (crew, counter-flagged)'):''?>"
                               data-id="<?=$question_flag_account_id?>"
                               data-name="<?=explode(' ',$question_flag_account_name)[0]?>"
                               data-fullname="<?=$question_flag_account_name?>"
                               src="/identicon?id=<?=$question_flag_account_id?>">
                        <?}?>
                      </div>
                    </div>
                  <?}?>
                  <div class="element fa fw fa-flag" title="unflag this question"></div>
                  <div class="element fa fw fa-flag-o" title="flag this question (n.b. flags are public)"></div>
                  <?if($communicant_is_post_flag_crew&&($question_active_flags>0)){?>
                    <div class="element fa fw fa-flag-checkered" title="counterflag"></div>
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
              <?foreach(db("select answer_id,answer_change,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_account_name,answer_is_deleted,answer_communicant_votes,answer_summary
                                 , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                                 , to_char(answer_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_change_at_iso
                                 , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                                 , extract('epoch' from current_timestamp-answer_change_at)::bigint answer_change_when
                            from answer
                            order by answer_votes desc, answer_communicant_votes desc, answer_id desc") as $i=>$r){ extract($r);?>
                <div class="bar<?=$answer_is_deleted?' deleted':''?>">
                  <a href="/<?=$community_name?>?q=<?=$question?>#a<?=$answer_id?>" class="element summary shrink"><?=($i===0)?'Top ':''?>Answer<?=($i>0)?' #'.($i+1):''?>: <span data-markdown="<?=$answer_summary?>"><?=$answer_summary?></span></a>
                  <div>
                    <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
                    <?if($answer_votes){?>
                      <span class="element">
                        <i class="fa fa-star<?=(($answer_account_id!==$account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>" data-count="<?=$answer_votes?>"></i>
                      </span>
                    <?}?>
                    <span class="element"><?=$answer_account_name?></span>
                    <img title="Stars: <?=$answer_communicant_votes?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="/identicon?id=<?=$answer_account_id?>">
                  </div>
                </div>
              <?}?>
            </div>
          <?}?>
        </div>
        <?if($kind_has_answers){?>
          <div class="banner">
            <h3><?=$question_answer_count?> Answer<?=($question_answer_count!==1)?'s':''?></h3>
            <div style="flex: 1 1 0;"></div>
            <a <?=($auth&&( $question_votes>=$kind_minimum_votes_to_answer ))?'href="/answer?question='.$question.'"':'title="requires '.($kind_minimum_votes_to_answer-$question_votes).' more stars"'?> class="button"><?=$question_answered_by_me?$l_provide_another_answer:$l_provide_answer?></a>
          </div>
        <?}?>
        <?foreach(db("select answer_id,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_has_history
                            ,answer_license_href,answer_license_name,answer_codelicense_name,answer_license_description,answer_codelicense_description,answer_account_name,answer_account_is_imported
                            ,answer_communicant_votes,answer_selink_user_id,answer_se_answer_id,answer_i_flagged,answer_i_counterflagged,answer_crew_flags,answer_active_flags
                           , answer_account_id=$1 answer_account_is_me
                           , answer_crew_flags>0 answer_is_deleted
                           , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                           , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                           , answer_codelicense_id<>1 and answer_codelicense_name<>answer_license_name answer_has_codelicense
                           , answer_active_flags>(answer_i_flagged::integer) answer_other_flags
                      from answer
                      order by answer_votes desc, answer_communicant_votes desc, answer_id desc",$account_id) as $i=>$r){ extract($r);?>
          <div id="a<?=$answer_id?>" data-id="<?=$answer_id?>" class="post answer<?=$answer_i_flagged?' flagged':''?><?
                                                                               ?><?=$answer_i_counterflagged?' counterflagged':''?><?
                                                                               ?><?=$answer_is_deleted?' deleted':''?>">
            <div class="bar">
              <div><span class="element"><?=($i===0)?'Top Answer':('Answer #'.($i+1))?></span></div>
              <div>
                <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
                <span class="element">
                  <?if($answer_account_is_imported){?>
                    <span><?if($answer_selink_user_id){?><a href="<?=$sesite_url.'/users/'.$answer_selink_user_id?>"><?=$answer_account_name?></a> <?}?>imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id.'/'.$answer_se_answer_id.'/#'.$answer_se_answer_id?>">from SE</a></span>
                  <?}else{?>
                    <span><?=$answer_account_name?></span>
                  <?}?>
                </span>
                <img title="Stars: <?=$answer_communicant_votes?>" class="icon<?=($auth&&!$answer_account_is_me)?' pingable':''?>" data-id="<?=$answer_account_id?>" data-name="<?=explode(' ',$answer_account_name)[0]?>" data-fullname="<?=$answer_account_name?>" src="/identicon?id=<?=$answer_account_id?>">
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
                  <?if(!$answer_account_is_me){?><a class="element" href="." onclick="$(this).closest('.answer').find('.icon').click(); return false;">comment</a><?}?>
                  <a class="element" href="." onclick="$(this).hide().next('.element').show(); return false;"><?=$l_license?></a>
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
                          <?foreach(db("select answer_flag_is_crew,answer_flag_direction,answer_flag_account_id,answer_flag_account_name
                                        from answer_flag
                                        where answer_id=$1 and answer_flag_account_id<>$2
                                        order by answer_flag_is_crew, answer_flag_at",$answer_id,$account_id) as $i=>$r){ extract($r);?>
                            <img class="icon pingable"
                                 title="<?=$answer_flag_account_name?><?=$answer_flag_is_crew?(($answer_flag_direction===1)?' (crew)':' (crew, counter-flagged)'):''?>"
                                 data-id="<?=$answer_flag_account_id?>"
                                 data-name="<?=explode(' ',$answer_flag_account_name)[0]?>"
                                 data-fullname="<?=$answer_flag_account_name?>"
                                 src="/identicon?id=<?=$answer_flag_account_id?>">
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
  <div id="dummyresizerx"></div>
  <div id="chat-wrapper" class="pane hidepane">
    <footer>
      <div id="community-rooms">
        <div>
          <div class="panecontrol fa fa-angle-double-left hidepane" onclick="localStorage.removeItem('chat'); $('.pane').toggleClass('hidepane');"></div>
          <a class="frame this"<?=$dev?' href="/room?id='.$room.'" title="room settings"':''?> title="<?=$room_name?>" data-id="<?=$room?>"><img class="icon roomicon" src="/roomicon?id=<?=$room?>"></a>
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
      <div class="element"><a class="panel" data-panel="messages-wrapper"><?=$question?$l_comments:$l_chat?></a><?if($auth){?> / <a class="panel" data-panel="starboard" href="."><?=$l_starred?></a> / <a class="panel" data-panel="notifications" href="."><?=$l_notifications?></a><?}?></div>
      <div class="element">
        <?if($auth){?>
          <?if($room_can_listen){?><a id="listen" href="."><?=$l_listen?></a><?}?>
          <?if($room_can_mute){?><a id="mute" href="."><?=$l_mute?></a><?}?>
          <?if($room_is_pinned){?><a id="unpin" href="."><?=$l_unpin?></a><?}else{?><a id="pin" href="."><?=$l_pin?></a><?}?>
        <?}?>
        <a href="/transcript?room=<?=$room?>"><?=$l_transcript?></a>
      </div>
    </div>
    <div id="chat-panels">
      <div id="messages-wrapper" class="panel">
        <div id="chat" class="panel">
          <div class="firefoxwrapper follow">
            <div id="messages">
              <?if($room_has_chat){?>
                <?$ch = curl_init('http://127.0.0.1/chat?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
                <div style="flex: 1 0 10px;"></div>
              <?}elseif($question){?>
                <div style="flex: 1 0 10px;">
                  <div style="padding: 10vh 20%;">
                    <?if($auth){?>
                      <?if($question_se_question_id){?>
                        <p>This is a dedicated room for discussion about this imported question.</p>
                        <p>You can direct a comment to any answer poster via the 'comment' link under their post.</p>
                      <?}else{?>
                        <p>This is a dedicated room for discussion about this question.</p>
                        <p>You can direct a comment to the question poster (or any answer poster) via the 'comment' link under their post.</p>
                      <?}?>
                    <?}else{?>
                      <p>This is a dedicated room for discussion about this question.</p>
                      <p>Once logged in you can direct comments to the question poster (or any answer poster) here.</p>
                    <?}?>
                  </div>
                </div>
              <?}?>
            </div>
          </div>
          <?if($auth){?>
            <div id="active-users">
              <?$ch = curl_init('http://127.0.0.1/chat?activeusers&room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
            </div>
          <?}?>
        </div>
        <?if($canchat){?>
          <div id="canchat-wrapper"<?=$hidepreview?'':' class="previewing"'?>>
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
              </div>
            </div>
          </div>
        <?}?>
      </div>
      <?if($auth){?>
        <div class="firefoxwrapper" class="panel" style="visibility: hidden">
          <div id="starboard">
            <?$ch = curl_init('http://127.0.0.1/starboard?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
            <div style="flex: 1 0 0;"></div>
          </div>
        </div>
        <div id="notifications" class="panel">
          <?$ch = curl_init('http://127.0.0.1/notification?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
        </div>
      <?}?>
    </div>
  </div>
</body>
</html>
<?ob_end_flush();
