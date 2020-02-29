<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to community,pg_temp");
if(isset($_COOKIE['uuid'])){ ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied'); }
if(ccdb("select exists(select 1 from private where community_name=$1)",$_GET['community'])) { header('Location: //topanswers.xyz/private?community='.$_GET['community']); exit; }
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
if($auth) setcookie("uuid",$_COOKIE['uuid'],2147483647,'/','topanswers.xyz',null,true);
extract(cdb("select login_resizer_percent,login_chat_resizer_percent
                   ,account_id,account_is_dev,account_notification_id
                   ,community_id,community_name,community_display_name,community_my_power,community_code_language,community_about_question_id,community_ask_button_text,community_banner_markdown
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_tables_are_monospace
                   ,communicant_is_post_flag_crew,communicant_can_import
                   ,room_id,room_name,room_can_chat,room_has_chat
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,sesite_url
                   ,question_id,question_title,question_markdown,question_votes,question_license_name,question_se_question_id,question_crew_flags,question_active_flags
                   ,question_has_history,question_is_deleted,question_votes_from_me,question_answered_by_me,question_is_answered,question_answer_count,question_i_subscribed,question_i_flagged,question_i_counterflagged
                   ,question_when
                   ,question_account_id,question_account_is_me,question_account_name,question_account_is_imported
                   ,question_communicant_se_user_id,question_communicant_votes
                   ,question_license_href,question_has_codelicense,question_codelicense_name,question_license_description,question_codelicense_description
                  , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                   ,kind_short_description,kind_can_all_edit,kind_has_answers,kind_has_question_votes,kind_has_answer_votes,kind_minimum_votes_to_answer,kind_allows_question_multivotes,kind_allows_answer_multivotes
                   ,kind_show_answer_summary_toc
             from one"));
$dev = $account_is_dev;
$_GET['community']===$community_name || fail(400,'invalid community');
$question = $_GET['q']??'0';
$room = $room_id;
$canchat = $room_can_chat;
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
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
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/select2.css">
  <link rel="stylesheet" href="/lib/starrr.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
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
    body { display: flex; background: rgb(var(--rgb-dark)); }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { flex-direction: column; flex: 1 1 <?=$login_resizer_percent?>%; overflow: hidden; }

    textarea, pre, code, .CodeMirror { font-family: var(--monospace-font-family); }
    textarea, pre, :not(pre)>code, .CodeMirror { font-size: 90%; }
    [data-rz-handle='horizontal'] { margin: 7px 0; transition: opacity 0.1s linear 0s; }
    [data-rz-handle='horizontal']:not(:hover):not(:active) { opacity: 0; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; }
    .icon:not(.roomicon) { border-radius: 2px; }
    .icon.pingable:not(.ping):hover { box-shadow: 0 0 0 1px rgb(var(--rgb-dark)); cursor: pointer; transition: box-shadow linear 0.1s; }
    .icon.ping { box-shadow: 0 0 0 1px rgb(var(--rgb-highlight)); }
    <?if($dev){?>.changed { outline: 2px solid orange; }<?}?>
    .spacer { flex: 0 0 auto; min-height: 13px; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: rgba(var(--rgb-dark),0.6); pointer-events: none; }

    .select2-dropdown { border: 1px solid rgb(var(--rgb-dark)) !important; box-shadow: 0 0 0.2rem 0.3rem white; }
    a[data-lightbox] img { cursor: zoom-in; }

    #qa { overflow: auto; scroll-behavior: smooth; }
    #qa #info { color: rgb(var(--rgb-mid)); padding: 6px; background: rgb(var(--rgb-mid)); font-size: 12px; }
    #qa .banner { display: flex; margin: 16px; align-items: center; }
    #qa .banner h1, #qa .banner h3 { color: rgb(var(--rgb-light)); font-weight: normal; margin: 0; }
    @supports (-webkit-touch-callout: none) { #qa * { -webkit-transform: translate3d(0, 0, 0); } }
    #chat-wrapper header { border-top: 2px solid black; }
    #more { margin-bottom: 2rem; display: none; display: flex; justify-content: center; }

    <?if($question){?>
      <?if($auth){?>.tag:hover i { visibility: visible; }<?}?>
      .newtag { position: relative; cursor: pointer; margin-left: 0; }
      .newtag .tag { opacity: 0.4; margin: 0; }
      .newtag:hover .tag { opacity: 1; }
      .newtag>div { position: absolute; top: -2px; right: -2px; z-index: 1; visibility: hidden; }

      #qa #question { margin: 16px; }
      #qa .post:target { box-shadow: 0 0 1px 2px rgb(var(--rgb-highlight)); }
      #qa .markdown { border: 1px solid rgb(var(--rgb-dark)); border-width: 1px 0; padding: 8px; }
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

    #chat-wrapper { font-size: 14px; flex: 1 1 <?=100-$login_resizer_percent?>%; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; overflow: hidden; }
    #chat-wrapper .label { font-size: 12px; padding: 2px 0 1px 0; border-bottom: 1px solid rgb(var(--rgb-dark)); }
    #chat-wrapper .roomtitle { flex: 0 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    #chat { display: flex; flex: 1 0 0; min-height: 0; }
    #chat-panels { display: flex; flex: 1 1 auto; flex-direction: column; overflow: hidden; margin: 16px 0; }

    #notification-wrapper { display: flex; flex-direction: column; flex: 1 1 <?=$login_chat_resizer_percent?>%; overflow: hidden; margin: 0 16px; border-radius: 3px; background: rgb(var(--rgb-light)); }
    #notification-wrapper:empty, #notification-wrapper:empty + [data-rz-handle] { display: none; }
    #notification-wrapper .label { border-bottom: 1px solid rgb(var(--rgb-dark)); flex: 0 0 auto; }
    #notifications { overflow-x: hidden; overflow-y: auto; }
    #messages-wrapper { flex: 1 1 <?=100-$login_chat_resizer_percent?>%; display: flex; flex-direction: column; overflow: hidden; border-radius: 3px; background: rgb(var(--rgb-light)); margin: 0 16px; }
    #messages { flex: 1 1 0; display: flex; flex-direction: column-reverse; overflow-x: hidden; overflow-y: auto; scroll-behavior: smooth; background: rgb(var(--rgb-mid)); padding: 4px; }
    .newscroll { border-bottom: 3px solid rgb(var(--rgb-highlight)); }
    #firefoxwrapper { overflow-y: auto; overflow-x: hidden; height: 100%; }
    #firefoxwrapper #messages { min-height: 100%; }
    #messages .message .who { top: -1.3em; }
    #messages .message:not(:hover) .when { opacity: 0; }
    #starboard { background: rgb(var(--rgb-mid)); overflow-x: hidden; overflow-y: auto; flex: 1 1 auto; display: none; flex-direction: column-reverse; scroll-behavior: smooth; }
    #starboard .message { padding: 4px; padding-top: 1.3em; }
    #starboard .message:not(:first-child) { border-bottom: 1px solid rgba(var(--rgb-dark),0.6); }
    #starboard .message .who { top: 0.2rem; font-size: 12px; }
    #starboard .message .button-group:not(:first-child) .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    #starboard .message .button-group:first-child { display: none; }
    #starboard .message:not(:hover) .button-group:not(:first-child) { display: grid; }
    #starboard .message:not(:hover) .button-group:not(:first-child) .fa-link { display: none; }

    #preview { display: block; width: 100%; background: rgb(var(--rgb-light)); border-top: 1px solid rgb(var(--rgb-dark)); padding: 4px; }
    #preview .markdown:empty { visibility: hidden; }
    #canchat-wrapper { flex: 0 0 auto; }
    #chattext-wrapper { position: relative; display: flex; border-top: 1px solid rgb(var(--rgb-dark)); align-items: center; }
    #chatuploadfile { display: none; }
    #chatupload { position: absolute; right: 4px; font-size: 18px; color: rgb(var(--rgb-dark)); cursor: pointer; }
    #chatupload:active { color: rgb(var(--rgb-mid)); }
    #chattext { flex: 0 0 auto; font-family: inherit; font-size: 14px; width: 100%; height: 26px; resize: none; outline: none; border: none; padding: 4px; padding-right: 30px; margin: 0; }

    #chatorstarred { pointer-events: none; }
    #chatorstarred a[href] { pointer-events: auto; }

    .message { position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message:not(.processed) { opacity: 0; }
    .message .who { white-space: nowrap; font-size: 10px; position: absolute; }
    .message .who>a { color: rgb(var(--rgb-dark)); }
    .message .who>a[href^='#'] { text-decoration: none; }
    .message .when { color: rgb(var(--rgb-dark)); transition: opacity linear 0.1s; }
    .message .markdown { flex: 0 1 auto; max-height: 30vh; padding: 4px; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; background: white; overflow: auto; transition: background linear 0.1s; }
    .message .markdown img { max-height: 7rem; }
    .message .button-group { display: grid; grid-template: 11px 11px / 12px 12px; align-items: center; justify-items: start; font-size: 11px; margin-top: 1px; margin-left: 1px; }
    .message .button-group:first-child { grid-template: 11px 11px / 22px 2px; }
    .message .button-group .fa { color: rgb(var(--rgb-dark)); cursor: pointer; text-decoration: none; }
    .message .button-group .fa.me { color: rgb(var(--rgb-highlight)); }
    .message:hover .button-group:first-child { display: none; }
    .message .button-group:not(.show) { display: none; }
    .message:not(:hover) .button-group:not(:first-child) { display: none; }
    .message .button-group:first-child .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    .message .button-group:first-child .fa[data-count][data-count="0"] { visibility: hidden; }
    .message.merged>.markdown { margin-top: -1px; }
    .message.merged>.who, .message.merged>.icon { visibility: hidden; }
    .message:target .markdown { box-shadow: 0 0 2px 2px rgb(var(--rgb-highlight)) inset; }
    .message.thread .markdown { background: rgba(var(--rgb-highlight),0.25); }

    .notification { padding: 4px; border-radius: 3px; margin: 2px; border: 1px solid rgba(var(--rgb-dark),0.6); }
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

    #active { flex: 0 0 23px; display: flex; flex-direction: column; justify-content: space-between; background: rgb(var(--rgb-light)); border-left: 1px solid rgb(var(--rgb-dark)); overflow-y: hidden; }
    #active-rooms { flex: 1 1 auto; display: flex; flex-direction: column; overflow-y: hidden; }
    #active-rooms a { position: relative; }
    #active-rooms a.processed[href][data-unread]:after { content:attr(data-unread); position: absolute; bottom: 1px; right: 1px; font-family: sans-serif; font-size: 9px; background: rgb(var(--rgb-highlight)); color: black;
                                                         width: 12px; height: 12px; text-align: center; line-height: 13px; border-radius: 30%; pointer-events: none; box-shadow: 0 0 2px 2px #fffd; text-shadow: 0 0 1px white; }
    #active-rooms>a:not([href])>.icon { outline: 1px solid rgb(var(--rgb-highlight)); }
    #active-rooms>a[href]:hover>.icon { outline: 1px solid rgba(var(--rgb-highlight),0.6); }
    #active-spacer { flex: 0 0 auto; padding: 1rem 0; cursor: pointer; }
    #active-spacer>div { background: rgb(var(--rgb-dark)); height: 1px; }
    #active-users { flex: 1 1 auto; display: flex; flex-direction: column-reverse; overflow-y: hidden; }

    .simple-pagination { list-style: none; display: block; overflow: hidden; padding: 0 5px 5px 0; margin: 0; list-style: none; padding: 0; margin: 0; }
    .simple-pagination ul { display: flex; padding: 0; }
    .simple-pagination li { position:relative; flex: 0 0 auto; list-style: none; outline-left: 1px solid rgb(var(--rgb-dark)); }
    .simple-pagination li>span { user-select: none; }
    .simple-pagination li>* { display: block; height: 38px; width: 38px; line-height: 38px; text-decoration: none; color: black; text-align: center; background-color: rgb(var(--rgb-light)); outline: 1px solid rgb(var(--rgb-dark)); }
    .simple-pagination li:not(.disabled):not(.active):hover>* { background-color: rgb(var(--rgb-mid)); }
    .simple-pagination li>.current:not(.prev):not(.next) { position: relative; z-index: 1; outline: 2px solid rgb(var(--rgb-highlight)); }
    .simple-pagination li>.ellipse { padding: 0 10px; user-select: none; }
    .simple-pagination li>.prev { border-radius: 3px 0 0 3px; }
    .simple-pagination li>.next { border-radius: 0 3px 3px 0; }

    #search { flex: 0 1 570px; min-width: 0; }
    #search+div { display: none; }

    #dummyresizerx { background-color: black; flex: 0 0 2px; }
    #dummyresizery { flex: 0 0 2px; }

    .pane { display: flex; }
    .panecontrol { display: none; width: 28px; font-size: 24px; text-align: center; }
    @media (max-width: 576px){
      .hidepane { display: none; }
      .panecontrol { display: unset; }
      textarea,select,input { font-size: 16px; }
      #chattext-wrapper:not(:hover) button { display: unset; }
      #poll { display: none; }
      #se { display: none; }
      #chat-panels { margin: 0; }
      #notification-wrapper { margin: 0; }
      #messages-wrapper { margin: 0; }
      .simple-pagination li>* { height: 22px; width: 22px; line-height: 22px; font-size: 12px; }
    }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/jquery.waitforimages.js"></script>
  <script src="/lib/codemirror/codemirror.js"></script>
  <script src="/lib/codemirror/sql.js"></script>
  <script src="/lib/vex/vex.combined.min.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
  <script src="/lib/resizer.js"></script>
  <script src="/lib/favico.js"></script>
  <script src="/lib/select2.js"></script>
  <script src="/lib/starrr.js"></script>
  <script src="/lib/jquery.simplePagination.js"></script>
  <script>
    //moment.locale(window.navigator.userLanguage || window.navigator.language);
    $(function(){
      var title = document.title, latestChatId;
      var favicon = new Favico({ animation: 'fade', position: 'up' });
      var chatTimer, maxChatChangeID = 0, maxActiveRoomChatID = 0, maxNotificationID = <?=$auth?$account_notification_id:'0'?>, numNewChats = 0;
      var maxQuestionPollMajorID = 0, maxQuestionPollMinorID = 0, questionPage = 1;
      var firefox = false;

      vex.defaultOptions.className = 'vex-theme-topanswers';

      $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');

      <?if($clearlocal){?>
        localStorage.removeItem('<?=$clearlocal?>');
        localStorage.removeItem('<?=$clearlocal?>.title');
        localStorage.removeItem('<?=$clearlocal?>.type');
      <?}?>

      function setFinalSpacer(){
        var scroll, frst = Math.round((Date.now() - (new Date($('#messages>.message').first().data('at'))))/1000) || 300, finalspacer = $('#messages .spacer:first-child');
        if(frst>600) finalspacer.css('min-height','1em').css('line-height',(Math.round(100*Math.log10(1+frst)/4)/100).toString()+'em').addClass('bigspacer').text(moment.duration(frst,'seconds').humanize()+' later');
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
            $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
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
      function paginateQuestions(n){
        var m = $('#questions').children('.question').data('of')
          , p = Math.ceil(m/10)
          , d = (n<7)?[8,8,8,8,7,6][n-1]:((n>(p-6))?[8,8,8,8,7,5][p-n]:5)
          , o = { items: m
                , itemsOnPage: 10
                , currentPage: n
                , prevText: '«'
                , nextText: '»'
                , ellipsePageSet: false
                , displayedPages: d
                , onPageClick: function(n){ questionPage = n; $('#questions').children('.question').remove(); updateQuestions(true); return false; } };
        $('#more').show().pagination(o);
        $('#qa>div.banner').show();
      }
      function updateQuestions(scroll){
        var maxQuestion = $('#questions>:first-child').data('poll-major-id'), full = $('#questions').children('.question').length===0;
        if($('#qa').scrollTop()<100) scroll = true;
        $.get('/questions?community=<?=$community_name?>'+(full?'&page='+questionPage:'&id='+maxQuestion),function(data) {
          if($('#questions>:first-child').data('poll-major-id')===maxQuestion){
            var newquestions;
            $(data).each(function(){ $('#'+$(this).attr('id')).removeAttr('id').slideUp({ complete: function(){ $(this).remove(); } }); });
            newquestions = $(data).filter('.question').prependTo($('#questions')).hide().slideDown(full?0:400);
            processNewQuestions();
            paginateQuestions(questionPage);
            if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
          }
        },'html').fail(setChatPollTimeout);
      }
      function searchQuestions(){
        $('#search+div').show();
        $.get('/questions?community=<?=$community_name?>&search='+$('#search').val(),function(data) {
          $('#questions>.question').remove();
          $(data).filter('.question').prependTo($('#questions'));
          processNewQuestions();
          $('#more').hide();
          $('#qa>div.banner').hide();
          $('#search+div').hide();
        },'html');
      }
      function processStarboard(){
        $('#starboard .markdown').renderMarkdown();
        $('#starboard .when').each(function(){
          $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
        });
        $('#starboard>.message').addClass('processed');
      }
      function updateStarboard(){
        $.get('/starboard?room=<?=$room_id?>',function(r){
          $('#starboard').replaceWith(r);
          processStarboard();
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function renderChat(){
        var t = $(this);
        t.find('.markdown').renderMarkdown(function(){ t.find('.question:not(.processed)').each(renderQuestion).addClass('processed'); });
      }
      function processNewChat(scroll){
        var newchat = $('#messages>*:not(.processed)')
          , scroller = $('#firefoxwrapper').length ? $('#firefoxwrapper') : $('#messages');
        newchat.filter('.message').each(renderChat).find('.when').each(function(){
          $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
        });
        if(newchat.find('img').length===0){
          newchat.addClass('processed');
        }else{
          newchat.find('img').waitForImages(true).done(function(){
            if(scroll){
              newchat.addClass('processed');
              scroller.scrollTop(1000000);
            }else{
              newchat.addClass('processed');
              scroller.addClass('newscroll').scroll(_.debounce(function(){ if((scroller.scrollTop() - scroller[0].scrollHeight + scroller[0].offsetHeight) > -5){ scroller.removeClass('newscroll'); scroller.off('scroll'); } }));
            }
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
        var read, count = 0;
        read = localStorage.getItem('read')?JSON.parse(localStorage.getItem('read')):{};
        $('#active-rooms>a:not([data-unread]):not(.processed)').each(function(){
          delete read[$(this).attr('data-room')];
          $(this).addClass('processed');
        });
        $('#active-rooms>a[data-unread]:not(.processed)').each(function(){
          var r = $(this).attr('data-room'), l = $(this).data('latest');
          if(r==='<?=$room?>') read['<?=$room?>'] = _.union(read['<?=$room?>']||[],$('#messages>.message').map(function(){ var id = +this.id.substring(1); return (id>l)?id:null; }).get());
          if(read[r]){
            read[r] = $.map(read[r],function(v){ return (v>l)?v:null; });
            $(this).attr('data-unread',Math.max(0,$(this).attr('data-unread')-read[r].length));
            if($(this).attr('data-unread')==='0') $(this).removeAttr('data-unread');
          }
          $(this).addClass('processed');
        });
        localStorage.setItem('read',JSON.stringify(read));
        _.forEach(read,function(e){ count += e.length; });
        localStorage.setItem('readCount',count);
      }
      function updateActiveRooms(){
        $.get('/chat?activerooms&room=<?=$room?>').done(function(r){
          $('#active-rooms').html(r);
          updateRoomLatest();
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function updateChat(scroll){
        var maxChat = $('#messages>.message').first().data('id')
          , scroller = $('#firefoxwrapper').length ? $('#firefoxwrapper') : $('#messages');
        if(typeof scroll==='undefined') scroll = (scroller.scrollTop() - scroller[0].scrollHeight + scroller[0].offsetHeight) > -5;
        $.get('/chat?room=<?=$room?>'+(($('#messages>.message').length===0)?'':'&id='+maxChat),function(data) {
          if($('#messages>.message').first().data('id')===maxChat){
            var newchat;
            $('#messages>.spacer:first-child').remove();
            newchat = $(data).prependTo($('#messages'));
            if(maxChatChangeID) numNewChats += newchat.filter('.message:not(.mine)').length;
            if(maxChatChangeID && (document.visibilityState==='hidden') && numNewChats !== 0){ document.title = '('+numNewChats+') '+title; }
            processNewChat(scroll);
            <?if($auth){?>
              $.get('/chat?room='+<?=$room?>+'&activeusers').done(function(r){
                var savepings = $('#active-users .ping').map(function(){ return $(this).data('id'); }).get();
                $('#active-users').html(r);
                $.each(savepings,function(){ $('#active-users .icon[data-id='+this+']').addClass('ping'); });
              });
              $.get('/chat?activerooms&room=<?=$room?>').done(function(r){
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
          processNewChat();
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
        $('#notification-wrapper .markdown').renderMarkdown(function(){ $('#notification-wrapper .markdown').find('.question:not(.processed)').each(renderQuestion).addClass('processed'); });
        $('#notification-wrapper .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
        $('#notifications>.notification').addClass('processed');
      }
      function updateNotifications(){
        $.get('/notification?room=<?=$room_id?>',function(r){
          $('#notification-wrapper').replaceWith(r);
          processNotifications();
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function checkChat(){
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
            }else if((j.Q>maxQuestionPollMajorID)&&(questionPage===1)&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating questions because poll ('+j.Q+') > max ('+maxQuestionPollMajorID+')');<?}?>
              updateQuestions();
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
        if(confirm('This will set a cookie to identify your account. You must be 16 or over to join TopAnswers.')){
          $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
            alert('This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n'+r);
            location.reload(true);
          }).fail(function(r){
            alert((r.status)===429?'Rate limit hit, please try again later':responseText);
            location.reload(true);
          });
        }
      });
      $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'link', link: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#chat-wrapper').on('click','.fa-reply', function(){
        var m = $(this).closest('.message');
        $('#status').attr('data-replyid',m.data('id')).attr('data-replyname',m.data('name')).data('update')();
        $('#chattext').focus();
      });
      $('#chat-wrapper').on('click','.fa-ellipsis-h', function(){
        if($(this).closest('.button-group').is(':last-child')) $(this).closest('.button-group').removeClass('show').parent().children('.button-group:nth-child(2)').addClass('show');
        else $(this).closest('.button-group').removeClass('show').next().addClass('show');
      });
      $('#chat-wrapper').on('click','.fa-edit', function(){
        var m = $(this).closest('.message');
        $('.ping').removeClass('ping locked');
        $.each(m.data('pings'),function(k,v){ $('.icon.pingable[data-id="'+v+'"]').addClass('ping locked'); });
        $('#status').attr('data-editid',m.data('id')).attr('data-replyid',m.attr('data-reply-id')).attr('data-replyname',m.attr('data-reply-name')).data('update')();
        $('#chattext').val(m.find('.markdown').attr('data-markdown')).focus().trigger('input');
      });
      function starflag(t,action,direction){
        var id = t.closest('.message').data('id'), m = $('#c'+id+',#n'+id).find('.button-group:not(:first-child) .fa-'+action+((direction===-1)?'':'-o'));
        m.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/chat', data: { action: ((direction===-1)?'un':'')+action, room: <?=$room?>, id: id }, xhrFields: { withCredentials: true } }).done(function(r){
          m.css({ 'opacity':'1','pointer-events':'auto' }).toggleClass('me fa-'+action+' fa-'+action+'-o')
           .closest('.buttons').find('.button-group:first-child .'+action+'s[data-count]').toggleClass('me fa-'+action+' fa-'+action+'-o')
           .each(function(){ $(this).attr('data-count',+$(this).attr('data-count')+direction); });
        });
      };
      $('#chat-wrapper').on('click','.fa-star-o', function(){ starflag($(this),'star',1); });
      $('#chat-wrapper').on('click','.fa-star', function(){ starflag($(this),'star',-1); });
      $('#chat-wrapper').on('click','.fa-flag-o', function(){ starflag($(this),'flag',1); });
      $('#chat-wrapper').on('click','.fa-flag', function(){ starflag($(this),'flag',-1); });
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
        if($('#status').attr('data-editid')) strings.push('Editing');
        if($('#status').attr('data-replyid')) strings.push('Replying to: '+$('#status').attr('data-replyname'));
        console.debug(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }));
        console.debug(_.map(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }),function(e){ return e[1]; }));
        if($('.ping').length) strings.push('Pinging: '+_.map(_.uniqBy($('.ping').map(function(){ return { 'id': $(this).data('id'), 'name': $(this).data('fullname') }; }).get(),function(e){ return e.id; }),function(e){ return e.name; }).join(', '));
        if(strings.length){
          $('#status').children('span').text(strings.join(', '));
          $('#cancel').show();
        }else{
          $('#status').children('span').text('Preview:');
          $('#cancel').hide();
        }
      });
      $('#cancel').click(function(){
        $('.ping').removeClass('ping locked');
        $('#status').attr('data-editid','').attr('data-replyid','').attr('data-replyname','').data('update')();
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
          , scroller = $('#firefoxwrapper').length ? $('#firefoxwrapper') : $('#messages')
          , scroll = (scroller.scrollTop()+scroller.innerHeight()+40) > scroller.prop("scrollHeight");
        sync = typeof sync !== 'undefined' ? sync : false;
        s = m.match(/^https:\/\/topanswers.xyz\/transcript\?room=([1-9][0-9]*)&id=(-?[1-9][0-9]*)?[^#]*(#c(-?[1-9][0-9]*))?$/);
        if(s&&(s[2]===s[4])){
          $.get({ url: '/chat?quote&room=<?=$room?>&id='+s[2], async: !sync }).done(function(r){
            if($('#chattext').val()===m){
              $('#preview .markdown').css('visibility','visible').attr('data-markdown',r.replace(/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/m,function(match){ return ' *— '+(moment(match).fromNow())+'*'; })).renderMarkdown();
              if(scroll) scroller.scrollTop(1000000);
            }
          }).fail(function(){
            if($('#chattext').val()===m){
              $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown();
            }
          });
        }else{
          $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown(function(){ $('#preview .question:not(.processed)').each(renderQuestion).addClass('processed'); });
          if(scroll) scroller.scrollTop(1000000);
        }
      }
      var renderPreviewThrottle;
      renderPreviewThrottle = _.throttle(renderPreview,100);
      $('#chattext').on('input', function(){
        if(!$(this).data('initialheight')) $(this).data('initialheight',this.scrollHeight);
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
                post = { msg: $('#preview .markdown').attr('data-markdown'), room: <?=$room?>, editid: editid, replyid: replyid, pings: arr, action: 'edit' };
                $('#c'+editid).css('opacity',0.5);
              }else{
                post = { room: <?=$room?>
                       , msg: $('#preview .markdown').attr('data-markdown')
                       , replyid: replyid
                       , pings: arr
                       , action: 'new'
                       , read: $.map(JSON.parse(localStorage.getItem('read')), function(v){ return _.last(v); }) };
              }
              $.post({ url: '//post.topanswers.xyz/chat', data: post, xhrFields: { withCredentials: true } }).done(function(){
                if(edit){
                  $('#c'+editid).css('opacity',1).find('.markdown').attr('data-markdown',msg).attr('data-reply-id',replyid).end().each(renderChat);
                  checkChat();
                }else{
                  if(replyid) $('#notifications .message[data-id='+replyid+']').remove();
                  if($('#notifications .message').children().length===0) $('#notification-wrapper').children().remove();
                  updateChat(true);
                }
                $('#cancel').click();
                t.val('').prop('disabled',false).css('height',t.data('initialheight')).focus().trigger('input');
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
      document.addEventListener('visibilitychange', function(){ numNewChats = 0; if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
      $('#dummyresizerx').remove();
      $('#dummyresizery').remove();
      const qaAndChat = new Resizer('body', { width: 2
                                            , colour: 'black'
                                            , full_length: true
                              <?if($auth){?>, callback: function(w) { $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'resizer', position: Math.round(w) }, xhrFields: { withCredentials: true } }); }<?}?> });
      const notificationsAndChat = new Resizer('#chat-panels', { width: 2
                                                               , colour: 'black'
                                                               , full_length: true
                                                 <?if($auth){?>, callback: function(y) { $.post({ url: '//post.topanswers.xyz/profile'
                                                                                                , data: { action: 'chat_resizer', position: Math.round(y) }
                                                                                                , xhrFields: { withCredentials: true } }); }<?}?> });
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
      $('#notification-wrapper .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
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
      paginateQuestions(questionPage);
      $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(function(){
        $('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
        $('#qa .post:not(.processed) .when').each(function(){
          var t = $(this);
          t.text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
          t.attr('title',moment(t.data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
        });
        $('#qa .post').addClass('processed');
      });
      if(('netscape' in window) && / rv:/.test(navigator.userAgent)){
        firefox -= true;
        $('#starboard').next('#messages').addBack().wrapAll('<div id="firefoxwrapper"></div>');
        $('#firefoxwrapper').closest('#messages-wrapper').css('border-radius','0');
        setTimeout(function(){ $('#firefoxwrapper').css('scroll-behavior','smooth'); },2000);
      }
      processNewChat(true);
      updateRoomLatest();
      processNotifications();
      setChatPollTimeout();
      $('#info').find('.markdown[data-markdown]').renderMarkdown(function(){ $('#info').css('color','rgb(var(--rgb-dark)'); });
      $('#se').click(function(){
        var t = $(this), f = t.closest('form');
        vex.dialog.open({
          input: ['<p>Enter question or answer id or url (and optionally further answer ids/urls from the same question) from ',
                    '<select name="site"><?foreach(db("select sesite_id,sesite_url,source_is_default from sesite") as $r){extract($r);?><option value="<?=$sesite_id?>"<?=$source_is_default?" selected":""?>><?=$sesite_url?></option><?}?></select>.',
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
      $('#active-spacer').click(function(){
        var t = $(this);
        if((t.prev().css('flex-shrink')==='1')&&(t.next().css('flex-shrink')==='1')) t.next().animate({ 'flex-shrink': 100 });
        else if(t.next().css('flex-shrink')==='100') t.next().animate({ 'flex-shrink': 1 }).end().prev().animate({ 'flex-shrink': 100 });
        else t.prev().animate({ 'flex-shrink': 1 });
      });
      $(window).on('hashchange',function(){ $(':target')[0].scrollIntoView(); });
      $('#chat-wrapper').on('click','.notification[data-type="chat"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/chat', data: { action: 'dismiss', room: <?=$room?>, id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification[data-type="question"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss-question', id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification[data-type="question flag"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss-question-flag', id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification[data-type="answer"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss-answer', id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification[data-type="answer flag"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss-answer-flag', id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.notification[data-type="system"] .fa.fa-times-circle', function(){
        $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss-system', id: $(this).closest('.notification').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
        return false;
      });
      function search(){
        if($('#search').val()===''){
          $('#questions>.question').remove();
          maxQuestionPollMajorID = 0;
          maxQuestionPollMinorID = 0;
          updateQuestions();
        }else{
          searchQuestions();
        }
      }
      $('#search').on('input',_.debounce(search,500));
      $('#search').keydown(function(e){
        if(e.which===27){
          $(this).val('').trigger('input');
          return false;
        }
      });
      $('#chatorstarred').click(function(){
        $(this).children('a').each(function(){ $(this).attr('href',function(i,a){ return a===undefined?'.':null; }); });
        $('#starboard,#messages').each(function(){ $(this).css('display',function(i,s){ return s==='none'?'flex':'none'; }); })
        $('#preview,#canchat-wrapper').toggle();
        processStarboard(true);
        return false;
      });
    });
  </script>
  <title><?=isset($_GET['room']) ? ($room_name.' - ') : (isset($_GET['q'])?$question_title.' - ':'')?><?=$community_display_name?> - TopAnswers</title>
</head>
<body>
  <main class="pane">
    <header>
      <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
      <?if(!$question){?>
        <div class="container shrink"><input class="element" type="search" id="search" placeholder="🔍&#xFE0E; {type} [tag] &quot;exact phrase&quot; fuzzy"><div class="element fa fa-fw fa-spinner fa-pulse"></div></div>
      <?}?>
      <div>
        <?if(!$auth){?><span class="element"><input id="join" type="button" value="join"> or <input id="link" type="button" value="log in"></span><?}?>
        <?if($auth){?>
          <?if($community_about_question_id){?><a href="/<?=$community_name?>?q=<?=$community_about_question_id?>" class="button wideonly">About</a><?}?>
          <?if($auth&&$communicant_can_import&&$sesite_url){?>
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
            <?if($kind_short_description){?><div><?=$kind_short_description?></div><?}?>
            <a title="<?=$question_title?>"><?=$question_title?></a>
          </div>
          <div class="bar">
            <div>
              <img title="Stars: <?=$question_communicant_votes?>" class="icon<?=($auth&&!$question_account_is_me)?' pingable':''?>" data-id="<?=$question_account_id?>" data-name="<?=explode(' ',$question_account_name)[0]?>" data-fullname="<?=$question_account_name?>" src="/identicon?id=<?=$question_account_id?>">
              <span class="element">
                <?if($question_account_is_imported){?>
                  <span><?if($question_communicant_se_user_id>0){?><a href="<?=$sesite_url.'/users/'.$question_communicant_se_user_id?>"><?=$question_account_name?></a> <?}?>imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id?>">from SE</a></span>
                <?}else{?>
                  <span><?=$question_account_name?></span>
                <?}?>
              </span>
              <span class="element">
                <a href="<?=$question_license_href?>" title="<?=$question_license_description?>"><?=$question_license_name?></a>
                <?if($question_has_codelicense){?>
                  <span> + </span>
                  <a href="/meta?q=24" title="<?=$question_codelicense_description?>"><?=$question_codelicense_name?> for original code</a>
                <?}?>
              </span>
              <span class="when element" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"></span>
            </div>
            <div>
              <div class="element container">
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
                <?foreach(db("select tag_id,tag_name from tag where tag_is") as $r){ extract($r);?>
                  <span class="tag element" data-question-id="<?=$question?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
                <?}?>
              </div>
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
                <?if($question_account_is_me||$kind_can_all_edit){?><a class="element" href="/question?id=<?=$question?>">edit</a><?}?>
                <?if($question_has_history){?><a class="element" href="/question-history?id=<?=$question?>">history</a><?}?>
                <?if(!$question_account_is_me){?><a class="element" href='.' onclick="$('#question .icon').click(); return false;">comment</a><?}?>
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
            <div style="height: 2px; background: rgb(var(--rgb-dark));"></div>
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
            <a <?=($auth&&( $question_votes>=$kind_minimum_votes_to_answer ))?'href="/answer?question='.$question.'"':'title="requires '.($kind_minimum_votes_to_answer-$question_votes).' more stars"'?> class="button">Provide <?=$question_answered_by_me?'another':'an'?> answer</a>
          </div>
        <?}?>
        <?foreach(db("select answer_id,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_has_history
                            ,answer_license_href,answer_license_name,answer_codelicense_name,answer_license_description,answer_codelicense_description,answer_account_name,answer_account_is_imported
                            ,answer_communicant_votes,answer_communicant_se_user_id,answer_se_answer_id,answer_i_flagged,answer_i_counterflagged,answer_crew_flags,answer_active_flags
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
                  <a href="<?=$answer_license_href?>" title="<?=$answer_license_description?>"><?=$answer_license_name?></a>
                  <?if($answer_has_codelicense){?>
                    <span> + </span>
                    <a href="/meta?q=24" title="<?=$answer_codelicense_description?>"><?=$answer_codelicense_name?> for original code</a></span>
                  <?}?>
                </span>
                <span class="element">
                  <?if($answer_account_is_imported){?>
                    <span><?if($answer_communicant_se_user_id){?><a href="<?=$sesite_url.'/users/'.$answer_communicant_se_user_id?>"><?=$answer_account_name?></a> <?}?>imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id.'//'.$answer_se_answer_id.'/#'.$answer_se_answer_id?>">from SE</a></span>
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
                  <a class="element" href="/answer?id=<?=$answer_id?>">edit</a>
                  <?if($answer_has_history){?><a class="element" href="/answer-history?id=<?=$answer_id?>">history</a><?}?>
                  <?if(!$answer_account_is_me){?><a class="element" href='.' onclick="$(this).closest('.answer').find('.icon').click(); return false;">comment</a><?}?>
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
        <div class="banner">
          <h1>Latest:</h1>
          <div style="flex: 1 1 0;"></div>
        </div>
        <div id="questions">
          <?$ch = curl_init('http://127.0.0.1/questions?community='.$community_name.'&page=1'); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
        </div>
        <div id='more'></div>
      <?}?>
    </div>
  </main>
  <div id="dummyresizerx"></div>
  <div id="chat-wrapper" class="pane hidepane">
    <header>
      <div style="display: flex; align-items: center; height: 100%;">
        <div class="panecontrol fa fa-angle-double-left hidepane" onclick="localStorage.removeItem('chat'); $('.pane').toggleClass('hidepane');"></div>
        <a class="frame"<?=$dev?' href="/room?id='.$room.'" title="room settings"':''?> title="<?=$room_name?>"><img class="icon roomicon" src="/roomicon?id=<?=$room?>"></a>
        <div></div>
      </div>
      <div>
        <?if($auth) if($dev){?><input id="poll" class="element" type="button" value="poll"><?}?>
      </div>
      <div style="display: flex; align-items: center; height: 100%;">
        <?foreach(db("select room_id,room_name
                      from room natural join community
                      where community_name=$1 and room_id<>$2
                      order by room_name desc",$community_name,$room) as $r){ extract($r,EXTR_PREFIX_ALL,'r');?>
          <a class="frame" href="/<?=$community_name?>?room=<?=$r_room_id?>" title="<?=$r_room_name?>"><img class="icon roomicon" src="/roomicon?id=<?=$r_room_id?>"></a>
        <?}?>
      </div>
    </header>
    <div id="chat">
      <div id="chat-panels">
        <?if($auth){?>
          <?$ch = curl_init('http://127.0.0.1/notification?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
          <div id="dummyresizery" data-rz-handle="horizontal"></div>
        <?}?>
        <div id="messages-wrapper">
          <div class="label container">
            <div id="chatorstarred" class="element"><a><?=$question?'Comments':'Chat'?></a><?=($auth)?' / <a href=".">Starred</a>':''?></div>
            <?if(!$question){?><div class="element roomtitle" title="<?=$room_name?>">&#8220;<?=$room_name?>&#8221;</div><?}?>
            <div class="element"><a class="element" href="/transcript?room=<?=$room?>">transcript</a></div>
          </div>
          <?if($auth){?>
            <div id="starboard">
              <?$ch = curl_init('http://127.0.0.1/starboard?room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
              <div style="flex: 1 0 0;"></div>
            </div>
          <?}?>
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
          <?if($canchat){?>
            <div id="preview" class="message processed">
              <div id="status" style="width: 100%; font-style: italic; font-size: 10px;" data-replyid="" data-replyname="" data-editid="">
                <span>Preview:</span>
                <i id="cancel" class="fa fa-fw fa-times" style="display: none; cursor: pointer;"></i>
              </div>
              <div style="display: flex;"><div class="markdown" data-markdown=""></div></div>
            </div>
            <div id="canchat-wrapper">
              <div id="chattext-wrapper">
                <form action="/upload" method="post" enctype="multipart/form-data"><input id="chatuploadfile" name="image" type="file" accept="image/*"></form>
                <i id="chatupload" class="fa fa-fw fa-picture-o" title="embed image"></i>
                <textarea id="chattext" rows="1" placeholder="type message here" maxlength="5000"></textarea>
              </div>
            </div>
          <?}?>
        </div>
      </div>
      <?if($auth){?>
        <div id="active">
          <div id="active-rooms">
            <?$ch = curl_init('http://127.0.0.1/chat?activerooms&room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
          </div>
          <div id="active-spacer"><div></div></div>
          <div id="active-users">
            <?$ch = curl_init('http://127.0.0.1/chat?activeusers&room='.$room); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
          </div>
        </div>
      <?}?>
    </div>
  </div>
</body>
</html>
<?ob_end_flush();
