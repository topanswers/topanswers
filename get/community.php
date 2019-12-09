<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
$uuid = $_COOKIE['uuid'] ?? false;
$clearlocal = $_COOKIE['clearlocal']??'';
setcookie('clearlocal','',0,'/','topanswers.xyz',true,true);
if($uuid) setcookie("uuid",$uuid,2147483647,'/','topanswers.xyz',null,true);
$environment = $_COOKIE['environment'] ?? 'prod';
$dev = false;
if($uuid){
  ccdb("select login($1)",$uuid);
  $dev = (ccdb("select account_is_dev from my_account")==='t');
}
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
$question = $_GET['q']??'0';
if($question) ccdb("select count(*) from question where question_id=$1",$question)==='1' || die('invalid question id');;
$room = $_GET['room']??($question?ccdb("select question_room_id from question where question_id=$1",$question):ccdb("select community_room_id from community where community_name=$1",$community));
$canchat = false;
if($uuid) $canchat = ccdb("select room_can_chat from room where room_id=$1",$room)==='t';
extract(cdb("select community_id,community_my_power,sesite_url,community_code_language,my_community_regular_font_name,my_community_monospace_font_name
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
                  , coalesce(my_community_can_import,false) my_community_can_import
             from community natural join my_community
                  left join sesite on sesite_id=community_sesite_id
             where community_name=$1",$community));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: smaller;">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1">
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/select2.css">
  <link rel="stylesheet" href="/lib/starrr.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; flex: 0 0 auto; font-size: 1rem; background: #<?=$colour_dark?>; white-space: nowrap; }
    header select, header input, header a:not(.icon) { margin: 3px; }
    header .icon { border: 1px solid #<?=$colour_light?>; margin: 1px; }
    header .icon>img { background: #<?=$colour_mid?>; height: 24px; border: 1px solid #<?=$colour_dark?>; display: block; padding: 1px; }
    [data-rz-handle] { flex: 0 0 2px; background: black; }
    [data-rz-handle] div { width: 2px; background: black; }

    <?if($dev){?>.changed { outline: 2px solid orange; }<?}?>
    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { background: white; margin: 1.6rem 1.6rem 2.2rem 1.6em; border-radius: 5px 5px 0 5px; font-size: larger; }
    #qa>:last-child { margin-bottom: 4rem; }
    .answer { background: white; margin: 0 1.2rem 2.4rem 1.2rem; border-radius: 5px 0 5px 5px; font-size: larger; }
    .answer .bar { border-top: 1px solid #<?=$colour_dark?>; }
    .answer:target { box-shadow: 0 0 3px 3px #<?=$colour_highlight?>; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>80; background: #<?=$colour_mid?>; }
    .tags { display: flex; margin-left: 0.25rem; margin-top: 1px; white-space: nowrap; overflow: hidden; }
    .tag { padding: 0.1em 0.2em 0.1em 0.4em; background: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; font-size: 0.8rem; border-radius: 0 1rem 1rem 0; position: relative; margin-right: 0.2rem; margin-bottom: 0.1rem; display: inline-block; }
    .tag::after { position: absolute; border-radius: 50%; background: #<?=$colour_light?>; border: 1px solid #<?=$colour_dark?>; height: 0.5rem; width: 0.5rem; content: ''; top: calc(50% - 0.25rem); right: 0.25rem; box-sizing: border-box; }
    .tag i { visibility: hidden; cursor: pointer; position: relative; z-index: 1; color: #<?=$colour_dark?>; background: #<?=$colour_mid?>; border-radius: 50%; }
    .tag i::before { border-radius: 50%; }
    <?if($uuid&&$question){?>.tag:hover i { visibility: visible; }<?}?>
    .newtag { position: relative; cursor: pointer; }
    .newtag .tag { opacity: 0.4; margin: 0; }
    .newtag:hover .tag { opacity: 1; }

    .select2-dropdown { border: 1px solid #<?=$colour_dark?> !important; box-shadow: 0 0 0.2rem 0.3rem white; }
    a[data-lightbox] img { cursor: zoom-in; }
    .starrr { margin-left: 0.2rem; }
    .starrr a.fa-star { color: #<?=$colour_highlight?>; }
    .starrr a.fa-star-o { color: #<?=$colour_dark?>; }
    #question .fa-bell, #question .fa-bell-o { font-size: 1rem; margin-right: 0.2rem; cursor: pointer; }
    #question .fa-bell { color: #<?=$colour_highlight?>; }
    #question .fa-bell-o { color: #<?=$colour_dark?>; }
    #question:not(.subscribed) .fa-bell { display: none; }
    #question.subscribed .fa-bell-o { display: none; }

    #qa .bar { border: 1px solid #<?=$colour_dark?>; border-width: 1px 0; font-size: 0.8rem; background: #<?=$colour_light?>; display: flex; align-items: center; justify-content: space-between; min-height: calc(1.5rem + 2px); overflow: hidden; }
    <?if($question){?>#qa .bar:last-child { border-bottom: none; border-radius: 0 0 5px 5px; }<?}?>
    #qa .bar:first-child { border-top: none; border-radius: 5px 0 0 0; }
    #qa .bar .title { margin-left: 0.4rem; }
    #qa .bar+.bar { border-top: none; }
    #qa .bar>* { display: flex; align-items: center; white-space: nowrap; }
    #qa .bar>*>*:not(:last-child) { margin-right: 0.4rem; }
    #qa .identicon, #active-users .identicon, #active-rooms .roomicon { height: 1.5rem; width: 1.5rem; margin: 1px; display: block; }
    #active-rooms a { position: relative; }
    #active-rooms a[href][data-unread]:after { content:attr(data-unread); position: absolute; bottom: 1px; right: 1px; font-family: sans-serif; font-size: 0.5em; background: #<?=$colour_light?>e0; color: black;
                                               width: 9px; height: 9px; text-align: center; line-height: 10px; border-radius: 30%; border: 2px solid #<?=$colour_highlight?>80; pointer-events: none; }
    #active-rooms>a:not([href])>.roomicon { outline: 1px solid #<?=$colour_highlight?>; }
    #active-rooms>a[href]:hover>.roomicon { outline: 1px solid #<?=$colour_dark?>; }
    #qa .markdown { padding: 0.6rem; }
    #qa .minibar { border: 1px solid #<?=$colour_light?>; border-width: 1px 0;font-size: 0.8rem; display: flex; align-items: center; justify-content: space-between; min-height: calc(1.5rem + 2px); }
    #qa .minibar:last-child { border-bottom: none; }
    #qa .minibar+.minibar { border-top: none; }
    #qa .bar+.minibar { border-top: none; }
    #qa .score { color: #<?=$colour_dark?>; }
    #qa .score.me { color: #<?=$colour_highlight?>; }
    #qa .minibar>* { display: flex; align-items: center; min-width: 0; }
    #qa .minibar>*>*:not(:last-child) { margin-right: 0.4rem; }
    #qa .minibar .summary { min-width: 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; margin-left: 0.5rem; }
    #qa .minibar .summary>span>p { display: inline; }
    #qa .minibar>:first-child { flex: 1 1 auto; margin-right: 1rem; text-overflow: ellipsis; }
    #qa .minibar>:last-child { flex: 0 0 auto; margin-left: 1rem; }
    #qa .minibar>a:first-child { display: block; text-decoration: none; color: black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0.2rem; }
    #qa .question>a:first-child { display: block; padding: 0.6rem; text-decoration: none; font-size: larger; color: black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}

    .identicon.pingable:not(.ping):hover { outline: 1px solid #<?=$colour_dark?>; cursor: pointer; }
    .identicon.ping { outline: 1px solid #<?=$colour_highlight?>; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.65em; position: absolute; }
    .message .identicon { flex: 0 0 1.2rem; height: 1.2rem; margin-right: 0.2rem; margin-top: 0.1rem; }
    .message .markdown { flex: 0 1 auto; max-height: 20vh; padding: 0.25rem; border: 1px solid darkgrey; border-radius: 0.3em; background: white; overflow: auto; }

    .message .button-group { display: grid; grid-template: 0.8rem 0.8rem / 0.9rem 0.9rem; align-items: center; justify-items: start; font-size: 0.8rem; margin-left: 1px; }
    .message .button-group:first-child { grid-template: 0.8rem 0.8rem / 1.7rem 0.1rem; }
    .message .button-group .fa { color: #<?=$colour_dark?>; cursor: pointer; text-decoration: none; }
    .message .button-group .fa.me { color: #<?=$colour_highlight?>; }
    .message:hover .button-group:first-child { display: none; }
    .message .button-group:not(.show) { display: none; }
    .message:not(:hover) .button-group:not(:first-child) { display: none; }
    .message .button-group:first-child .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    .message .button-group:first-child .fa[data-count][data-count="0"] { visibility: hidden; }

    .message.merged { margin-top: -1px; }
    .message.merged .who, .message.merged .identicon { visibility: hidden; }
    .message:target .markdown { box-shadow: 0 0 2px 2px #<?=$colour_highlight?> inset; }
    #chat .message .who { top: -1.2em; }
    #chat .markdown img { max-height: 7rem; }
    #chat .message.thread .markdown { background: #<?=$colour_highlight?>40; }
    #messages .message:not(:hover) .when { display: none; }
    #notifications .message+.message { margin-top: 0.2em; }
    #notifications .message { padding: 0.3em; border-radius: 0.2em; }
    #notifications .message[data-type='chat'] { padding-top: 1.3em; }
    #notifications .message[data-type='chat'] .who { top: 0.2rem; font-size: 0.9rem; }
    #chatupload:active i { color: #<?=$colour_mid?>; }

    .pane { display: flex; }
    .panecontrol { display: none; }
    #ios-spacer { display: none; }
    @media (max-width: 576px){
      .hidepane { display: none; }
      .panecontrol { display: unset; }
      textarea,select,input { font-size: 16px; }
      #chattext-wrapper:not(:hover) button { display: unset; }
      header { flex-direction: unset; white-space: unset; }
      #ios-spacer { display: unset; }
      #poll { display: none; }
      #se { display: none; }
    }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/jquery.waitforimages.js"></script>
  <script src="/lib/codemirror/codemirror.js"></script>
  <script src="/lib/codemirror/sql.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
  <script src="/lib/resizer.js"></script>
  <script src="/lib/favico.js"></script>
  <script src="/lib/select2.js"></script>
  <script src="/lib/starrr.js"></script>
  <script>
    //moment.locale(window.navigator.userLanguage || window.navigator.language);
    $(function(){
      var title = document.title, latestChatId;
      var favicon = new Favico({ animation: 'fade', position: 'up' });
      var chatTimer, maxChatChangeID = 0, maxNotificationID = <?=$uuid?ccdb("select account_notification_id from my_account"):'0'?>, numNewChats = 0;
      var maxQuestionPollMajorID = 0, maxQuestionPollMinorID = 0;

      <?if($clearlocal){?>
        localStorage.removeItem('<?=$clearlocal?>');
      <?}?>

      function setFinalSpacer(){
        var scroll, last = Math.round((Date.now() - (new Date($('#messages>.message').last().data('at'))))/1000) || 300, finalspacer = $('#messages .spacer:last-child');
        if(($('#messages').scrollTop()+$('#messages').innerHeight()+4)>$('#messages').prop("scrollHeight")) scroll = true;
        if(last>600) finalspacer.css('min-height','1em').css('line-height',(Math.round(100*Math.log10(1+last)/4)/100).toString()+'em').addClass('bigspacer').text(moment.duration(last,'seconds').humanize()+' later');
        if(scroll) setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },0);
      }
      function setChatPollTimeout(){
        var chatPollInterval, chatLastChange = Math.round((Date.now() - (new Date($('#messages>.message').last().data('at'))))/1000) || 300;
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
      }
      function renderQuestion(){
        $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
        $(this).find('.when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
      }
      function updateQuestions(scroll){
        var maxQuestion = $('#qa>:first-child').data('poll-major-id');
        if($('#qa').scrollTop()<100) scroll = true;
        $.get('/questions?community=<?=$community?>'+(($('#qa').children('.question').length===0)?'':'&id='+maxQuestion),function(data) {
          if($('#qa>:first-child').data('poll-major-id')===maxQuestion){
            var newquestions;
            $(data).each(function(){ $('#'+$(this).attr('id')).removeAttr('id').slideUp({ complete: function(){ $(this).remove(); } }); });
            newquestions = $(data).filter('.question').prependTo($('#qa')).hide().slideDown(maxQuestionPollMajorID?400:0);
            newquestions.each(renderQuestion);
            newquestions.each(function(){
              if($(this).data('poll-major-id')>maxQuestionPollMajorID) maxQuestionPollMajorID = $(this).data('poll-major-id');
              if($(this).data('poll-minor-id')>maxQuestionPollMinorID) maxQuestionPollMinorID = $(this).data('poll-minor-id');
            });
            if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
          }
          <?if($uuid){?>setChatPollTimeout();<?}?>
          if($('#qa').children('.question').length>=20) $('#more').parent().show();
        },'html').fail(setChatPollTimeout);
      }
      function moreQuestions(){
        var last = $('#qa>.question').last(); minQuestion = last.data('poll-major-id');
        $('<div style="text-align: center;"><i class="fa fa-spinner fa-pulse fa-fw"></i></div>').insertAfter($('#more'));
        $('#more').hide().next().show();
        $.get('/questions?community=<?=$community?>&older&id='+minQuestion,function(data) {
          var newquestions = $(data).filter('.question').insertAfter(last).hide().slideDown(400);
          newquestions.each(renderQuestion);
          $('#more').show().next().hide();
          if(newquestions.length<20) $('#more').parent().hide();
        },'html');
      }
      function searchQuestions(){
        $.get('/questions?community=<?=$community?>&search='+$('#search').val(),function(data) {
          $('#qa>.question').remove();
          $(data).filter('.question').prependTo($('#qa'));
          $('#qa>.question').each(renderQuestion);
          $('#more').parent().hide();
        },'html');
      }
      function renderChat(){
        $(this).find('.markdown').renderMarkdown();
      }
      function updateChat(scroll){
        var maxChat = $('#messages>.message').last().data('id');
        if(($('#messages').scrollTop()+$('#messages').innerHeight()+4)>$('#messages').prop("scrollHeight")) scroll = true;
        $.get('/chat?room=<?=$room?>'+(($('#messages').children().length===1)?'':'&id='+maxChat),function(data) {
          if($('#messages>.message').last().data('id')===maxChat){
            var newchat;
            $('#messages>.spacer:last-child').remove();
            if(!maxChatChangeID) $('#messages').css('scroll-behavior','auto');
            newchat = $(data).appendTo($('#messages')).css('opacity','0');
            newchat.filter('.message').each(renderChat).find('.when').each(function(){ $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
            if(maxChatChangeID) numNewChats += newchat.filter('.message').length;
            if(maxChatChangeID && (document.visibilityState==='hidden')){ document.title = '('+numNewChats+') '+title; }
            if(newchat.find('img').length===0){
              if(scroll) setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")).css('scroll-behavior','smooth'); },0);
              else $('#messages').css('scroll-behavior','smooth');
            }else{
              newchat.find('img').waitForImages(true).done(function(){
                newchat.css('opacity','1');
                if(scroll){
                  setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")).css('scroll-behavior','smooth'); },0);
                }else{
                  $('#messages').css('scroll-behavior','smooth').css('border-bottom','3px solid #<?=$colour_highlight?>').scroll(_.debounce(function(){ $('#messages').css('border-bottom','none'); }));
                }
              });
            }
            $('.message').each(function(){
              var id = $(this).data('id'), rid = id;
              function foo(b){
                if(arguments.length!==0) $(this).addClass('t'+id);
                if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message[data-id='+$(this).data('reply-id')+']')[0], true);
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
            if(!maxChatChangeID) $('#messages').children().first().next().filter('.spacer').remove();
            if(!maxChatChangeID) $('#messages>.message').first().removeClass('merged');
            if(!maxChatChangeID) $('#messages>.message').each(function(){ if($(this).data('change-id')>maxChatChangeID) maxChatChangeID = $(this).data('change-id'); });
            if(scroll){
              if(maxChatChangeID){
                //setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },0);
              }else{
                //setTimeout(function(){ $('#messages').css('scroll-behavior','auto'); $('#messages').scrollTop($('#messages').prop("scrollHeight")); $('#messages').css('scroll-behavior','smooth'); },0);
              }
            }
            <?if($uuid){?>
              $.get('/chat?room='+<?=$room?>+'&activeusers').done(function(r){
                var savepings = $('#active-users .ping').map(function(){ return $(this).data('id'); }).get();
                $('#active-users').html(r);
                $.each(savepings,function(){ $('#active-users .identicon[data-id='+this+']').addClass('ping'); });
              });
              $.get('/chat?activerooms&room=<?=$room?>').done(function(r){
                $('#active-rooms').html(r);
                $('#active-rooms>a[href]').click(function(){
                  $('<form action="//post.topanswers.xyz/room" method="post" style="display: none;"><input name="action" value="switch"><input name="from-id" value="<?=$room?>"><input name="id" value="'+$(this).attr('data-room')+'"></form>').appendTo($(this)).submit();
                  return false;
                });
              });
            <?}?>
          }
          <?if($uuid){?>setChatPollTimeout();<?}?>
        },'html').fail(setChatPollTimeout);
      }
      function updateChatChangeIDs(){
        $.get('/chat?changes&room='+<?=$room?>+'&fromid='+maxChatChangeID,function(r){
          _(JSON.parse(r)).forEach(function(e){ $('#c'+e[0]).each(function(){ if(e[1]>$(this).data('change-id')) $(this).addClass('changed'); }); });
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function updateQuestionPollIDs(){
        $.get('/questions?changes&community=<?=$community?>&fromid='+maxQuestionPollMinorID,function(r){
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
          $('#c'+id).each(renderChat);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function actionQuestionChange(id){
        $('#q'+id).css('opacity',0.5);
        $.get('/questions?one&community=<?=$community?>&id='+id,function(r){
          $('#q'+id).replaceWith(r);
          $('#q'+id).each(renderQuestion);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function updateNotifications(){
        $.get(window.location.href,function(r){
          var scroll = ($('#messages').scrollTop()+$('#messages').innerHeight()+4)>$('#messages').prop("scrollHeight");
          $('#notification-wrapper').replaceWith($('<div />').append(r).find('#notification-wrapper'));
          $('#notification-wrapper .markdown').renderMarkdown();
          $('#notification-wrapper .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
          <?if($uuid){?>
            $('#notification-wrapper a[data-room]').click(function(){
              $('<form action="//post.topanswers.xyz/room" method="post" style="display: none;"><input name="action" value="switch"><input name="from-id" value="<?=$room?>"><input name="id" value="'+$(this).attr('data-room')+'"></form>').appendTo($(this)).submit();
              return false;
            });
          <?}?>
          if(scroll){ $('#messages').css('scroll-behavior','auto'); $('#messages').scrollTop($('#messages').prop("scrollHeight")); $('#messages').css('scroll-behavior','smooth'); }
          if(scroll) setTimeout(function(){ $('#messages').css('scroll-behavior','auto'); $('#messages').scrollTop($('#messages').prop("scrollHeight")); $('#messages').css('scroll-behavior','smooth'); },0);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function checkChat(){
        $.get('/poll?room=<?=$room?>').done(function(r){
          var j = JSON.parse(r);
          if(j.c>+$('#messages>.message').last().data('id')){
            <?if($dev){?>console.log('updating chat');<?}?>
            updateChat();
          }else if(j.n>maxNotificationID){
            <?if($dev){?>console.log('updating notifications');<?}?>
            updateNotifications();
            maxNotificationID = j.n;
          <?if(!$question){?>
            }else if((j.Q>maxQuestionPollMajorID)&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating questions');<?}?>
              updateQuestions();
          <?}?>
          }else if(j.cc>maxChatChangeID){
            <?if($dev){?>console.log('updating chat change flag statuses');<?}?>
            updateChatChangeIDs();
            maxChatChangeID = j.cc
          }else if($('.message.changed').length){
            <?if($dev){?>console.log('updating chat '+$('.message.changed').last().data('id'));<?}?>
            actionChatChange($('.message.changed').last().data('id'));
          <?if(!$question){?>
            }else if((j.q>maxQuestionPollMinorID)&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating guestion change flag statuses');<?}?>
              updateQuestionPollIDs();
              maxQuestionPollMinorID = j.q
            }else if($('.question.changed').length&&($('#search').val()==='')){
              <?if($dev){?>console.log('updating question '+$('.question.changed').first().data('id'));<?}?>
              actionQuestionChange($('.question.changed').first().data('id'));
          <?}?>
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
        if(confirm('This will set a cookie to identify your account. You must be 16 or over to join TopAnswers.')) { $.post({ url: '//post.topanswers.xyz/uuid', async: false, xhrFields: { withCredentials: true } }).done(function(){
          window.location = '/profile?highlight-recovery';
        }).fail(function(r){
          alert((r.status)===429?'Rate limit hit, please try again later':responseText);
          location.reload(true);
        }) };
      });
      $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: '//post.topanswers.xyz/uuid', data: { pin: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#chat-wrapper').on('click','.fa-reply', function(){
        var m = $(this).closest('.message');
        $('#replying').attr('data-id',m.data('id')).attr('data-name',m.data('name')).data('update')();
        $('#chattext').focus();
        setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },500);
      });
      $('#chat-wrapper').on('click','.fa-ellipsis-h', function(){
        if($(this).closest('.button-group').is(':last-child')) $(this).closest('.button-group').removeClass('show').parent().children('.button-group:nth-child(2)').addClass('show');
        else $(this).closest('.button-group').removeClass('show').next().addClass('show');
      });
      $('#chat-wrapper').on('click','.fa-edit', function(){
        var m = $(this).closest('.message');
        $('.ping').removeClass('ping');
        $('#replying').attr('data-id',m.data('id')).attr('data-name',m.data('name')).data('update')();
        $('#chattext').val(m.find('.markdown').attr('data-markdown')).focus().trigger('input');
      });
      function starflag(t,action,direction){
        var id = t.closest('.message').data('id'), m = $('#c'+id+',#n'+id).find('.button-group:not(:first-child) .fa-'+action+((direction===-1)?'':'-o'));
        m.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/chat', data: { action: ((direction===-1)?'un':'')+action, id: id }, xhrFields: { withCredentials: true } }).done(function(r){
          m.css({ 'opacity':'1','pointer-events':'auto' }).toggleClass('me fa-'+action+' fa-'+action+'-o').closest('.buttons').find('.button-group:first-child .'+action+'s[data-count]').toggleClass('me fa-'+action+' fa-'+action+'-o')
           .each(function changecount(){ $(this).attr('data-count',(+$(this).attr('data-count'))+direction); });
        });
      };
      function subscribe(state){
        var b = $('#question .fa-bell, #question .fa-bell-o');
        b.css({'opacity':'0.3','pointer-events':'none'});
        $.post({ url: '//post.topanswers.xyz/question', data: { action: (state?'':'un')+'subscribe', id: <?=$question?> }, xhrFields: { withCredentials: true } }).done(function(r){
          b.css({ 'opacity':'1','pointer-events':'auto' }).toggle();
        });
      }
      $('#chat-wrapper').on('click','.fa-star-o', function(){ starflag($(this),'star',1); });
      $('#chat-wrapper').on('click','.fa-star', function(){ starflag($(this),'star',-1); });
      $('#chat-wrapper').on('click','.fa-flag-o', function(){ starflag($(this),'flag',1); });
      $('#chat-wrapper').on('click','.fa-flag', function(){ starflag($(this),'flag',-1); });
      $('.fa-bell').click(function(){ subscribe(false); });
      $('.fa-bell-o').click(function(){ subscribe(true); });
      $('body').on('click','.identicon.pingable', function(){
        if(!$(this).hasClass('ping')){ textareaInsertTextAtCursor($('#chattext'),'@'+$(this).data('name')+' '); }
        $(this).toggleClass('ping');
        if($('#c'+$('#replying').attr('data-id')).hasClass('mine')) $('#replying').attr('data-id','');
        $('#chattext').focus();
        $('#replying').data('update')();
      });
      $('#replying').data('update',function(){
        var state = $('#replying').attr('data-id') || $('.ping').length, strings = [];
        if($('#replying').attr('data-id')) strings.push(($('#c'+$('#replying').attr('data-id'))).hasClass('mine')?'Editing':('Replying to: '+$('#replying').attr('data-name')));
        if($('.ping').length) strings.push('Pinging: '+$('.ping').map(function(){ return $(this).data('fullname'); }).get().join(', '));
        if(strings.length){
          $('#replying').children('span').text(strings.join(', '));
          $('#cancelreply').show();
        }else{
          $('#replying').children('span').text('Preview:');
          $('#cancelreply').hide();
        }
      });
      $('#cancelreply').click(function(){
        $('.ping').removeClass('ping');
        $('#replying').attr('data-id','').attr('data-name','').data('update')();
      });
      $('.markdown').renderMarkdown();
      $('#community').change(function(){
        <?if($uuid){?>
          $('<form action="//post.topanswers.xyz/room" method="post" style="display: none;"><input name="action" value="switch"><input name="from-id" value="<?=$room?>"><input name="id" value="'+$(this).val()+'"></form>').appendTo($(this)).submit();
        <?}else{?>
          window.location = '/'+$(this).find(':selected').attr('data-name');
        <?}?>
      });
      $('#tags').select2({ placeholder: "select a tag" });
      function tagdrop(){ $('#tags').select2('open'); };
      $('#tags').on('select2:close', function (e) { setTimeout(function(){ $('.newtag').one('click',tagdrop); },200); });
      $('#tags').change(function(){ $.post({ url: '//post.topanswers.xyz/question', data: { id: $(this).data('question-id'), tagid: $(this).val(), action: 'new-tag' }, xhrFields: { withCredentials: true } }).done(function(){ window.location.reload(); }); });
      $('.newtag').one('click',tagdrop);
      $('.tag i').click(function(){ $.post({ url: '//post.topanswers.xyz/question', data: { id: $(this).parent().data('question-id'), tagid: $(this).parent().data('tag-id'), action: 'remove-tag' }, xhrFields: { withCredentials: true } }).done(function(){ window.location.reload(); }); });
      $('#room').change(function(){
        <?if($uuid){?>
          $('<form action="//post.topanswers.xyz/room" method="post" style="display: none;"><input name="action" value="switch"><input name="from-id" value="<?=$room?>"><input name="id" value="'+$(this).val()+'"></form>').appendTo($(this)).submit();
        <?}else{?>
          window.location = '/<?=$community?>?room='+$(this).val();
        <?}?>
      });
      $('#chattext').on('input', function(){
        var m = $('#chattext').val();
        if(!$(this).data('initialheight')) $(this).data('initialheight',this.scrollHeight);
        if(this.scrollHeight>$(this).outerHeight()) $(this).css('height',this.scrollHeight);
        $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown();
        setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },500);
      }).trigger('input');
      $('#chattext').keydown(function(e){
        var t = $(this), msg = t.val(),  replyid = $('#replying').attr('data-id'), c = $('#c'+replyid), edit = c.hasClass('mine'), post;
        if(e.which===13) {
          if(!e.shiftKey) {
            if(msg.trim()){
              clearTimeout(chatTimer);
              if(edit){
                post = { msg: msg, editid: replyid, action: 'edit' };
                c.css('opacity',0.5);
              }else{
                arr = [];
                $('.ping').each(function(){ arr.push($(this).data('id')); });
                post = { room: <?=$room?>, msg: msg, replyid: replyid, pings: arr, action: 'new' };
              }
              $.post({ url: '//post.topanswers.xyz/chat', data: post, xhrFields: { withCredentials: true } }).done(function(){
                if(edit){
                  c.css('opacity',1).find('.markdown').attr('data-markdown',msg).end().each(renderChat);
                  checkChat();
                }else{
                  if(replyid) $('#notifications .message[data-id='+replyid+']').remove();
                  if($('#notifications .message').children().length===0) $('#notification-wrapper').children().remove();
                  updateChat();
                }
                $('#cancelreply').click();
                t.val('').prop('disabled',false).css('height',t.data('initialheight')).focus().trigger('input');
              }).fail(function(r){
                alert(r.status+' '+r.statusText+'\n'+r.responseText);
                t.prop('disabled',false).focus();
              });
              $('.ping').removeClass('ping');
              $(this).prop('disabled',true);
            }
            return false;
          }else{
            textareaInsertTextAtCursor($(this),'  ');
          }
        }else if(e.which===38){
          if(msg===''){
            $('#messages .message.mine').last().find('.fa-edit').click()
            return false;
          }
        }else if(e.which===27){
          $('#cancelreply').click();
          t.val('').css('height',$(this).data('initialheight')).css('min-height',0).focus().trigger('input');
          return false;
        }
      });
      document.addEventListener('visibilitychange', function(){ numNewChats = 0; if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
      const myResizer = new Resizer('body', { callback: function(w) { $.post({ url: '//post.topanswers.xyz/community', data: { action: 'resizer', position: Math.round(w) }, xhrFields: { withCredentials: true } }); } });
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
      $('#qa .when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
      $('#notification-wrapper .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
      <?if($uuid){?>
        $('#question .starrr, #qa .answer .starrr').each(function(){
          var t = $(this), v = t.data('votes');
          t.starrr({
            rating: v,
            max: <?=$community_my_power?>,
            change: function(e,n){
              n = n||0;
              if(n!==v){
                t.css({'opacity':'0.3','pointer-events':'none'});
                $.post({ url: '//post.topanswers.xyz/'+t.data('type'), data: { action: 'vote', id: t.data('id'), votes: n }, xhrFields: { withCredentials: true } }).done(function(r){
                  t.css({'opacity':'1','pointer-events':'auto'}).siblings('.score').html('<span>score: '+r+'</span>');
                  v = n;
                }).fail(function(r){ alert((r.status)===429?'Rate limit hit, please try again later':r.responseText); });
              }
            }
          });
          t.find('a').removeAttr('href');
        });
      <?}?>
      $('#qa .summary span[data-markdown]').renderMarkdownSummary();
      $('#qa .summary span[data-markdown]').each(function(){ alert('called'); });
      updateChat(true);
      updateNotifications();
      <?if(!$question){?>updateQuestions(true);<?}?>
      $('#se').click(function(){
        var t = $(this), f = t.closest('form')
          , ids = prompt('Enter question id (and optionally answer ids) from <?=$sesite_url?>.\nSeparate each id with a space. No need to list your own answers; they will be imported automatically.');
        if(ids!==null) {
          qid = ids.split(' ')[0];
          aids = ids.split(' ').slice(1).join(' ');
          t.hide().after('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
          f.find('[name=seqid]').attr('value',qid);
          f.find('[name=seaids]').attr('value',aids);
          f.submit();
        }
        return false;
      });
      setTimeout(function(){ $('.answer:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
      $('#active-spacer').click(function(){
        var t = $(this);
        if((t.prev().css('flex-shrink')==='1')&&(t.next().css('flex-shrink')==='1')) t.next().animate({ 'flex-shrink': 100 });
        else if(t.next().css('flex-shrink')==='100') t.next().animate({ 'flex-shrink': 1 }).end().prev().animate({ 'flex-shrink': 100 });
        else t.prev().animate({ 'flex-shrink': 1 });
      });
      $(window).on('hashchange',function(){ $(':target')[0].scrollIntoView(); });
      $('#chat-wrapper').on('click','.message[data-type="chat"] .dismiss', function(){
        $.post({ url: '//post.topanswers.xyz/chat', data: { action: 'dismiss', id: $(this).closest('.message').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.message[data-type="question"] .dismiss', function(){
        $.post({ url: '//post.topanswers.xyz/question', data: { action: 'dismiss', id: $(this).closest('.message').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#chat-wrapper').on('click','.message[data-type="answer"] .dismiss', function(){
        $.post({ url: '//post.topanswers.xyz/answer', data: { action: 'dismiss', id: $(this).closest('.message').attr('data-id') }, xhrFields: { withCredentials: true } }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#more').click(function(){ moreQuestions(); return false; });
      function search(){
        if($('#search').val()===''){
          $('#qa>.question').remove();
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
      $('#environment').change(function(){
        var v = $(this).val();
        if(v==='prod'){
          Cookies.remove('environment',{ secure: true, domain: '.topanswers.xyz' });
        }else{
          Cookies.set('environment',v,{ secure: true, domain: '.topanswers.xyz' });
          //Cookies.set('environment',v,{ secure: true, domain: 'post.topanswers.xyz' });
        }
        window.location.reload(true);
      });
    });
  </script>
  <title><?=$question?ccdb('select question_title from question where question_id=$1',$question):ccdb("select coalesce(room_name,initcap(community_name)||' Chat') room_name from room natural join community where room_id=$1",$room)?> - TopAnswers</title>
</head>
<body style="display: flex;">
  <main class="pane" style="background: #<?=$colour_dark?>; flex-direction: column; flex: 1 1 <?=($uuid)?ccdb("select login_resizer_percent from login"):'70'?>%; overflow: hidden;">
    <header style="border-bottom: 2px solid black;">
      <div>
        <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers</a>
        <select id="community">
          <?foreach(db("select community_name,community_room_id,community_display_name from community order by community_name desc") as $r){ extract($r);?>
            <option value="<?=$community_room_id?>" data-name="<?=$community_name?>"<?=($community===$community_name)?' selected':''?>><?=$community_display_name?></option>
          <?}?>
        </select>
        <?if($dev){?>
          <select id="environment">
            <?foreach(db("select environment_name from environment") as $r){ extract($r);?>
              <option<?=($environment===$environment_name)?' selected':''?>><?=$environment_name?></option>
            <?}?>
          </select>
        <?}?>
        <input class="panecontrol" type="button" value="chat" onclick="localStorage.setItem('chat','chat'); $('.pane').toggleClass('hidepane'); $('#chattext').trigger('input').blur();">
      </div>
      <?if(!$question){?><div><input type="search" id="search" placeholder="search"></div><?}?>
      <div style="display: flex; align-items: center;">
        <?if(!$uuid){?><input id="join" type="button" value="join"> or <input id="link" type="button" value="log in"><?}?>
        <?if(($my_community_can_import==='t')&&$sesite_url&&!$question){?>
          <form method="post" action="//post.topanswers.xyz/question">
            <input type="hidden" name="action" value="new-se">
            <input type="hidden" name="community" value="<?=$community?>">
            <input type="hidden" name="seqid" value="">
            <input type="hidden" name="seaids" value="">
            <input id="se" type="submit" value="import question from SE">
          </form>
        <?}?>
        <?if($uuid){?><form method="get" action="/question"><input type="hidden" name="community" value="<?=$community?>"><input id="ask" type="submit" value="ask question"></form><?}?>
        <?if($uuid){?><a href="/profile" class="icon"><img src="/identicon?id=<?=ccdb("select account_id from login")?>"></a><?}?>
      </div>
    </header>
    <div id="qa" style="overflow: auto; scroll-behavior: smooth;">
      <?if($question){?>
        <?extract(cdb("select question_title,question_markdown,question_votes,question_have_voted,question_votes_from_me,question_answered_by_me,question_has_history,license_name,license_href,codelicense_name,account_id
                             ,account_name,account_is_me,question_se_question_id,account_is_imported,account_community_se_user_id
                            , question_i_subscribed
                            , coalesce(account_community_votes,0) account_community_votes
                            , codelicense_id<>1 and codelicense_name<>license_name has_codelicense
                            , case question_type when 'question' then '' when 'meta' then (case community_name when 'meta' then '' else 'Meta Question: ' end) when 'blog' then 'Blog Post: ' end question_type
                            , question_type<>'question' question_is_votable
                            , question_type='blog' question_is_blog
                            , extract('epoch' from current_timestamp-question_at) question_when
                       from question natural join account natural join community natural join license natural join codelicense natural left join account_community
                       where question_id=$1",$question));?>
        <div id="question" class="<?=($question_have_voted==='t')?'voted':''?> <?=($question_i_subscribed==='t')?'subscribed':''?>" style="border-radius: 0 0 5px 5px; font-size: larger; background: white;">
          <div style="font-size: larger; text-shadow: 0.1em 0.1em 0.1em lightgrey; padding: 0.6rem;"><?=$question_type.htmlspecialchars($question_title)?></div>
          <div class="bar">
            <div>
              <img title="Stars: <?=$account_community_votes?>" class="identicon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" data-fullname="<?=$account_name?>" src="/identicon?id=<?=$account_id?>">
              <span>
                <span class="when" data-seconds="<?=$question_when?>"></span>,
                <?if($account_is_imported==='t'){?>
                  <span><?if($account_community_se_user_id>0){?>by <a href="<?=$sesite_url.'/users/'.$account_community_se_user_id?>"><?=htmlspecialchars($account_name)?></a> <?}?>imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id?>">from SE</a></span>
                <?}else{?>
                  <span>by <?=htmlspecialchars($account_name)?></span>
                <?}?>
              </span>
              <span>
                <a href="<?=$license_href?>"><?=$license_name?></a>
                <?if($has_codelicense==='t'){?><span>+ <a href="/meta?q=24"><?=$codelicense_name?> for original code</a></span><?}?>
              </span>
            </div>
            <div>
              <div class="tags">
                <?if($uuid){?>
                  <span class="newtag" style="margin-right: 0.2rem; margin-bottom: 0.1rem;">
                    <div style="position: absolute; top: -2px; right: -2px; z-index: 1; visibility: hidden;">
                      <select id="tags" data-question-id="<?=$question?>">
                        <option></option>
                        <?foreach(db("select tag_id,tag_name
                                      from tag natural join community
                                      where community_name=$1 and tag_id not in (select tag_id from question_tag_x where question_id=$2)
                                      order by tag_question_count desc,tag_name",$community,$question) as $r){ extract($r);?>
                          <option value="<?=$tag_id?>"><?=$tag_name?></option>
                        <?}?>
                      </select>
                    </div>
                    <span class="tag">&#65291;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                  </span>
                <?}?>
                <?foreach(db("select tag_id,tag_name from question_tag_x_not_implied natural join tag where question_id=$1",$question) as $r){ extract($r);?>
                  <span class="tag" data-question-id="<?=$question?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
                <?}?>
              </div>
            </div>
          </div>
          <div id="markdown" class="markdown" data-markdown="<?=htmlspecialchars($question_markdown)?>"><?=htmlspecialchars($question_markdown)?></div>
          <div class="bar">
            <div>
              <?if($question_is_votable==='t'){?>
                <?if($account_is_me==='f'){?>
                  <div class="starrr" data-id="<?=$question?>" data-type="question" data-votes="<?=$question_votes_from_me?>" title="rate this question"></div>
                <?}else{?>
                  <span></span>
                <?}?>
                <span class="score">score: <?=$question_votes?></span>
              <?}?>
              <?if($uuid){?>
                <span></span>
                <?if(($account_is_me==='t')||($question_is_blog==='f')){?><a href="/question?id=<?=$question?>">edit</a><?}?>
                <?if($question_has_history==='t'){?><a href="/question-history?id=<?=$question?>">history</a><?}?>
                <?if($account_is_me==='f'){?><a href='.' onclick="$('#question .identicon').click(); return false;">comment</a><?}?>
              <?}?>
            </div>
            <div>
              <div>
                <div class="fa fw fa-bell" title="unsubscribe from this question"></div>
                <div class="fa fw fa-bell-o" title="subscribe to this question"></div>
              </div>
            </div>
          </div>
        </div>
        <?if($question_is_blog==='f'){?>
          <form method="GET" action="/answer">
            <input type="hidden" name="question" value="<?=$question?>">
            <input id="answer" type="submit" value="answer this question<?=($question_answered_by_me==='t')?' again':''?>" style="margin: 2em auto; display: block;"<?=$uuid?'':' disabled'?>>
          </form>
        <?}?>
        <?foreach(db("select answer_id,answer_markdown,account_id,answer_votes,answer_have_voted,answer_votes_from_me,answer_has_history,license_name,codelicense_name,account_name,account_is_me,account_is_imported
                            ,account_community_se_user_id,answer_se_answer_id
                           , coalesce(account_community_votes,0) account_community_votes
                           , extract('epoch' from current_timestamp-answer_at) answer_when
                           , codelicense_id<>1 and codelicense_name<>license_name has_codelicense
                      from answer natural join account natural join (select question_id,community_id from question) q natural join license natural join codelicense natural left join account_community
                      where question_id=$1
                      order by answer_votes desc, account_community_votes desc, answer_id desc",$question) as $i=>$r){ extract($r);?>
          <div id="a<?=$answer_id?>" class="answer<?=($answer_have_voted==='t')?' voted':''?>" data-id="<?=$answer_id?>">
            <div class="bar">
              <div><span class="title"><?=($i===0)?'Top Answer':('Answer #'.($i+1))?></span></div>
              <div>
                <span>
                  <a href="<?=$license_href?>"><?=$license_name?></a>
                  <?if($has_codelicense==='t'){?><span>+ <a href="/meta?q=24"><?=$codelicense_name?> for original code</a></span><?}?>
                </span>
                <span>
                  <span class="when" data-seconds="<?=$answer_when?>"></span>
                  <?if($account_is_imported==='t'){?>
                    <span>by <a href="<?=$sesite_url.'/users/'.$account_community_se_user_id?>"><?=htmlspecialchars($account_name)?></a> imported <a href="<?=$sesite_url.'/questions/'.$question_se_question_id.'//'.$answer_se_answer_id.'/#'.$answer_se_answer_id?>">from SE</a></span>
                  <?}else{?>
                    <span>by <?=htmlspecialchars($account_name)?></span>
                  <?}?>
                </span>
                <img title="Stars: <?=$account_community_votes?>" class="identicon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" data-fullname="<?=$account_name?>" src="/identicon?id=<?=$account_id?>">
              </div>
            </div>
            <div class="markdown" data-markdown="<?=htmlspecialchars($answer_markdown)?>"><?=htmlspecialchars($answer_markdown)?></div>
            <div class="bar">
              <div>
                <?if($account_is_me==='f'){?>
                  <div class="starrr" data-id="<?=$answer_id?>" data-type="answer" data-votes="<?=$answer_votes_from_me?>" title="rate this answer"></div>
                <?}else{?>
                  <span></span>
                <?}?>
                <span class="score">score: <?=$answer_votes?></span>
                <?if($uuid){?>
                  <span></span>
                  <a href="/answer?id=<?=$answer_id?>">edit</a>
                  <?if($answer_has_history==='t'){?><a href="/answer-history?id=<?=$answer_id?>">history</a><?}?>
                  <?if($account_is_me==='f'){?><a href='.' onclick="$(this).closest('.answer').find('.identicon').click(); return false;">comment</a><?}?>
                <?}?>
              </div>
            </div>
          </div>
        <?}?>
      <?}else{?>
        <div style="margin-bottom: 4rem; display: none; text-align: center;"><a id="more" href=".">show more</a><i class="fa fa-spinner fa-pulse fa-fw" style="display: none"></i></div>
      <?}?>
    </div>
  </main>
  <div id="chat-wrapper" class="pane hidepane" style="background: #<?=$colour_mid?>; flex: 1 1 <?=($uuid)?ccdb("select 100-login_resizer_percent from login"):'30'?>%; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; overflow: hidden;">
    <div id="ios-spacer" style="flex: 0 0 76px;"></div>
    <header style="border-top: 2px solid black;">
      <div style="display: flex; align-items: center;">
        <a <?=$dev?'href="/room?id='.$room.'" ':''?>class="icon"><img src="/roomicon?id=<?=$room?>"></a>
        <?if(!$question){?>
          <select id="room">
            <?foreach(db("select room_id, coalesce(room_name,initcap(community_name)||' Chat') room_name
                          from room natural join community
                          where community_name=$1 and (not room_is_for_question or room_id=$2)
                          order by room_name desc",$community,$room) as $r){ extract($r);?>
              <option<?=($room_id===$room)?' selected':''?> value="<?=$room_id?>"><?=$room_name?></option>
            <?}?>
          </select>
        <?}?>
        <a href="/transcript?room=<?=$room?>" style="color: #<?=$colour_mid?>;">transcript</a>
      </div>
      <input class="panecontrol" type="button" value="questions" onclick="localStorage.removeItem('chat'); $('.pane').toggleClass('hidepane');">
      <?if($uuid) if(intval(ccdb("select account_id from login"))<3){?><input id="poll" type="button" value="poll"><?}?>
    </header>
    <?if($canchat){?>
      <div id="canchat-wrapper" style="flex: 0 0 auto;">
        <div id="chattext-wrapper" style="position: relative; display: flex; border-top: 1px solid #<?=$colour_dark?>;">
          <form action="/upload" method="post" enctype="multipart/form-data"><input id="chatuploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
          <button id="chatupload" class="button" style="position: absolute; right: 0.15em; top: 0; bottom: 0; font-size: 1.5em; color: #<?=$colour_dark?>;" title="embed image"><i class="fa fa-picture-o" style="display: block;"></i></button>
          <textarea id="chattext" style="flex: 0 0 auto; width: 100%; height: 0; resize: none; outline: none; border: none; padding: 0.3em; padding-right: 2rem; margin: 0; font-family: inherit; font-size: inherit;" rows="1" placeholder="type message here" maxlength="5000"></textarea>
        </div>
      </div>
    <?}?>
    <div id="chat" style="display: flex; flex: 1 0 0; min-height: 0;">
      <div id="messages-wrapper" style="flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden;">
        <div id="notification-wrapper">
          <?if($uuid&&((ccdb("select count(*)>0 from chat_notification")==='t')||ccdb("select count(*)>0 from question_notification")==='t'||ccdb("select count(*)>0 from answer_notification")==='t')){?>
            <div id="notifications" style="display: flex; flex-direction: column; flex: 0 1 auto; min-height: 0; max-height: 30vh; border: 2px solid black; border-width: 2px 1px 2px 0; background: #<?=$colour_light?>; padding: 0.3rem; padding-top: 0; overflow-x: hidden; overflow-y: auto;">
              <div style="font-size: 0.9rem; padding: 1px;">Notifications:</div>
              <?foreach(db("with c as (select 'chat' notification_type
                                            , chat_id notification_id
                                            , chat_at notification_at
                                            , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                            , encode(community_mid_shade,'hex') notification_mid_shade
                                            , encode(community_dark_shade,'hex') notification_dark_shade
                                            , community_name notification_community_name
                                            , question_id
                                            , null::text question_title
                                            , null::integer answer_id
                                            , null::boolean answer_notification_is_edit
                                            , room_id notification_room_id
                                            , account_id chat_from_account_id
                                            , chat_reply_id
                                            , chat_markdown
                                            , chat_flag_count
                                            , chat_star_count
                                            , chat_has_history
                                            , question_id is not null chat_is_question_room
                                            , coalesce(room_name,(select question_title from question where question_room_id=room.room_id)) chat_room_name 
                                            , coalesce(nullif(account_name,''),'Anonymous') chat_from_account_name
                                            , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) chat_reply_account_name
                                            , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) chat_reply_account_is_me
                                            , chat_flag_at is not null chat_i_flagged
                                            , chat_star_at is not null chat_i_starred
                                       from chat_notification natural join chat c natural join room natural join community natural join account natural left join chat_flag natural left join chat_star
                                            natural left join (select question_room_id room_id, question_id, question_title from question) q)
                               , q as (select 'question' notification_type
                                            , question_history_id notification_id
                                            , question_notification_at notification_at
                                            , to_char(question_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                            , encode(community_mid_shade,'hex') notification_mid_shade
                                            , encode(community_dark_shade,'hex') notification_dark_shade
                                            , community_name notification_community_name
                                            , question_id
                                            , question_title
                                            , null::integer
                                            , null::boolean
                                            , question_room_id
                                            , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                                       from question_notification natural join question natural join community)
                               , a as (select 'answer' notification_type
                                            , answer_history_id notification_id
                                            , answer_notification_at notification_at
                                            , to_char(answer_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                            , encode(community_mid_shade,'hex') notification_mid_shade
                                            , encode(community_dark_shade,'hex') notification_dark_shade
                                            , community_name notification_community_name
                                            , question_id
                                            , question_title
                                            , answer_id
                                            , answer_notification_is_edit
                                            , question_room_id
                                            , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                                       from answer_notification natural join answer natural join (select question_id,question_title,community_id,question_room_id from question) z natural join community)
                            select * from c union all select * from q union all select * from a
                            order by notification_at limit 20") as $r){ extract($r);?>
                <div id="n<?=$notification_id?>" class="message" style="background: #<?=$notification_mid_shade?>;" data-id="<?=$notification_id?>" data-type="<?=$notification_type?>"<?if($notification_type==='chat'){?> data-name="<?=$chat_from_account_name?>" data-reply-id="<?=$chat_reply_id?>"<?}?>>
                  <?if($notification_type==='chat'){?>
                    <span class="who" title="<?=$chat_from_account_name?><?=$chat_reply_id?' replying to '.(($chat_reply_account_is_me==='t')?'Me':$chat_reply_account_name):''?> in <?=$chat_room_name?>">
                      <?=$chat_from_account_name?>
                      <?if($notification_room_id!==$room){?>
                        <?=$chat_reply_id?' replying to '.(($chat_reply_account_is_me==='t')?'<em>Me</em>':$chat_reply_account_name):''?>
                        <span style="color: #<?=$notification_dark_shade?>;">in</span>
                        <a href="/<?=$notification_community_name?>?<?=($chat_is_question_room==='t')?'q='.$question_id:'room='.$notification_room_id?>" data-room="<?=$notification_room_id?>"  style="color: #<?=$notification_dark_shade?>;" title="<?=$chat_room_name?>"><?=$chat_room_name?></a>
                      <?}else{?>
                        <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$notification_dark_shade.'; text-decoration: none;">&nbsp;replying to&nbsp;</a> '.(($chat_reply_account_is_me==='t')?'<em>Me</em>':$chat_reply_account_name):''?>
                      <?}?>
                      <span class="when" data-at="<?=$notification_at_iso?>"></span>
                      —
                      <span style="color: #<?=$notification_dark_shade?>;">(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
                    </span>
                    <img class="identicon" src="/identicon?id=<?=$chat_from_account_id?>">
                    <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
                    <span class="buttons">
                      <span class="button-group show">
                        <i class="stars <?=($chat_i_starred==='t')?'me ':''?>fa fa-star<?=($chat_i_starred==='t')?'':'-o'?>" data-count="<?=$chat_star_count?>"></i>
                        <i></i>
                        <i class="flags <?=($chat_i_flagged==='t')?'me ':''?>fa fa-flag<?=($chat_i_flagged==='t')?'':'-o'?>" data-count="<?=$chat_flag_count?>"></i>
                        <i></i>
                      </span>
                      <span class="button-group show">
                        <i class="<?=($chat_i_starred==='t')?'me ':''?>fa fa-star<?=($chat_i_starred==='t')?'':'-o'?>" title="star"></i>
                        <i class="fa fa-ellipsis-h" title="more actions"></i>
                        <i class="<?=($chat_i_flagged==='t')?'me ':''?> fa fa-flag<?=($chat_i_flagged==='t')?'':'-o'?>" title="flag"></i>
                        <?if($canchat&&($notification_room_id===$room)){?><i class="fa fa-fw fa-reply fa-rotate-180" title="reply"></i><?}else{?><i></i><?}?>
                      </span>
                      <span class="button-group">
                        <a href="/transcript?room=<?=$notification_room_id?>&id=<?=$notification_id?>#c<?=$notification_id?>" class="fa fa-link" title="permalink"></a>
                        <i class="fa fa-ellipsis-h" title="more actions"></i>
                        <?if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$notification_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
                        <i></i>
                      </span>
                    </span>
                  <?}elseif($notification_type==='question'){?>
                    <div style="display: flex; overflow: hidden; font-size: 0.9rem; white-space: nowrap;">
                      <span class="when" data-at="<?=$notification_at_iso?>"></span>
                      <span style="flex: 0 0 auto;">, question edit:&nbsp;</span>
                      <a href="/question-history?id=<?=$question_id?>#h<?=$notification_id?>" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
                      —
                      <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
                    </div>
                  <?}elseif($notification_type==='answer'){?>
                    <div style="display: flex; overflow: hidden; font-size: 0.9rem; white-space: nowrap;">
                      <span class="when" data-at="<?=$notification_at_iso?>"></span>
                      <span style="flex: 0 0 auto;">, answer <?=($answer_notification_is_edit==='t')?'edit':'posted'?> on:&nbsp;</span>
                      <a href="/answer-history?id=<?=$answer_id?>#h<?=$notification_id?>" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
                      —
                      <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
                    </div>
                  <?}?>
                </div>
              <?}?>
            </div>
            <div style="position: relative;"><div style="position: absolute; z-index: 1; pointer-events: none; height: 2em; width: 100%; background: linear-gradient(#<?=$colour_dark?>80,transparent);"></div></div>
          <?}?>
        </div>
        <div id="messages" style="flex: 1 1 auto; display: flex; flex-direction: column; padding: 0.5em; overflow: auto;">
          <div style="flex: 1 0 0.5em;">
            <?if($question&&(ccdb("select count(*) from (select * from chat where room_id=$1 limit 1) z",$room)==='0')){?>
              <div style="padding: 10vh 20%;">
                <?if($uuid){?>
                  <?if(ccdb("select question_se_question_id is null from question where question_id=$1",$question)==='t'){?>
                    <p>This is a dedicated room for discussion about this question.</p>
                    <p>You can direct a comment to the question poster (or any answer poster) via the 'comment' link under their post.</p>
                  <?}else{?>
                    <p>This is a dedicated room for discussion about this imported question.</p>
                    <p>You can direct a comment to any answer poster via the 'comment' link under their post.</p>
                  <?}?>
                <?}else{?>
                  <p>This is a dedicated room for discussion about this question.</p>
                  <p>Once logged in you can direct comments to the question poster (or any answer poster) here.</p>
                <?}?>
              </div>
            <?}?>
          </div>
        </div>
        <?if($canchat){?>
          <div id="preview" class="message" style="display: block; width: 100%; background: #<?=$colour_light?>; margin-top: 0.1rem; border-top: 1px solid #<?=$colour_dark?>; padding: 0.2rem;">
            <div id="replying" style="width: 100%; font-style: italic; font-size: 0.6rem;" data-id="">
              <span>Preview:</span>
              <i id="cancelreply" class="fa fa-fw fa-times" style="display: none; cursor: pointer;"></i>
            </div>
            <div style="display: flex;"><div class="markdown" data-markdown=""></div></div>
          </div>
        <?}?>
      </div>
      <?if($uuid){?>
        <div id="active" style="flex: 0 0 calc(1.5rem + 5px); display: flex; flex-direction: column; justify-content: space-between; background: #<?=$colour_light?>; border-left: 1px solid #<?=$colour_dark?>; padding: 1px; overflow-y: hidden;">
          <div id="active-rooms" style="flex: 1 1 auto; display: flex; flex-direction: column; overflow-y: hidden;"></div>
          <div id="active-spacer" style="flex: 0 0 auto; padding: 1rem 0; cursor: pointer;">
            <div style="background: #<?=$colour_dark?>; height: 1px;"></div>
          </div>
          <div id="active-users" style="flex: 1 1 auto; display: flex; flex-direction: column-reverse; overflow-y: hidden;"></div>
        </div>
      <?}?>
    </div>
  </div>
</body>   
</html>   
