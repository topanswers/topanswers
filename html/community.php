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
$uuid = $_COOKIE['uuid'] ?? false;
if($uuid) ccdb("select login($1)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'dismiss': exit(db("select dismiss_notification($1)",$_POST['id']));
    case 'new-chat': exit(db("select new_chat($1,$2,nullif($3,'')::integer,('{'||$4||'}')::integer[])",$_POST['room'],$_POST['msg'],$_POST['replyid']??'',isset($_POST['pings'])?implode(',',$_POST['pings']):''));
    case 'new-tag': exit(db("select new_question_tag($1,$2)",$_POST['questionid'],$_POST['tagid']));
    case 'remove-tag': exit(db("select remove_question_tag($1,$2)",$_POST['questionid'],$_POST['tagid']));
    default: die('unrecognized action');
  }
}
if(isset($_GET['flagchatid'])){
  exit(ccdb("select set_chat_flag($1)",$_GET['flagchatid']));
}
if(isset($_GET['unflagchatid'])){
  exit(ccdb("select remove_chat_flag($1)",$_GET['unflagchatid']));
}
if(isset($_GET['starchatid'])){
  exit(ccdb("select set_chat_star($1)",$_GET['starchatid']));
}
if(isset($_GET['unstarchatid'])){
  exit(ccdb("select remove_chat_star($1)",$_GET['unstarchatid']));
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
  <link rel="stylesheet" href="/select2.css">
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

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { display: block; text-decoration: none; margin-bottom: 0.5em; padding: 1em; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; color: black; white-space: nowrap; overflow: hidden; }
    .answer { margin-bottom: 2em; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; box-shadow: 0.1em 0.1em 0.2em #a794b4; }
    .answer .bar { border-top: 1px solid #<?=$colour_dark?>; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    .tag { padding: 0.1em 0.2em 0.1em 0.4em; background-color: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; font-size: 0.8rem; border-radius: 0 1rem 1rem 0; position: relative; margin-right: 0.2rem; margin-bottom: 0.1rem; display: inline-block; }
    .tag::after { position: absolute; border-radius: 50%; background: #<?=$colour_light?>; border: 1px solid #<?=$colour_dark?>; height: 0.5rem; width: 0.5rem; content: ''; top: calc(50% - 0.25rem); right: 0.25rem; box-sizing: border-box; }
    .tag i { visibility: hidden; cursor: pointer; position: relative; z-index: 1; color: #<?=$colour_dark?>; background: #<?=$colour_mid?>; border-radius: 50%; }
    .tag i::before { border-radius: 50%; }
    <?if($uuid&&$question){?>.tag:hover i { visibility: visible; }<?}?>
    .newtag { position: relative; cursor: pointer; }
    .newtag .tag { opacity: 0.4; margin: 0; }
    .newtag:hover .tag { opacity: 1; }

    #qa .bar { font-size: 0.6em; padding: 0.6rem; background: #<?=$colour_light?>; }
    #qa .markdown { padding: 0.6rem; }
    #qa .when { color: #<?=$colour_dark?>; }

    .markdown { overflow: auto; }
    .markdown>:first-child { margin-top: 0; }
    .markdown>:last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 2em; }
    .markdown img { max-height: 7em; }
    .markdown table { border-collapse: collapse; }
    .markdown td, .markdown th { white-space: nowrap; border: 1px solid black; }
    .markdown blockquote {  padding-left: 0.7em;  margin-left: 0.7em; margin-right: 0; border-left: 0.3em solid #<?=$colour_mid?>; }
    .markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    .markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }
    .active-user { height: 1.5em; width: 1.5em; margin: 0.1em; }
    .active-user:not(.me):hover { outline: 1px solid #<?=$colour_dark?>; cursor: pointer; }
    .active-user.ping { outline: 1px solid #<?=$colour_highlight?>; }
    #chattext-wrapper:not(:hover) button { display: none; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.6em; position: absolute; }
    .message .identicon { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; }
    .message .markdown-wrapper { display: flex; position: relative; flex: 0 1 auto; max-height: 20vh; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; overflow: hidden; }
    .message .markdown-wrapper .reply { position: absolute; right: 0; bottom: 0; background-color: #fffd; padding: 0.2em; padding-left: 0.4em; }
    .message .buttons { flex: 0 0 auto; max-height: 1.3em; padding: 0.05em 0; }
    .message .button { display: block; white-space: nowrap; color: #<?=$colour_dark?>; line-height: 0; }
    .message .button:not(.marked) { visibility: hidden; }
    .message:not(.mine):hover .button { visibility: visible; }
    .message.merged { margin-top: -1px; }
    .message.merged .who,
    .message.merged .identicon { visibility: hidden; }
    .message.mine .buttons .button { cursor: default; }
    #chat .message .who { top: -1.2em; }
    #chat .message.thread .markdown-wrapper { background: #<?=$colour_highlight?>40; }
    #notifications .message { padding: 0.3em; padding-top: 1.05em; border-radius: 0.2em; }
    #notifications .message .who { top: 0.5em; }
    #notifications .message+.message { margin-top: 0.2em; }
    #chatupload:active i { color: #<?=$colour_mid?>; }
  </style>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script src="/resizer.js"></script>
  <script src="/favico.js"></script>
  <script src="/select2.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ linkify: true, highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }}).use(window.markdownitSup).use(window.markdownitSub);
      var chatChangeId = <?=ccdb("select room_latest_change_id from room where room_id=$1",$room)?>;
      var notificationChangeId = <?=ccdb("select coalesce(max(chat_id),0) from chat_notification natural join chat")?>;
      var chatLastChange = <?=ccdb("select extract(epoch from current_timestamp-room_latest_change_at)::integer from room where room_id=$1",$room)?>;
      var chatPollInterval, title = document.title, latestChatId;
      var favicon = new Favico({ animation: 'fade', position: 'up' });
      function setChatPollInterval(){
        if(chatLastChange<5) chatPollInterval = 1000;
        else if(chatLastChange<15) chatPollInterval = 3000;
        else if(chatLastChange<30) chatPollInterval = 5000;
        else if(chatLastChange<300) chatPollInterval = 10000;
        else if(chatLastChange<3600) chatPollInterval = 30000;
        else chatPollInterval = 60000;
      }
      function updateChat(){
        var tempScrollTop = $('#messages .message:first').position().top, firstId = $('#messages .message:first').data('id');
        $.get(window.location.href,function(data) {
          var arr = [], newChats;
          $('.ping').each(function(){ arr.push($(this).data('id')); });
          $('#chat').replaceWith($('<div />').append(data).find('#chat'));
          $('#notification-wrapper').replaceWith($('<div />').append(data).find('#notification-wrapper'));
          $.each(arr, function(k,v){ $('.active-user[data-id='+v+']').addClass('ping'); });
          $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
          newChats = $('#messages .message[data-id="'+latestChatId+'"]').prevAll('.message').length;
          if(document.visibilityState==='hidden'){ document.title = (newChats?('('+newChats+') '):'')+title; }
          chatLastChange = 0;
          initChat();
          if(($('#messages .message:first').data('id') === firstId) || (($('#messages')[0].scrollHeight+40) > ($('#messages').scrollTop()+$('#messages').outerHeight()))){
            $('#messages').scrollTop($('#messages').scrollTop()-tempScrollTop+$('#messages .message[data-id="'+firstId+'"]').position().top);
          }
          $('#chattext').trigger('input');
        },'html');
      }
      function checkChat(){
        $.get('/change?room=<?=$room?>',function(r){
          if((chatChangeId!==JSON.parse(r).chat)||(notificationChangeId!==JSON.parse(r).notification)){
            chatChangeId = JSON.parse(r).chat;
            notificationChangeId = JSON.parse(r).notification;
            updateChat();
          }else{
            chatLastChange += Math.floor(chatPollInterval/1000);
            setChatPollInterval();
          }
        });
      }
      function pollChat() { checkChat(); setTimeout(pollChat, chatPollInterval); }
      function initChat() {
        setChatPollInterval();
        $('.message').each(function(){
          var id = $(this).data('id'), rid = id;
          function foo(b){
            if(arguments.length!==0) $(this).addClass('t'+id);
            if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message[data-id='+$(this).data('reply-id')+']')[0], true);
            if(arguments.length===0 || b===false) $('.message[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
          }
          foo.call(this);
        });
        $('.message .markdown img').each(function(i){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+i+'"></a>'); });
        $('.message .markdown a').attr({ 'rel':'nofollow', 'target':'_blank' });
        $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
        $('.message .when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
        $('#messages').scrollTop($('#messages')[0].scrollHeight);
        favicon.badge($('#notifications .message').length);
      }
      function textareaInsertTextAtCursor(e,t) {
        var v = e.val(), s = e.prop('selectionStart')+t.length;
        e.val(v.substring(0,e.prop('selectionStart'))+t+v.substring(e.prop('selectionEnd'),v.length));
        e.prop('selectionStart',s).prop('selectionEnd',s);
        e.trigger('input');
      }
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#join').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }).fail(function(r){ alert(r.responseText); }); location.reload(true); } });
      $('#link').click(function(){ var pin = prompt('Enter PIN from account profile'); if(pin!==null) { $.ajax({ type: "GET", url: '/uuid?pin='+pin, async: false }); location.reload(true); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('click','.reply', function(){ $('#replying').attr('data-id',$(this).closest('.message').data('id')).slideDown('fast').children('span').text($(this).closest('.message').data('name')); $('#chattext').focus(); });
      $('#chat-wrapper').on('click','.flag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'flagchatid='+$(this).closest('.message').data('id')).done(function(r){ chatChangeId = +r; updateChat(); }); });
      $('#chat-wrapper').on('click','.unflag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unflagchatid='+$(this).closest('.message').data('id')).done(function(r){ chatChangeId = +r; updateChat(); }); });
      $('#chat-wrapper').on('click','.star', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'starchatid='+$(this).closest('.message').data('id')).done(function(r){ chatChangeId = +r; updateChat(); }); });
      $('#chat-wrapper').on('click','.unstar', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unstarchatid='+$(this).closest('.message').data('id')).done(function(r){ chatChangeId = +r; updateChat(); }); });
      $('#chat-wrapper').on('click','.active-user:not(.me)', function(){ if(!$(this).hasClass('ping')){ textareaInsertTextAtCursor($('#chattext'),'@'+$(this).data('name')); } $(this).toggleClass('ping'); $('#chattext').focus(); });
      $('#chat-wrapper').on('click','.dismiss', function(){
        $.post('/community', { id: $(this).closest('.message').attr('data-id'), action: 'dismiss' }).done(function(){ updateChat(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#replying>button').click(function(){ $('#replying').attr('data-id','').slideUp('fast'); });
      $('.tag').click(function(){ $(this).prev('div').css('visibility','visible'); });
      $(document).click(function(e){ if((!$(e.target).closest('.newtag').find('div').length) && (!$(e.target).closest('.dropdown').length)){ $('.tag').prev('div').css('visibility','hidden'); } });
      $('select.tags').select2();
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      $('#community').change(function(){ window.location = '/'+$(this).val().toLowerCase(); });
      $('select.tags').change(function(){ if($(this).val()!=='0'){ $.post(window.location.href, { questionid: $(this).data('question-id'), tagid: $(this).val(), action: 'new-tag' }).done(function(){ window.location.reload(); }); } });
      $('.tag i').click(function(){ $.post(window.location.href, { questionid: $(this).parent().data('question-id'), tagid: $(this).parent().data('tag-id'), action: 'remove-tag' }).done(function(){ window.location.reload(); }); });
      $('#room').change(function(){ window.location = '/<?=$community?>?room='+$(this).val(); });
      $('#chattext').on('input', function(){
        $(this).css('height', '0');
        $(this).css('height',this.scrollHeight+'px');
        if($(this).val().trim()){ $('#preview .markdown').html(md.render($('#chattext').val())); $('#preview').slideDown('fast'); } else { $('#preview').slideUp('fast'); }
      });
      $('#chattext').keydown(function(e){
        var t = $(this);
        $('#messages').animate({ scrollTop: $('#messages').prop("scrollHeight") }, 'fast');
        if((e.keyCode || e.which) == 13) {
          if(!e.shiftKey) {
            if(t.val().trim()){
              arr = [];
              $('.ping').each(function(){ arr.push($(this).data('id')); });
              $.post('/community', { room: <?=$room?>, msg: t.val(), replyid: $('#replying').attr('data-id'), pings: arr, action: 'new-chat' }).done(function(){
                updateChat();
                t.val('').prop('disabled',false).focus().css('height', 'auto');
                $('#preview').slideUp('fast');
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
      document.addEventListener('visibilitychange', function(){ if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
      const myResizer = new Resizer('body', { callback: function(w) { $.get(window.location.href, { resizer: Math.round(w) }); } });
      setTimeout(pollChat, chatPollInterval);
      initChat();
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
        $.ajax({ url: "/upload", type: "POST", data: d, processData: false, cache: false, contentType: false }).done(function(r){ $('#chattext').prop('disabled',false).focus(); textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')'); });
        return false;
      });
      $('#qa .when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
      $('#qa .markdown a').attr({ 'rel':'nofollow', 'target':'_blank' });
    });
  </script>
  <title><?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex;">
  <main style="display: flex; flex-direction: column; flex: 1 1 <?=($uuid)?ccdb("select login_resizer_percent from login"):'50'?>%; overflow: hidden;">
    <header style="border-bottom: 2px solid black; display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto;">
      <div style="margin: 0.5em; margin-right: 0.1em;">
        <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers</a>
        <select id="community">
          <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
            <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
          <?}?>
        </select>
      </div>
      <div style="display: flex; height: 100%; align-items: center;">
        <?if(!$uuid){?><input id="join" type="button" value="join" style="margin: 0.5em;"> or <input id="link" type="button" value="link" style="margin: 0.5em;"><?}?>
        <?if($uuid){?><form method="GET" action="/question"><input type="hidden" name="community" value="<?=$community?>"><input id="ask" type="submit" value="ask question" style="margin: 0.5em;"></form><?}?>
        <?if($uuid){?><a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2em; display: block; height: 2.4em;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a><?}?>
      </div>
    </header>
    <div id="qa" style="background-color: white; overflow: auto; padding: 0.5em;">
      <?if($question){?>
        <?extract(cdb("select question_title,question_markdown,account_name,account_is_me
                            , case question_type when 'question' then '' when 'meta' then 'Meta Question: ' when 'blog' then 'Blog Post: ' end question_type
                            , question_type='blog' question_is_blog
                            , extract('epoch' from current_timestamp-question_at) question_when
                       from question natural join account
                       where question_id=$1",$question));?>
        <div id="question" style="border: 1px solid #<?=$colour_dark?>; border-radius: 0.2em; font-size: larger; box-shadow: 0.1em 0.1em 0.2em #<?=$colour_mid?>;">
          <div style="font-size: larger; text-shadow: 0.1em 0.1em 0.1em lightgrey; padding: 0.6rem; border-bottom: 1px solid #<?=$colour_dark?>;"><?=$question_type.htmlspecialchars($question_title)?></div>
          <div class="bar" style="border-bottom: 1px solid #<?=$colour_dark?>;">
            <?=htmlspecialchars($account_name)?>,
            <span class="when" data-seconds="<?=$question_when?>"></span>
            <?if($uuid && (($account_is_me==='t')||($question_is_blog==='f'))){?><a href="/question?id=<?=$question?>">edit</a><?}?>
            <div style="margin-top: 0.4rem; display: flex; flex-wrap: wrap;">
              <?foreach(db("select tag_id,tag_name from question_tag_x_not_implied natural join tag where question_id=$1",$question) as $r){ extract($r);?>
                <span class="tag" data-question-id="<?=$question?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
              <?}?>
              <?if($uuid){?>
                <span class="newtag" style="margin-right: 0.2rem; margin-bottom: 0.1rem;">
                  <div style="position: absolute; top: -2px; left: -2px; z-index: 1; visibility: hidden;">
                    <select class="tags" data-question-id="<?=$question?>">
                      <option value="0" disabled selected><?=(ccdb("select exists (select tag_id,tag_name from tag natural join community where community_name=$1)",$community))?'select tag':''?></option>
                      <?foreach(db("select tag_id,tag_name from tag natural join community where community_name=$1 and tag_id not in (select tag_id from question_tag_x where question_id=$2)",$community,$question) as $r){ extract($r);?>
                        <option value="<?=$tag_id?>"><?=$tag_name?></option>
                      <?}?>
                    </select>
                  </div>
                  <span class="tag">&#65291;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </span>
              <?}?>
            </div>
          </div>
          <div id="markdown" class="markdown" data-markdown="<?=htmlspecialchars($question_markdown)?>"></div>
        </div>
        <?if($uuid && ($question_is_blog==='f')){?><form method="GET" action="/answer"><input type="hidden" name="question" value="<?=$question?>"><input id="answer" type="submit" value="answer this question" style="margin: 2em auto; display: block;"></form><?}?>
        <?foreach(db("select answer_id, answer_markdown, extract('epoch' from current_timestamp-answer_at) answer_when from answer where question_id=$1",$question) as $r){ extract($r);?>
          <div class="answer">
            <div class="markdown" data-markdown="<?=htmlspecialchars($answer_markdown)?>"></div>
            <div class="bar">
              <?=htmlspecialchars($account_name)?>,
              <span class="when" data-seconds="<?=$answer_when?>"></span>
              <a href="/answer?id=<?=$answer_id?>">edit</a>
            </div>
          </div>
        <?}?>
      <?}else{?>
        <?if(ccdb("select count(*) from question natural join community where community_name=$1",$community)==="0"){?>
          <?for($x = 1; $x<10; $x++){?>
            <div class="question">Question <?=$x?></div>
          <?}?>
        <?}else{?>
          <?foreach(db("select question_id,question_at,question_title
                             , case question_type when 'question' then '' when 'meta' then 'Meta Question: ' when 'blog' then 'Blog Post: ' end question_type
                        from question natural join community
                        where community_name=$1",$community) as $r){ extract($r);?>
            <a href="/<?=$community?>?q=<?=$question_id?>" class="question"<?=$question_type?(' style="background-color: #'.$colour_light.';"'):''?>>
              <div><?=$question_type.$question_title?></div>
              <div>
                <?foreach(db("select tag_id,tag_name from question_tag_x_not_implied natural join tag where question_id=$1",$question_id) as $r){ extract($r);?>
                  <span class="tag" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
                <?}?>
              </div>
            </a>
          <?}?>
        <?}?>
      <?}?>
    </div>
  </main>
  <div id="chat-wrapper" style="background-color: #<?=$colour_mid?>; flex: 1 1 <?=($uuid)?ccdb("select 100-login_resizer_percent from login"):'50'?>%; display: flex; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; overflow: hidden;">
    <header style="flex: 0 0 auto; border-top: 2px solid black; padding: 0.5em;">
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
      <?if($uuid) if(intval(ccdb("select account_id from login"))<3){?><input id="poll" type="button" value="poll"><?}?>
    </header>
    <?if($canchat){?>
      <div id="chattext-wrapper" style="position: relative; display: flex;">
        <form action="/upload" method="post" enctype="multipart/form-data"><input id="chatuploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
        <button id="chatupload" class="button" style="position: absolute; right: 0.15em; top: 0; bottom: 0; font-size: 1.5em; color: #<?=$colour_dark?>;" title="embed image"><i class="fa fa-picture-o" style="display: block;"></i></button>
        <textarea id="chattext" style="flex: 0 0 auto; width: 100%; resize: none; outline: none; border: none; padding: 0.3em; margin: 0; font-family: inherit; font-size: inherit;" rows="1" placeholder="type message here" maxlength="5000" autofocus></textarea>
      </div>
      <div id="replying" style="flex: 0 0 auto; width: 100%; padding: 0.1em 0.3em; border-bottom: 1px solid darkgrey; font-style: italic; font-size: smaller; display: none;" data-id="">
        Replying to: 
        <span></span>
        <button id="cancelreply" class="button" style="float: right;">&#x2573;</button>
      </div>
      <div id="preview" class="message" style="flex: 0 0 auto; width: 100%; border-bottom: 1px solid darkgrey; padding: 0.2em; display: none;">
        <div class="markdown-wrapper">
          <div class="markdown" data-markdown="">
          </div>
        </div>
      </div>
    <?}?>
    <div id="chat" style="display: flex; flex: 1 0 0; min-height: 0; border-bottom: 1px solid darkgrey;">
      <div id="messages" style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column-reverse; padding: 0.5em; overflow: auto;">
        <?foreach(db("select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and chat_gap<60 chat_account_is_repeat
                      from (select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,chat_at
                                 , coalesce(nullif(account_name,''),'Anonymous') account_name
                                 , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                                 , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                                 , round(extract('epoch' from chat_at-coalesce(lag(chat_at) over (order by chat_at), current_timestamp))) chat_gap
                                 , chat_flag_at is not null is_flagged
                                 , chat_star_at is not null is_starred
                                 , (lead(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lead(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
                            from chat c natural join account natural left join chat_flag natural left join chat_star
                            where room_id=$1".($uuid?"":" and chat_flag_count=0").") z
                      order by chat_at desc limit 100",$room) as $r){ extract($r);?>
          <div class="message<?=($account_is_me==='t')?' mine':''?><?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<span style="color: #'.$colour_dark.';">&nbsp;replying to&nbsp;</span>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</small>
            <img class="identicon" src="/identicon.php?id=<?=$account_id?>">
            <div class="markdown-wrapper">
              <?if($canchat){?><button class="button reply" title="reply"><i class="fa fa-reply fa-rotate-180"></i></button><?}?>
              <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
            </div>
            <?if($canchat){?>
              <span class="buttons">
                <?if($account_is_me==='f'){?>
                  <button class="button <?=($is_starred==='t')?'unstar':'star'?><?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star<?=($is_starred==='t')?'':'-o'?>"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button class="button <?=($is_flagged==='t')?'unflag':'flag'?><?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag<?=($is_flagged==='t')?'':'-o'?>"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}else{?>
                  <button class="button<?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button class="button<?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}?>
              </span>
            <?}?>
          </div>
          <?if($chat_account_is_repeat==='f'){?><div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div><?}?>
        <?}?>
      </div>
      <?if($uuid){?>
        <div id="active-users" style="flex: 0 0 auto; display: flex; flex-direction: column-reverse; align-items: flex-start; background-color: #<?=$colour_light?>; border-left: 1px solid darkgrey; padding: 0.1em; overflow-y: hidden;">
          <?foreach(db("select account_id,account_name,account_is_me from room_account_x natural join account where room_id=$1 order by room_account_x_latest_chat_at desc",$room) as $r){ extract($r);?>
            <img title="<?=$account_name?>" class="active-user<?=($account_is_me==='t')?' me':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" src="/identicon.php?id=<?=$account_id?>">
          <?}?>
        </div>
      <?}?>
    </div>
    <div id="notification-wrapper">
      <?if($uuid&&(ccdb("select count(*)>0 from chat_notification")==='t')){?>
        <div id="notifications" style="display: flex; flex-direction: column; flex: 0 1 auto; min-height: 0; max-height: 30vh; border-bottom: 1px solid darkgrey; background-color: #<?=$colour_light?>; padding: 0.3em; overflow-x: hidden; overflow-y: scroll;">
          <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,room_id,room_name,community_name
                             , coalesce(nullif(account_name,''),'Anonymous') account_name
                             , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                             , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                             , round(extract('epoch' from current_timestamp-chat_at)) chat_ago
                             , chat_flag_at is not null is_flagged
                             , chat_star_at is not null is_starred
                             , encode(community_mid_shade,'hex') chat_mid_shade
                             , encode(community_dark_shade,'hex') chat_dark_shade
                        from chat_notification natural join chat c natural join room natural join community natural join account natural left join chat_flag natural left join chat_star
                        order by chat_at limit 100") as $r){ extract($r);?>
            <div class="message" style="background-color: #<?=$chat_mid_shade?>;" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
              <small class="who">
                <?=($account_is_me==='t')?'<em>Me</em>':$account_name?>
                <?=$chat_reply_id?'<span style="color: #'.$chat_dark_shade.';">replying to</span> '.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>
                <?if($room_id!==$room){?><span style="color: #<?=$chat_dark_shade?>;">in&nbsp;</span><a href="/<?=$community_name?>?room=<?=$room_id?>" style="color: #<?=$chat_dark_shade?>;"><?=$room_name?></a><?}?>
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
                <button class="button <?=($is_starred==='t')?'unstar':'star'?><?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star<?=($is_starred==='t')?'':'-o'?>"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                <button class="button <?=($is_flagged==='t')?'unflag':'flag'?><?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag<?=($is_flagged==='t')?'':'-o'?>"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
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
