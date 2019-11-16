<?
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid'] ?? false;
$dev = false;
if($uuid){
  ccdb("select login($1)",$uuid);
  $dev = (ccdb("select account_is_dev from my_account")==='t');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'new-tag': exit(ccdb("select new_question_tag($1,$2)",$_POST['questionid'],$_POST['tagid']));
    case 'remove-tag': exit(ccdb("select remove_question_tag($1,$2)",$_POST['questionid'],$_POST['tagid']));
    default: fail(400,'unrecognized action');
  }
}
if(isset($_GET['resizer'])){
  db("select change_resizer($1)",$_GET['resizer']);
  exit;
}
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
$question = $_GET['q']??'0';
if($question) ccdb("select count(*) from question where question_id=$1",$question)==='1' || die('invalid question id');;
$room = $_GET['room']??($question?ccdb("select question_room_id from question where question_id=$1",$question):ccdb("select community_room_id from community where community_name=$1",$community));
$canchat = false;
if($uuid) $canchat = ccdb("select room_can_chat from room where room_id=$1",$room)==='t';
extract(cdb("select community_id,community_my_power
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1">
  <link rel="stylesheet" href="/highlightjs/default.css">
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/select2.css">
  <link rel="stylesheet" href="/starrr.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    [data-rz-handle] { flex: 0 0 2px; background-color: black; }
    [data-rz-handle] div { width: 2px; background-color: black; }

    <?if($dev){?>.changed { outline: 2px solid orange; }<?}?>
    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { margin-bottom: 2rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; box-shadow: 0.1em 0.1em 0.1em #<?=$colour_dark?>; }
    .answer { margin-bottom: 2em; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; box-shadow: 0.1em 0.1em 0.1em #<?=$colour_dark?>; }
    .answer .bar { border-top: 1px solid #<?=$colour_dark?>; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }
    .bigspacer:not(:hover)>span:first-child { display: none; }
    .bigspacer:hover>span:last-child { display: none; }
    .tags { display: flex; margin-left: 0.25rem; margin-top: 1px; white-space: nowrap; overflow: hidden; }
    .tag { padding: 0.1em 0.2em 0.1em 0.4em; background-color: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; font-size: 0.8rem; border-radius: 0 1rem 1rem 0; position: relative; margin-right: 0.2rem; margin-bottom: 0.1rem; display: inline-block; }
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

    #qa .bar { border: 1px solid #<?=$colour_dark?>; border-width: 1px 0; font-size: 0.8rem; background: #<?=$colour_light?>; display: flex; align-items: center; justify-content: space-between; min-height: calc(1.5rem + 2px); }
    #qa .bar:last-child { border-bottom: none; border-radius: 0 0 0.2rem 0.2rem; }
    #qa .bar+.bar { border-top: none; }
    #qa .bar>* { display: flex; align-items: center; white-space: nowrap; }
    #qa .bar>*>*:not(:last-child) { margin-right: 0.4rem; }
    #qa .identicon, #active-users .identicon { height: 1.5rem; width: 1.5rem; margin: 1px; }
    #qa .markdown { padding: 0.6rem; }
    #qa .markdown img { max-height: 15em; }
    #qa .minibar { border: 1px solid #<?=$colour_light?>; border-width: 1px 0;font-size: 0.8rem; display: flex; align-items: center; justify-content: space-between; min-height: calc(1.5rem + 2px); }
    #qa .minibar:last-child { border-bottom: none; }
    #qa .minibar+.minibar { border-top: none; }
    #qa .bar+.minibar { border-top: none; }
    #qa .minibar>* { display: flex; align-items: center; min-width: 0; }
    #qa .minibar>*>*:not(:last-child) { margin-right: 0.4rem; }
    #qa .minibar .summary { min-width: 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; margin-left: 0.5rem; }
    #qa .minibar>:first-child { flex: 0 1 auto; margin-right: 1rem; text-overflow: ellipsis; }
    #qa .minibar>:last-child { flex: 0 0 auto; margin-left: 1rem; }
    #qa .minibar .score { color: #<?=$colour_dark?>; }
    #qa .minibar .score.me { color: #<?=$colour_highlight?>; }
    #qa .minibar>a:first-child { display: block; text-decoration: none; color: black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding: 0.2rem; }
    #qa .question>a:first-child { display: block; padding: 0.6rem; text-decoration: none; font-size: larger; color: black; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}

    .markdown { overflow: auto; padding-right: 2px; }
    .markdown>:first-child { margin-top: 1px; }
    .markdown>:last-child { margin-bottom: 1px; }
    .markdown ul { padding-left: 2em; }
    .markdown img { max-width: 100%; max-height: 7em; }
    .markdown table { border-collapse: collapse; }
    .markdown td, .markdown th { white-space: nowrap; border: 1px solid black; }
    .markdown blockquote {  padding-left: 0.7em;  margin-left: 0.7em; margin-right: 0; border-left: 0.3em solid #<?=$colour_mid?>; }
    .markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }
    .identicon.pingable:hover { outline: 1px solid #<?=$colour_dark?>; cursor: pointer; }
    .identicon.ping { outline: 1px solid #<?=$colour_highlight?>; }
    #chattext-wrapper:not(:hover) button { display: none; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.6em; position: absolute; }
    .message .identicon { flex: 0 0 1.2rem; height: 1.2rem; margin-right: 0.2rem; margin-top: 0.1rem; }
    .message .markdown-wrapper { display: flex; position: relative; flex: 0 1 auto; max-height: 20vh; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; overflow: hidden; }
    .message .markdown-wrapper .reply { position: absolute; right: 0; bottom: 0; background-color: #fffd; padding: 0.2em; padding-left: 0.4em; }
    .message .buttons { flex: 0 0 auto; max-height: 1.3em; padding: 0.05em 0; }
    .message .button { display: block; white-space: nowrap; color: #<?=$colour_dark?>; line-height: 0; }
    .message .button.me { color: #<?=$colour_highlight?>; }
    .message .button:not(.marked):not(.me) { visibility: hidden; }
    <?if($canchat){?>.message:not(.mine):hover .button { visibility: visible; }<?}else{?>.message .button { cursor: default; }<?}?>
    .message.merged { margin-top: -1px; }
    .message.merged .who,
    .message.merged .identicon { visibility: hidden; }
    .message.mine .buttons .button { cursor: default; }
    .message:target .markdown-wrapper { box-shadow: 0 0 2px 2px #<?=$colour_highlight?> inset; }
    #chat .message .who { top: -1.2em; }
    #chat .message.thread .markdown-wrapper { background: #<?=$colour_highlight?>40; }
    #notifications .message { padding: 0.3em; padding-top: 1.05em; border-radius: 0.2em; }
    #notifications .message .who { top: 0.3rem; }
    #notifications .message+.message { margin-top: 0.2em; }
    #chatupload:active i { color: #<?=$colour_mid?>; }

    .pane { display: flex; }
    @media (max-width: 576px){
      .hidepane { display: none; }
      textarea,select,input { font-size: 16px; }
      #chattext-wrapper:not(:hover) button { display: unset; }
      header { flex-direction: unset; white-space: unset; }
    }
  </style>
  <script src="/lodash.js"></script>
  <script src="/jquery.js"></script>
  <script src="/jquery.waitforimages.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/markdown-it-emoji.js"></script>
  <script src="/markdown-it-footnote.js"></script>
  <script src="/markdown-it-deflist.js"></script>
  <script src="/markdown-it-abbr.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script src="/resizer.js"></script>
  <script src="/favico.js"></script>
  <script src="/select2.js"></script>
  <script src="/starrr.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    moment.locale(window.navigator.userLanguage || window.navigator.language);
    $(function(){
      var md = window.markdownit({ linkify: true, highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitEmoji).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr);
      md.linkify.tlds('kiwi',true).tlds('xyz',true);
      var mdsummary = window.markdownit('zero').enable(['emphasis']);
      var title = document.title, latestChatId;
      var favicon = new Favico({ animation: 'fade', position: 'up' });
      var chatTimer, maxChatChangeID = 0, maxNotificationID = <?=ccdb("select account_notification_id from my_account")?>+0, numNewChats = 0;
      var maxQuestionPollMajorID = 0, maxQuestionPollMinorID = 0;

      function setChatPollTimeout(){
        var chatPollInterval, chatLastChange = Math.round((Date.now() - (new Date($('#messages>.message').last().data('at'))))/1000) || 300;
        if(chatLastChange<15) chatPollInterval = 1000;
        else if(chatLastChange<30) chatPollInterval = 3000;
        else if(chatLastChange<120) chatPollInterval = 5000;
        else if(chatLastChange<600) chatPollInterval = 10000;
        else if(chatLastChange<3600) chatPollInterval = 30000;
        else chatPollInterval = 60000;
        <?if($dev){?>console.log('set poll interval to '+chatPollInterval);<?}?>
        clearTimeout(chatTimer);
        chatTimer = setTimeout(checkChat,chatPollInterval);
      }
      function renderQuestion(){
        $(this).find('.summary span').each(function(){ $(this).html(mdsummary.renderInline($(this).attr('data-markdown'))); });
        $(this).find('.when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
      }
      function updateQuestions(scroll){
        var maxQuestion = $('#qa>:first-child').data('poll-major-id');
        if($('#qa').scrollTop()<100) scroll = true;
        $.get('/questions?community=<?=$community?>'+(($('#qa').children().length===0)?'':'&id='+maxQuestion),function(data) {
          if($('#qa>:first-child').data('poll-major-id')===maxQuestion){
            var newquestions;
            $(data).each(function(){ $('#'+$(this).attr('id')).removeAttr('id').slideUp({ complete: function(){ $(this).remove(); } }); });
            newquestions = $(data).filter('.question').prependTo($('#qa')).hide().slideDown(maxQuestionPollMajorID?400:0);
            if(maxQuestionPollMajorID) numNewChats += newquestions.length;
            if(maxQuestionPollMajorID && (document.visibilityState==='hidden')){ document.title = '('+numNewChats+') '+title; }
            newquestions.each(renderQuestion);
            newquestions.each(function(){
              if($(this).data('poll-major-id')>maxQuestionPollMajorID) maxQuestionPollMajorID = $(this).data('poll-major-id');
              if($(this).data('poll-minor-id')>maxQuestionPollMinorID) maxQuestionPollMinorID = $(this).data('poll-minor-id');
            });
            if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
          }
          <?if($uuid){?>setChatPollTimeout();<?}?>
        },'html').fail(setChatPollTimeout);
      }
      function updateChat(scroll){
        var maxChat = $('#messages>.message:last-child').data('id');
        if(($('#messages').scrollTop()+$('#messages').innerHeight()+4)>$('#messages').prop("scrollHeight")) scroll = true;
        $.get('/chat?room=<?=$room?>'+(($('#messages').children().length===1)?'':'&id='+maxChat),function(data) {
          if($('#messages>.message:last-child').data('id')===maxChat){
            var newchat = $(data).appendTo($('#messages')).css('opacity','0').find('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); }).end();
            if(maxChatChangeID) numNewChats += newchat.filter('.message').length;
            newchat.find('img').waitForImages(true).done(function(){
              newchat.css('opacity','1');
              if(scroll){
                setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")).css('scroll-behavior','smooth'); },0);
              }else{
                $('#messages').css('border-bottom','3px solid #<?=$colour_highlight?>').scroll(_.debounce(function(){ $('#messages').css('border-bottom','none'); }));
              }
            });
            if(maxChatChangeID && (document.visibilityState==='hidden')){ document.title = '('+numNewChats+') '+title; }
            $('.message').each(function(){
              var id = $(this).data('id'), rid = id;
              function foo(b){
                if(arguments.length!==0) $(this).addClass('t'+id);
                if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message[data-id='+$(this).data('reply-id')+']')[0], true);
                if(arguments.length===0 || b===false) $('.message[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
              }
              foo.call(this);
            });
            newchat.filter('.message').find('.markdown img').each(function(){ if(!$(this).parent().is('a')){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); } });
            newchat.filter('.message').find('.markdown a').attr({ 'rel':'nofollow', 'target':'_blank' });
            newchat.filter('.bigspacer').each(function(){
              $(this).children(':first-child').text(moment($(this).data('at')).calendar(null, { sameDay: 'LT', lastDay: '[Yesterday] LT', lastWeek: '[Last] dddd LT', sameElse: 'LLLL' })).end()
                     .children(':last-child').text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
            newchat.filter('.message').find('.when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
            newchat.filter('.message').find('.who a').filter(function(){ return !$(this).closest('div').hasClass('t'+$(this).attr('href').substring(2)); }).each(function(){
              var id = $(this).attr('href').substring(2);
              $(this).attr('href','/transcript?room=<?=$room?>&id='+id+'#c'+id);
            });
            if(!maxChatChangeID) $('#messages').children().first().next().filter('.spacer').remove();
            if(!maxChatChangeID) $('#messages>.message').first().removeClass('merged');
            if(!maxChatChangeID) $('#messages>.message').each(function(){ if($(this).data('change-id')>maxChatChangeID) maxChatChangeID = $(this).data('change-id'); });
            if(scroll) setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },0);
            <?if($uuid){?>
              $.get('/chat?room='+<?=$room?>+'&activeusers').done(function(r){
                var savepings = $('#active-users .ping').map(function(){ return $(this).data('id'); }).get();
                $('#active-users').html(r);
                $.each(savepings,function(){ $('#active-users .identicon[data-id='+this+']').addClass('ping'); });
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
        $.get('/chat?change&id='+id,function(r){
          var j = JSON.parse(r), t = $('#c'+id), s = t.find('.buttons>.button').first(), f = t.find('.buttons>.button').last();
          if(!t.hasClass('mine')){
            s.toggleClass('star',!j.i_starred).children('i').toggleClass('fa-star-o',!j.i_starred);
            s.toggleClass('me unstar',j.i_starred).children('i').toggleClass('fa-star',j.i_starred);
            f.toggleClass('flag',!j.i_flagged).children('i').toggleClass('fa-flag-o',!j.i_flagged);
            f.toggleClass('me unflag',j.i_flagged).children('i').toggleClass('fa-flag',j.i_flagged);
          }
          s.toggleClass('marked',j.stars>0);
          f.toggleClass('marked',j.flags>0);
          s.children('span').text((j.stars)||'');
          f.children('span').text((j.flags)||'');
          t.removeClass('changed');
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
          $('#notification-wrapper .markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
          if(scroll){ $('#messages').css('scroll-behavior','auto'); $('#messages').scrollTop($('#messages').prop("scrollHeight")); $('#messages').css('scroll-behavior','smooth'); }
          if(scroll) setTimeout(function(){ $('#messages').css('scroll-behavior','auto'); $('#messages').scrollTop($('#messages').prop("scrollHeight")); $('#messages').css('scroll-behavior','smooth'); },0);
          setChatPollTimeout();
        }).fail(setChatPollTimeout);
      }
      function checkChat(){
        $.get('/poll?room=<?=$room?>').done(function(r){
          var j = JSON.parse(r);
          if(j.c>+$('#messages>.message:last-child').data('id')){
            <?if($dev){?>console.log('updating chat');<?}?>
            updateChat();
          }else if(j.n>maxNotificationID){
            <?if($dev){?>console.log('updating notifications');<?}?>
            updateNotifications();
            maxNotificationID = j.n;
          <?if(!$question){?>
            }else if(j.Q>maxQuestionPollMajorID){
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
            }else if(j.q>maxQuestionPollMinorID){
              <?if($dev){?>console.log('updating guestion change flag statuses');<?}?>
              updateQuestionPollIDs();
              maxQuestionPollMinorID = j.q
            }else if($('.question.changed').length){
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

      $('#join').click(function(){
        if(confirm('This will set a cookie to identify your account')) { $.ajax({ type: "POST", url: '/uuid', async: false }).fail(function(r){
          alert((r.status)===429?'Rate limit hit, please try again later':responseText);
        }) };
        location.reload(true);
      });
      $('#link').click(function(){ var pin = prompt('Enter PIN from account profile'); if(pin!==null) { $.ajax('/uuid',{ type: "POST", data: { pin: pin }, async: false }).fail(function(r){ alert(r.responseText); }); location.reload(true); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#chat-wrapper').on('click','.reply', function(){
        $('#replying').attr('data-id',$(this).closest('.message').data('id')).data('update')();
        $('#chattext').focus();
        setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },500);
      });
      $('#chat-wrapper').on('click','.star', function(){
        var t = $(this);
        t.css({'opacity':'0.3','pointer-events':'none'});
        $.post('/chat',{ action: 'star', id: t.closest('.message').data('id') }).done(function(r){
          t.css({'opacity':'1','pointer-events':'auto'});
          t.removeClass('star').addClass('me unstar').children('i').toggleClass('fa-star fa-star-o').next().each(function(){ $(this).text(+$(this).text()+1); });
        });
      });
      $('#chat-wrapper').on('click','.unstar', function(){
        var t = $(this);
        t.css({'opacity':'0.3','pointer-events':'none'});
        $.post('/chat',{ action: 'unstar', id: t.closest('.message').data('id') }).done(function(r){
          t.css({'opacity':'1','pointer-events':'auto'});
          t.removeClass('me unstar').addClass('star').children('i').toggleClass('fa-star fa-star-o').next().each(function(){ $(this).text(((+$(this).text()-1)===0)?'':(+$(this).text()-1)); });
        });
      });
      $('#chat-wrapper').on('click','.flag', function(){
        var t = $(this);
        t.css({'opacity':'0.3','pointer-events':'none'});
        $.post('/chat',{ action: 'flag', id: t.closest('.message').data('id') }).done(function(r){
          t.css({'opacity':'1','pointer-events':'auto'});
          t.removeClass('flag').addClass('me unflag').children('i').toggleClass('fa-flag fa-flag-o').next().each(function(){ $(this).text(+$(this).text()+1); });
        });
      });
      $('#chat-wrapper').on('click','.unflag', function(){
        var t = $(this);
        t.css({'opacity':'0.3','pointer-events':'none'});
        $.post('/chat',{ action: 'unflag', id: t.closest('.message').data('id') }).done(function(r){
          t.css({'opacity':'1','pointer-events':'auto'});
          t.removeClass('me unflag').addClass('flag').children('i').toggleClass('fa-flag fa-flag-o').next().each(function(){ $(this).text(((+$(this).text()-1)===0)?'':(+$(this).text()-1)); });
        });
      });
      $('body').on('click','.identicon.pingable', function(){ if(!$(this).hasClass('ping')){ textareaInsertTextAtCursor($('#chattext'),'@'+$(this).data('name')); } $(this).toggleClass('ping'); $('#chattext').focus(); $('#replying').data('update')() });
      $('#chat-wrapper').on('click','.dismiss', function(){
        $.post('/chat', { action: 'dismiss', id: $(this).closest('.message').attr('data-id'), action: 'dismiss' }).done(function(){ updateNotifications(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#replying').data('update',function(){
        var state = $('#replying').attr('data-id') || $('.ping').length, strings = [];
        if($('#replying').attr('data-id')) strings.push('Replying to: '+$('#c'+$('#replying').attr('data-id')).data('name'));
        if($('.ping').length) strings.push('Pinging: '+$('.ping').map(function(){ return $(this).data('fullname'); }).get().join(', '));
        $('#replying').children('span').text(strings.join(', '));
        if(state && $('#replying').is(":hidden")) $('#replying').slideDown('fast');
        if(!state && $('#replying').is(":visible")) $('#replying').slideUp('fast');
      });
      $('#replying>button').click(function(){
        $('.ping').removeClass('ping');
        $('#replying').attr('data-id','').data('update')();
      });
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      $('.community').change(function(){ window.location = '/'+$(this).val().toLowerCase(); });
      $('#tags').select2({ placeholder: "select a tag" });
      function tagdrop(){ $('#tags').select2('open'); };
      $('#tags').on('select2:close', function (e) { setTimeout(function(){ $('.newtag').one('click',tagdrop); },200); });
      $('#tags').change(function(){ $.post(window.location.href, { questionid: $(this).data('question-id'), tagid: $(this).val(), action: 'new-tag' }).done(function(){ window.location.reload(); }); });
      $('.newtag').one('click',tagdrop);
      $('.tag i').click(function(){ $.post(window.location.href, { questionid: $(this).parent().data('question-id'), tagid: $(this).parent().data('tag-id'), action: 'remove-tag' }).done(function(){ window.location.reload(); }); });
      $('#room').change(function(){ window.location = '/<?=$community?>?room='+$(this).val(); });
      $('#chattext').on('input', function(){
        if(this.scrollHeight>$(this).outerHeight()) $(this).css("min-height",this.scrollHeight);
        if($(this).val().trim()){ $('#preview .markdown').html(md.render($('#chattext').val())); $('#preview:hidden').slideDown('fast'); } else { $('#preview:visible').slideUp('fast'); }
        setTimeout(function(){ $('#messages').scrollTop($('#messages').prop("scrollHeight")); },500);
      });
      $('#chattext').keydown(function(e){
        var t = $(this), replyid = $('#replying').attr('data-id');
        if((e.keyCode || e.which) == 13) {
          if(!e.shiftKey) {
            if(t.val().trim()){
              clearTimeout(chatTimer);
              arr = [];
              $('.ping').each(function(){ arr.push($(this).data('id')); });
              $.post('/chat', { room: <?=$room?>, msg: t.val(), replyid: replyid, pings: arr, action: 'new' }).done(function(){
                t.val('').prop('disabled',false).focus().css('height', 'auto').trigger('input').css('min-height',0);
                if(replyid) $('#notifications .message[data-id='+replyid+']').remove();
                if($('#notifications .message').children().length===0) $('#notification-wrapper').children().remove();
                updateChat();
              }).fail(function(r){
                alert(r.status+' '+r.statusText+'\n'+r.responseText);
                t.prop('disabled',false).focus();
              });
              $('#replying').attr('data-id','').slideUp('fast');
              $('.ping').removeClass('ping');
              $(this).prop('disabled',true);
            }
            return false;
          }else{
            textareaInsertTextAtCursor($(this),'  ');
          }
        }
      });
      document.addEventListener('visibilitychange', function(){ numNewChats = 0; if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
      const myResizer = new Resizer('body', { callback: function(w) { $.get(window.location.href, { resizer: Math.round(w) }); } });
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
        $.ajax({ url: "/upload", type: "POST", data: d, processData: false, cache: false, contentType: false }).done(function(r){
          $('#chattext').prop('disabled',false).focus();
          textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')');
        }).fail(function(r){
          alert(r.status+' '+r.statusText+'\n'+r.responseText);
          $('#chattext').prop('disabled',false).focus();
        });
        return false;
      });
      $('#qa .when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
      $('#qa .markdown a').attr({ 'rel':'nofollow', 'target':'_blank' });
      $('#qa .markdown img').each(function(i){ if(!$(this).parent().is('a')){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.markdown').parent('div').attr('id')+'"></a>'); } });
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
                $.post('/'+t.data('type'),{ action: 'vote', id: t.data('id'), votes: n }).done(function(r){
                  t.css({'opacity':'1','pointer-events':'auto'}).siblings('.score').html('<span>score: '+r+'</span>');
                  v = n;
                }).fail(function(r){ alert((r.status)===429?'Rate limit hit, please try again later':r.responseText); });
              }
            }
          });
          t.find('a').removeAttr('href');
        });
      <?}?>
      $('#qa .summary span[data-markdown]').each(function(){ $(this).html(mdsummary.renderInline($(this).attr('data-markdown'))); });
      updateChat(true);
      <?if(!$question){?>updateQuestions(true);<?}?>
      setTimeout(function(){ $('.answer:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 0);
    });
  </script>
  <title><?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex;">
  <main class="pane hidepane" style="flex-direction: column; flex: 1 1 <?=($uuid)?ccdb("select login_resizer_percent from login"):'50'?>%; overflow: hidden;">
    <header style="border-bottom: 2px solid black; display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto;">
      <div style="margin: 0.5em; margin-right: 0.1em;">
        <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers</a>
        <select class="community">
          <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
            <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
          <?}?>
        </select>
        <input class="panecontrol" type="button" value="chat" onclick="$('.pane').toggleClass('hidepane');">
      </div>
      <div style="display: flex; height: 100%; align-items: center;">
        <?if(!$uuid){?><input id="join" type="button" value="join" style="margin: 0.5em;"> or <input id="link" type="button" value="link" style="margin: 0.5em;"><?}?>
        <?if($uuid){?><form method="GET" action="/question"><input type="hidden" name="community" value="<?=$community?>"><input id="ask" type="submit" value="ask question" style="margin: 0.5em;"></form><?}?>
        <?if($uuid){?><a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2em; display: block; height: 2.4em;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a><?}?>
      </div>
    </header>
    <div id="qa" style="background-color: white; overflow: auto; padding: 0.5em; scroll-behavior: smooth;">
      <?if($question){?>
        <?extract(cdb("select question_title,question_markdown,question_votes,question_have_voted,question_votes_from_me,question_answered_by_me,question_has_history,license_name,license_href,codelicense_name,account_id,account_name,account_is_me
                            , coalesce(account_community_votes,0) account_community_votes
                            , codelicense_id<>1 and codelicense_name<>license_name has_codelicense
                            , case question_type when 'question' then '' when 'meta' then 'Meta Question: ' when 'blog' then 'Blog Post: ' end question_type
                            , question_type<>'question' question_is_votable
                            , question_type='blog' question_is_blog
                            , extract('epoch' from current_timestamp-question_at) question_when
                       from question natural join account natural join license natural join codelicense natural left join account_community
                       where question_id=$1",$question));?>
        <div id="question" class="<?=($question_have_voted==='t')?'voted':''?>" style="border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; box-shadow: 0.1em 0.1em 0.1em #<?=$colour_dark?>;">
          <div style="font-size: larger; text-shadow: 0.1em 0.1em 0.1em lightgrey; padding: 0.6rem;"><?=$question_type.htmlspecialchars($question_title)?></div>
          <div class="bar">
            <div>
              <img title="Reputation: <?=$account_community_votes?>" class="identicon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" data-fullname="<?=$account_name?>" src="/identicon.php?id=<?=$account_id?>">
              <span><span class="when" data-seconds="<?=$question_when?>"></span>, by <?=htmlspecialchars($account_name)?></span>
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
          <div id="markdown" class="markdown" data-markdown="<?=htmlspecialchars($question_markdown)?>"></div>
          <div class="bar">
            <div>
              <?if($question_is_votable==='t'){?>
                <?if($account_is_me==='f'){?>
                  <div class="starrr" data-id="<?=$question?>" data-type="question" data-votes="<?=$question_votes_from_me?>"></div>
                <?}else{?>
                  <span></span>
                <?}?>
                <span class="score">score: <?=$question_votes?></span>
              <?}?>
              <?if($uuid){?>
                <span></span>
                <?if(($account_is_me==='t')||($question_is_blog==='f')){?><a href="/question?id=<?=$question?>">edit</a><?}?>
                <?if($question_has_history==='t'){?><a href="/question-history?id=<?=$question?>">history</a><?}?>
              <?}?>
            </div>
            <div>
            </div>
          </div>
        </div>
        <?if($uuid && ($question_is_blog==='f')){?>
          <form method="GET" action="/answer">
            <input type="hidden" name="question" value="<?=$question?>">
            <input id="answer" type="submit" value="answer this question<?=($question_answered_by_me==='t')?' again':''?>" style="margin: 2em auto; display: block;">
          </form>
        <?}?>
        <?foreach(db("select answer_id,answer_markdown,account_id,answer_votes,answer_have_voted,answer_votes_from_me,answer_has_history,license_name,codelicense_name,account_name,account_is_me
                           , coalesce(account_community_votes,0) account_community_votes
                           , extract('epoch' from current_timestamp-answer_at) answer_when
                           , codelicense_id<>1 and codelicense_name<>license_name has_codelicense
                      from answer natural join account natural join (select question_id,community_id from question) q natural join license natural join codelicense natural left join account_community
                      where question_id=$1
                      order by answer_votes desc, answer_votes desc, answer_id desc",$question) as $r){ extract($r);?>
          <div id="a<?=$answer_id?>" class="answer<?=($answer_have_voted==='t')?' voted':''?>" data-id="<?=$answer_id?>">
            <div class="markdown" data-markdown="<?=htmlspecialchars($answer_markdown)?>"></div>
            <div class="bar">
              <div>
                <?if($account_is_me==='f'){?>
                  <div class="starrr" data-id="<?=$answer_id?>" data-type="answer" data-votes="<?=$answer_votes_from_me?>"></div>
                <?}else{?>
                  <span></span>
                <?}?>
                <span class="score">score: <?=$answer_votes?></span>
                <?if($uuid){?>
                  <span></span>
                  <a href="/answer?id=<?=$answer_id?>">edit</a>
                  <?if($answer_has_history==='t'){?><a href="/answer-history?id=<?=$answer_id?>">history</a><?}?>
                <?}?>
              </div>
              <div>
                <span>
                  <a href="<?=$license_href?>"><?=$license_name?></a>
                  <?if($has_codelicense==='t'){?><span>+ <a href="/meta?q=24"><?=$codelicense_name?> for original code</a></span><?}?>
                </span>
                <span><span class="when" data-seconds="<?=$answer_when?>"></span> by <?=htmlspecialchars($account_name)?></span>
                <img title="Reputation: <?=$account_community_votes?>" class="identicon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" src="/identicon.php?id=<?=$account_id?>">
              </div>
            </div>
          </div>
        <?}?>
      <?}?>
    </div>
  </main>
  <div id="chat-wrapper" class="pane" style="background-color: #<?=$colour_mid?>; flex: 1 1 <?=($uuid)?ccdb("select 100-login_resizer_percent from login"):'50'?>%; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; overflow: hidden;">
    <header style="flex: 0 0 auto; border-top: 2px solid black; padding: 0.5em;">
      <?if(!$question){?>
        <select class="community">
          <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
            <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
          <?}?>
        </select>
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
      <input class="panecontrol" type="button" value="questions" onclick="$('.pane').toggleClass('hidepane');">
      <?if($uuid) if(intval(ccdb("select account_id from login"))<3){?><input id="poll" type="button" value="poll"><?}?>
    </header>
    <?if($canchat){?>
      <div id="canchat-wrapper" style="flex: 0 0 auto;">
        <div id="preview" class="message" style="width: 100%; background-color: #<?=$colour_light?>; border-top: 1px solid #<?=$colour_dark?>; padding: 0.2em; display: none;">
          <div class="markdown-wrapper">
            <div class="markdown" data-markdown="">
            </div>
          </div>
        </div>
        <div id="chattext-wrapper" style="position: relative; display: flex; border-top: 1px solid #<?=$colour_dark?>;">
          <form action="/upload" method="post" enctype="multipart/form-data"><input id="chatuploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
          <button id="chatupload" class="button" style="position: absolute; right: 0.15em; top: 0; bottom: 0; font-size: 1.5em; color: #<?=$colour_dark?>;" title="embed image"><i class="fa fa-picture-o" style="display: block;"></i></button>
          <textarea id="chattext" style="flex: 0 0 auto; width: 100%; resize: none; outline: none; border: none; padding: 0.3em; margin: 0; font-family: inherit; font-size: inherit;" rows="1" placeholder="type message here" maxlength="5000" autofocus></textarea>
        </div>
        <div id="replying" style="width: 100%; background-color: #<?=$colour_light?>; border-top: 1px solid #<?=$colour_dark?>; padding: 0.1em 0.3em; display: none; font-style: italic; font-size: smaller;" data-id="">
          <span></span>
          <button id="cancelreply" class="button" style="float: right;">&#x2573;</button>
        </div>
      </div>
    <?}?>
    <div id="chat" style="display: flex; flex: 1 0 0; min-height: 0;">
      <div id="messages" style="flex: 1 1 auto; display: flex; flex-direction: column; padding: 0.5em; overflow: auto;"><div style="flex: 1 0 0.5em;"></div></div>
      <?if($uuid){?>
        <div id="active-users" style="flex: 0 0 calc(1.5rem + 5px); display: flex; flex-direction: column-reverse; align-items: flex-start; background-color: #<?=$colour_light?>; border-left: 1px solid #<?=$colour_dark?>; padding: 1px; overflow-y: hidden;"></div>
      <?}?>
    </div>
    <div id="notification-wrapper">
      <?if($uuid&&(ccdb("select count(*)>0 from chat_notification")==='t')){?>
        <div id="notifications" style="display: flex; flex-direction: column; flex: 0 1 auto; min-height: 0; max-height: 30vh; border-bottom: 1px solid darkgrey; background-color: #<?=$colour_light?>; padding: 0.3em; overflow-x: hidden; overflow-y: scroll;">
          <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,room_id,community_name,question_id
                             , question_id is not null is_question_room
                             , coalesce(room_name,(select question_title from question where question_room_id=room.room_id)) room_name
                             , coalesce(nullif(account_name,''),'Anonymous') account_name
                             , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                             , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                             , round(extract('epoch' from current_timestamp-chat_at)) chat_ago
                              , chat_flag_at is not null i_flagged
                              , (chat_flag_count-(chat_flag_at is not null)::integer) > 0 flagged_by_other
                              , chat_star_at is not null i_starred
                              , (chat_star_count-(chat_star_at is not null)::integer) > 0 starred_by_other
                             , encode(community_mid_shade,'hex') chat_mid_shade
                             , encode(community_dark_shade,'hex') chat_dark_shade
                        from chat_notification natural join chat c natural join room natural join community natural join account natural left join chat_flag natural left join chat_star
                             natural left join (select question_room_id room_id, question_id, question_title from question) q
                        order by chat_at limit 100") as $r){ extract($r);?>
            <div class="message" style="background-color: #<?=$chat_mid_shade?>;" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
              <small class="who">
                <?=($account_is_me==='t')?'<em>Me</em>':$account_name?>
                <?if($room_id!==$room){?>
                  <?=$chat_reply_id?' replying to</span> '.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>
                <?}else{?>
                  <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$chat_dark_shade.'; text-decoration: none;">&nbsp;replying to&nbsp;</a> '.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>
                <?}?>
                <?if($room_id!==$room){?>
                  <span style="color: #<?=$chat_dark_shade?>;">in&nbsp;</span>
                  <a href="/<?=$community_name?>?<?=($is_question_room==='t')?'q='.$question_id:'room='.$room_id?>" style="color: #<?=$chat_dark_shade?>;"><?=$room_name?></a>
                <?}?>
                —
                <span class="when" data-seconds="<?=$chat_ago?>"></span>
                <span style="color: #<?=$chat_dark_shade?>;">(view <a href="/transcript?room=<?=$room_id?>&id=<?=$chat_id?>#c<?=$chat_id?>" style="color: #<?=$chat_dark_shade?>;">transcript</a> or <a href='.' class="dismiss" style="color: #<?=$chat_dark_shade?>;">dismiss</a>)</span>
              </small>
              <img class="identicon" src="/identicon.php?id=<?=$account_id?>">
              <div class="markdown-wrapper">
                <?if($canchat&&($room_id===$room)){?><button class="button reply" title="reply"><i class="fa fa-reply fa-rotate-180"></i></button><?}?>
                <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
              </div>
              <span class="buttons">
                <button class="button<?=($starred_by_other==='t')?' marked':''?> <?=($i_starred==='t')?'me unstar':'star'?>"><i class="fa fa-fw fa-star<?=($i_starred==='t')?'':'-o'?>"></i><span><?=($chat_star_count>0)?$chat_star_count:''?></span></button>
                <button class="button<?=($flagged_by_other==='t')?' marked':''?> <?=($i_flagged==='t')?'me unflag':'flag'?>"><i class="fa fa-fw fa-flag<?=($i_flagged==='t')?'':'-o'?>"></i><span><?=($chat_flag_count>0)?$chat_flag_count:''?></span></button>
              </span>
            </div>
          <?}?>
        </div>
        <div style="position: relative;"><div style="position: absolute; height: 2em; width: 100%; background: linear-gradient(darkgrey,#<?=$colour_mid?>00);"></div></div>
      <?}?>
    </div>
  </div>
</body>   
</html>   
