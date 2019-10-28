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
if($uuid) ccdb("select set_config('custom.uuid',$1,false)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
  db("select new_chat($1,$2,$3,nullif($4,'')::integer)",$uuid,$_POST['room'],$_POST['msg'],$_POST['replyid']);
  exit;
}
if(isset($_GET['flagchatid'])){
  db("select set_chat_flag($1)",$_GET['flagchatid']);
  exit;
}
if(isset($_GET['unflagchatid'])){
  db("select remove_chat_flag($1)",$_GET['unflagchatid']);
  exit;
}
if(isset($_GET['starchatid'])){
  db("select set_chat_star($1)",$_GET['starchatid']);
  exit;
}
if(isset($_GET['unstarchatid'])){
  db("select remove_chat_star($1)",$_GET['unstarchatid']);
  exit;
}
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
$room = $_GET['room'] ?? ccdb("select community_room_id from community where community_name=$1",$community);
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; }
    .button { background: none; border: none; padding: 0; cursor: pointer; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid darkgrey; }
    .message { display: flex; }
    .message .markdown { flex: 0 0 auto; max-width: calc(100% - 1.7em); max-height: 8em; overflow: auto; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; }
    .message-wrapper { width: 100%; xmargin-top: 0.2em; position: relative; flex: 0 0 auto; }
    #notifications .message-wrapper { padding: 0.2em; border-radius: 0.2em; }
    .message-wrapper>small { font-size: 0.6em; width: 100%; display: flex; align-items: baseline; }
    #chat .message-wrapper>small { position: absolute; top: -1.5em; }
    .message-wrapper>small>span.flags { margin-left: 1em; }
    .message-wrapper>small>span.stars { margin-left: 1em; }
    .message-wrapper>small>span.buttons { margin-left: 1em; visibility: hidden; }
    .message-wrapper:hover>small>span.buttons { visibility: visible; }
    .message-wrapper>small>span.buttons>button { margin-left: 0.2em; color: #<?=$colour_dark?>; }
    .message-wrapper>.message>img { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; }
    .message-wrapper .dark { color: #<?=$colour_dark?>; }
    .thread>div { box-shadow: 0 0 0.1em 0.1em #<?=$colour_highlight?>; }
    .spacer { flex: 0 0 auto; display: flex; justify-content: center; align-items: center; min-height: 0.6em; width: 100%; }
    .bigspacer { background-image: url("data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk8AEAAFIATgDK/mEAAAAASUVORK5CYII="); background-position: 50% 0%;  background-repeat: repeat-y; }
    .spacer>span { font-size: smaller; font-style: italic; color: #<?=$colour_dark?>; background-color: #<?=$colour_mid?>; padding: 0.2em; }
    .markdown>:first-child { margin-top: 0; }
    .markdown>:last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 1em; }
    .markdown img { max-height: 7em; }
    .markdown table { border-collapse: collapse; }
    .markdown td, .markdown th { border: 1px solid black; }
    .markdown blockquote {  padding-left: 1em;  margin-left: 1em; margin-right: 0; border-left: 2px solid gray; }
    .active-user { height: 1.5em; width: 1.5em; margin: 0.1em; }
    #replying[data-id=""] { display: none; }
  </style>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/moment.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }}).use(window.markdownitSup).use(window.markdownitSub);
      var chatChangeId = <?=ccdb("select coalesce(max(chat_change_id),0) from chat where room_id=$1",$room)?>;
      var chatLastChange = <?=ccdb("select extract(epoch from current_timestamp-coalesce(max(chat_change_at),current_timestamp))::integer from chat where room_id=$1",$room)?>;
      var chatPollInterval;
      function setChatPollInterval(){
        if(chatLastChange<5) chatPollInterval = 1000;
        else if(chatLastChange<15) chatPollInterval = 3000;
        else if(chatLastChange<30) chatPollInterval = 5000;
        else if(chatLastChange<300) chatPollInterval = 10000;
        else if(chatLastChange<3600) chatPollInterval = 30000;
        else chatPollInterval = 60000;
      }
      function threadChat(){
        $('.message-wrapper').each(function(){
          var id = $(this).data('id'), rid = id;
          function foo(b){
            $(this).addClass('t'+id);
            if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message-wrapper[data-id='+$(this).data('reply-id')+']')[0], true);
            if(arguments.length===0 || b===false) $('.message-wrapper[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
          }
          foo.call(this);
        });
      }
      function updateChat(){
        $.get(window.location.href,function(data) {
          $('#chat').html($('<div />').append(data).find('#chat').children());
          $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
          chatLastChange = 0;
          initChat();
        },'html');
      }
      function checkChat(){
        $.get('/change?room=<?=$room?>',function(r){
          if(chatChangeId!==JSON.parse(r).chat_change_id){
            chatChangeId = JSON.parse(r).chat_change_id;
            updateChat();
          }else{
            chatLastChange += Math.floor(chatPollInterval/1000);
            setChatPollInterval();
          }
        });
      }
      function pollChat() { checkChat(); setTimeout(pollChat, chatPollInterval); }
      function initChat() { setChatPollInterval(); threadChat(); $('.bigspacer').each(function(){ $(this).children().text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); }); }
      $('#chat-wrapper').on('mouseenter', '.message-wrapper', function(){ $('.message-wrapper.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message-wrapper', function(){ $('.thread').removeClass('thread'); });
      $('#join').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }); location.reload(true); } });
      $('#link').click(function(){ var pin = prompt('Enter PIN from account profile'); if(pin!==null) { $.ajax({ type: "GET", url: '/uuid?pin='+pin, async: false }); location.reload(true); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('click','.reply', function(){ $('#replying').attr('data-id',$(this).closest('.message-wrapper').data('id')).children('span').text($(this).closest('.message-wrapper').data('name')); $('#chattext').focus(); });
      $('#chat-wrapper').on('click','.flag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'flagchatid='+$(this).closest('.message-wrapper').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.unflag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unflagchatid='+$(this).closest('.message-wrapper').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.star', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'starchatid='+$(this).closest('.message-wrapper').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.unstar', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unstarchatid='+$(this).closest('.message-wrapper').data('id')).done(updateChat); });
      $('#replying>button').click(function(){ $('#replying').attr('data-id',''); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      $('#community').change(function(){ window.location = '/'+$(this).val().toLowerCase(); });
      $('#room').change(function(){ window.location = '/<?=$community?>?room='+$(this).val(); });
      $('#chattext').on('input', function(){ $(this).css('height', '0'); $(this).css('height',this.scrollHeight+'px'); });
      $('#chattext').keydown(function(e){
        var t = $(this);
        if((e.keyCode || e.which) == 13) {
          if(!e.shiftKey) {
            $.post('/community', { room: <?=$room?>, msg: $('#chattext').val(), replyid: $('#replying').attr('data-id') }).done(function(){ updateChat(); t.val('').prop('disabled',false).focus(); });
            $('#replying').attr('data-id','');
            $(this).prop('disabled',true);
            return false;
          }
        }
      });
      setTimeout(pollChat, chatPollInterval);
      initChat();
    });
  </script>
  <title><?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex;">
  <main style="display: flex; flex-direction: column; flex: 0 0 60%;">
    <header style="border-bottom: 2px solid black; display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto;">
      <div style="margin: 0.5em;">
        <span style="color: #<?=$colour_mid?>;">TopAnswers </span>
        <select id="community">
          <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
            <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
          <?}?>
        </select>
      </div>
      <div style="height: 100%">
        <?if(!$uuid){?><input id="join" type="button" value="join" style="margin: 0.5em;"> or <input id="link" type="button" value="link" style="margin: 0.5em;"><?}?>
        <?if($uuid){?><a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2em; display: block; height: 2.4em;" src="/identicon.php?id=<?=ccdb("select account_id from login where login_is_me")?>"></a><?}?>
      </div>
    </header>
    <div id="qa" style="background-color: white; overflow: auto; padding: 0.5em;">
      <?for($x = 1; $x<100; $x++){?>
        <div class="question">Question <?=$x?></div>
      <?}?>
    </div>
  </main>
  <div id="chat-wrapper" style="background-color: #<?=$colour_mid?>; flex: 0 0 40%; display: flex; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; auto; border-left: 2px solid black;">
    <header style="flex: 0 0 auto; border-top: 2px solid black; padding: 0.5em;">
      <select id="room">
        <?foreach(db("select room_id, coalesce(room_name,initcap(community_name)||' Chat') room_name from room natural join community where community_name=$1 order by room_name desc",$community) as $r){ extract($r);?>
          <option<?=($room_id===$room)?' selected':''?> value="<?=$room_id?>"><?=$room_name?></option>
        <?}?>
      </select>
      <a href="/transcript?room=<?=$room?>" style="color: #<?=$colour_mid?>;">transcript</a>
      <?if($uuid) if(intval(ccdb("select account_id from login where login_is_me"))<3){?><input id="poll" type="button" value="poll"><?}?>
    </header>
    <?if($uuid){?>
      <textarea id="chattext" style="flex: 0 0 auto; width: 100%; resize: none; outline: none; border: none; padding: 0.3em; margin: 0; font-family: inherit; font-size: inherit;" rows="1" placeholder="type message here" autofocus></textarea>
      <div id="replying" style="flex: 0 0 auto; width: 100%; padding: 0.1em 0.3em; border-bottom: 1px solid darkgrey; font-style: italic; font-size: smaller;" data-id="">
        Replying to: 
        <span></span>
        <button id="cancelreply" class="button" style="float: right;">&#x2573;</button>
      </div>
    <?}?>
    <div id="chat" style="display: flex; flex: 1 0 0; min-height: 0; border-bottom: 1px solid darkgrey;">
      <div style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column-reverse; padding: 0.5em; overflow: scroll;">
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count
                           , coalesce(nullif(account_name,''),'Anonymous') account_name
                           , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                           , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                           , round(extract('epoch' from coalesce(lead(chat_at) over (order by chat_at), current_timestamp)-chat_at)) chat_gap
                           , chat_flag_at is not null is_flagged
                           , chat_star_at is not null is_starred
                      from chat c natural join account natural left join chat_flag natural left join chat_star
                      where room_id=$1
                      order by chat_at desc limit 100",$room) as $r){ extract($r);?>
          <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"><span></span></div>
          <div class="message-wrapper" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small>
              <span class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<span class="dark">&nbsp;replying to&nbsp;</span>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</span>
              <?if($chat_flag_count>0){?><span class="flags"><?=($chat_flag_count>20)?str_repeat('&#x2691;',20).'+'.($chat_flag_count-20):str_repeat('&#x2691;',$chat_flag_count)?></span><?}?>
              <?if($chat_star_count>0){?><span class="stars"><?=($chat_star_count>20)?str_repeat('&#x2605;',20).'+'.($chat_star_count-20):str_repeat('&#x2605;',$chat_star_count)?></span><?}?>
              <?if($uuid){?>
                <span class="buttons">
                  <button class="button reply" title="reply">&#x21b3;</button>
                  <?if($account_is_me==='f'){?>
                    <?if($is_flagged==='f'){?><button class="button flag" title="flag">&#x2690;</button><?}?>
                    <?if($is_flagged==='t'){?><button class="button unflag" title="remove flag">&#x2691;</button><?}?>
                    <?if($is_starred==='f'){?><button class="button star" title="star">&#x2606;</button><?}?>
                    <?if($is_starred==='t'){?><button class="button unstar" title="remove star">&#x2605;</button><?}?>
                  <?}?>
                </span>
              <?}?>
            </small>
            <div class="message">
              <img src="/identicon.php?id=<?=$account_id?>">
              <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
            </div>
          </div>
        <?}?>
      </div>
      <div id="active-users" style="flex: 0 0 auto; display: flex; flex-direction: column-reverse; align-items: flex-start; background-color: #<?=$colour_light?>; border-left: 1px solid darkgrey; padding: 0.1em; overflow-y: hidden;">
        <?foreach(db("select account_id from chat where room_id=$1 group by account_id having max(chat_at)>(current_timestamp-'7d'::interval) order by max(chat_at) desc",$room) as $r){ extract($r);?>
          <img class="active-user" src="/identicon.php?id=<?=$account_id?>">
        <?}?>
      </div>
    </div>
    <div style="position: relative;"><div style="position: absolute; height: 1em; xbottom: -1em; width: 100%; background: linear-gradient(darkgrey,#<?=$colour_mid?>00);"></div></div>
    <?if($uuid){?>
      <div id="notifications" style="display: flex; flex: 0 1 auto; min-height: 0; max-height: 20vh; border-bottom: 1px solid darkgrey; background-color: #<?=$colour_light?>; padding: 0.5em; overflow: scroll;">
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count
                           , coalesce(nullif(account_name,''),'Anonymous') account_name
                           , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                           , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                           , round(extract('epoch' from coalesce(lead(chat_at) over (order by chat_at), current_timestamp)-chat_at)) chat_gap
                           , chat_flag_at is not null is_flagged
                           , chat_star_at is not null is_starred
                           , encode(community_mid_shade,'hex') chat_colour
                      from chat_notification natural join chat c natural join room natural join community natural join account natural left join chat_flag natural left join chat_star
                      order by chat_at limit 100") as $r){ extract($r);?>
          <div class="message-wrapper" style="background-color: #<?=$chat_colour?>;" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small>
              <span class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<span class="dark">&nbsp;replying to&nbsp;</span>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</span>
              <?if($chat_flag_count>0){?><span class="flags"><?=($chat_flag_count>20)?str_repeat('&#x2691;',20).'+'.($chat_flag_count-20):str_repeat('&#x2691;',$chat_flag_count)?></span><?}?>
              <?if($chat_star_count>0){?><span class="stars"><?=($chat_star_count>20)?str_repeat('&#x2605;',20).'+'.($chat_star_count-20):str_repeat('&#x2605;',$chat_star_count)?></span><?}?>
              <span class="buttons">
                <button class="button reply" title="reply">&#x21b3;</button>
                <?if($account_is_me==='f'){?>
                  <?if($is_flagged==='f'){?><button class="button flag" title="flag">&#x2690;</button><?}?>
                  <?if($is_flagged==='t'){?><button class="button unflag" title="remove flag">&#x2691;</button><?}?>
                  <?if($is_starred==='f'){?><button class="button star" title="star">&#x2606;</button><?}?>
                  <?if($is_starred==='t'){?><button class="button unstar" title="remove star">&#x2605;</button><?}?>
                <?}?>
              </span>
            </small>
            <div class="message">
              <img src="/identicon.php?id=<?=$account_id?>">
              <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
            </div>
          </div>
        <?}?>
      </div>
    <?}?>
  </div>
</body>   
</html>   
