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
  if(isset($_POST['msg'])){
    db("select new_chat($1,$2,$3,nullif($4,'')::integer,('{'||$5||'}')::integer[])",$uuid,$_POST['room'],$_POST['msg'],$_POST['replyid'],implode(',',$_POST['pings']));
  }else{
    db("select dismiss_notification($1)",$_POST['id']);
  }
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
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }
    #replying[data-id=""] { display: none; }

    .markdown { overflow: auto; }
    .markdown>:first-child { margin-top: 0; }
    .markdown>:last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 1em; }
    .markdown img { max-height: 7em; }
    .markdown table { border-collapse: collapse; }
    .markdown td, .markdown th { white-space: nowrap; border: 1px solid black; }
    .markdown blockquote {  padding-left: 1em;  margin-left: 1em; margin-right: 0; border-left: 2px solid gray; }
    .markdown code { display: inline-block; padding: 0.1em; background: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; }
    .active-user { height: 1.5em; width: 1.5em; margin: 0.1em; }
    .active-user.ping { outline: 1px solid #<?=$colour_highlight?>; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.6em; position: absolute; }
    .message .identicon { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; }
    .message .markdown-wrapper { display: flex; position: relative; flex: 0 1 auto; max-height: 8em; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; overflow: hidden; }
    .message .markdown-wrapper .reply { position: absolute; right: 0; bottom: 0; background-color: #fffd; padding: 0.2em; padding-left: 0.4em; }
    .message .buttons { flex: 0 0 auto; max-height: 1.3em; padding: 0.05em 0; }
    .message .button { display: block; white-space: nowrap; color: #<?=$colour_dark?>; line-height: 0; }
    .message .button:not(.marked) { visibility: hidden; }
    .message:hover .button { visibility: visible; }
    .message.merged { margin-top: -1px; }
    .message.merged .who,
    .message.merged .identicon { visibility: hidden; }
    #chat .message .who { top: -1.2em; }
    #chat .message.thread .markdown-wrapper { background: #<?=$colour_highlight?>40; }
    #notifications .message { padding: 0.3em; padding-top: 1.05em; border-radius: 0.2em; }
    #notifications .message .who { top: 0.5em; }
    #notifications .message+.message { margin-top: 0.2em; }
  </style>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/moment.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
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
      function updateChat(){
        $.get(window.location.href,function(data) {
          arr = [];
          $('.ping').each(function(){ arr.push($(this).data('id')); });
          $('#chat').replaceWith($('<div />').append(data).find('#chat'));
          $('#notification-wrapper').replaceWith($('<div />').append(data).find('#notification-wrapper'));
          $.each(arr, function(k,v){ $('.active-user[data-id='+v+']').addClass('ping'); });
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
        $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
        $('.message .when').each(function(){ $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago'); });
        $('#messages').scrollTop($('#messages')[0].scrollHeight);
      }
      function textareaInsertTextAtCursor(e,t) {
        var v = e.val(), s = e.prop('selectionStart')+t.length;
        e.val(v.substring(0,e.prop('selectionStart'))+t+v.substring(e.prop('selectionEnd'),v.length));
        e.prop('selectionStart',s).prop('selectionEnd',s);
      }
      $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('#join').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }); location.reload(true); } });
      $('#link').click(function(){ var pin = prompt('Enter PIN from account profile'); if(pin!==null) { $.ajax({ type: "GET", url: '/uuid?pin='+pin, async: false }); location.reload(true); } });
      $('#poll').click(function(){ checkChat(); });
      $('#chat-wrapper').on('click','.reply', function(){ $('#replying').attr('data-id',$(this).closest('.message').data('id')).children('span').text($(this).closest('.message').data('name')); $('#chattext').focus(); });
      $('#chat-wrapper').on('click','.flag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'flagchatid='+$(this).closest('.message').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.unflag', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unflagchatid='+$(this).closest('.message').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.star', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'starchatid='+$(this).closest('.message').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.unstar', function(){ var url = window.location.href; $.get(url+((url.indexOf('?')===-1)?'?':'&')+'unstarchatid='+$(this).closest('.message').data('id')).done(updateChat); });
      $('#chat-wrapper').on('click','.active-user:not(.me)', function(){ if(!$(this).hasClass('ping')){ textareaInsertTextAtCursor($('#chattext'),'@'+$(this).data('name')); } $(this).toggleClass('ping'); $('#chattext').focus(); });
      $('#chat-wrapper').on('click','.dismiss', function(){
        $.post('/community', { id: $(this).closest('.message').attr('data-id') }).done(function(){ updateChat(); });
        $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
        return false;
      });
      $('#replying>button').click(function(){ $('#replying').attr('data-id',''); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      $('#community').change(function(){ window.location = '/'+$(this).val().toLowerCase(); });
      $('#room').change(function(){ window.location = '/<?=$community?>?room='+$(this).val(); });
      $('#chattext').on('input', function(){
        $(this).css('height', '0');
        $(this).css('height',this.scrollHeight+'px');
        if($(this).val()){ $('#preview .markdown').html(md.render($('#chattext').val())); $('#preview').show(); } else { $('#preview').hide(); }
      });
      $('#chattext').keydown(function(e){
        var t = $(this);
        if((e.keyCode || e.which) == 13) {
          if(!e.shiftKey) {
            arr = [];
            $('.ping').each(function(){ arr.push($(this).data('id')); });
            $.post('/community', { room: <?=$room?>, msg: $('#chattext').val(), replyid: $('#replying').attr('data-id'), pings: arr }).done(function(){
              updateChat();
              t.val('').prop('disabled',false).focus().css('height', 'auto');
              $('#preview').hide();
            });
            $('#replying').attr('data-id','');
            $('.ping').removeClass('ping');
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
      <div style="margin: 0.5em; margin-right: 0.1em;">
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
      <textarea id="chattext" style="flex: 0 0 auto; width: 100%; resize: none; outline: none; border: none; padding: 0.3em; margin: 0; font-family: inherit; font-size: inherit;" rows="1" placeholder="type message here" maxlength="5000" autofocus></textarea>
      <div id="replying" style="flex: 0 0 auto; width: 100%; padding: 0.1em 0.3em; border-bottom: 1px solid darkgrey; font-style: italic; font-size: smaller;" data-id="">
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
          <div class="message<?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<span style="color: #'.$colour_dark.';">&nbsp;replying to&nbsp;</span>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</small>
            <img class="identicon" src="/identicon.php?id=<?=$account_id?>">
            <div class="markdown-wrapper">
              <button class="button reply" title="reply"><i class="fa fa-reply fa-rotate-180"></i></button>
              <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
            </div>
            <?if($uuid){?>
              <span class="buttons">
                <?if($account_is_me==='f'){?>
                  <button class="button <?=($is_starred==='t')?'unstar':'star'?><?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star<?=($is_starred==='t')?'':'-o'?>"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button class="button <?=($is_flagged==='t')?'unflag':'flag'?><?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag<?=($is_flagged==='t')?'':'-o'?>"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}else{?>
                  <button title="star" class="button<?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button title="flag" class="button<?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}?>
              </span>
            <?}?>
          </div>
          <?if($chat_account_is_repeat==='f'){?><div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div><?}?>
        <?}?>
      </div>
      <div id="active-users" style="flex: 0 0 auto; display: flex; flex-direction: column-reverse; align-items: flex-start; background-color: #<?=$colour_light?>; border-left: 1px solid darkgrey; padding: 0.1em; overflow-y: hidden;">
        <?foreach(db("select account_id,account_name,account_is_me from room_account_x natural join account where room_id=$1 order by room_account_x_latest_chat_at desc",$room) as $r){ extract($r);?>
          <img class="active-user<?=($account_is_me==='t')?' me':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" src="/identicon.php?id=<?=$account_id?>">
        <?}?>
      </div>
    </div>
    <div id="notification-wrapper">
      <?if($uuid&&(ccdb("select count(*)>0 from chat_notification")==='t')){?>
        <div id="notifications" style="display: flex; flex-direction: column; flex: 0 1 auto; min-height: 0; max-height: 20vh; border-bottom: 1px solid darkgrey; background-color: #<?=$colour_light?>; padding: 0.3em; overflow: scroll;">
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
                <?if($room_id===$room){?><button class="button reply" title="reply"><i class="fa fa-reply fa-rotate-180"></i></button><?}?>
                <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
              </div>
              <span class="buttons">
                <?if($account_is_me==='f'){?>
                  <button class="button <?=($is_starred==='t')?'unstar':'star'?><?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star<?=($is_starred==='t')?'':'-o'?>"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button class="button <?=($is_flagged==='t')?'unflag':'flag'?><?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag<?=($is_flagged==='t')?'':'-o'?>"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}else{?>
                  <button title="star" class="button<?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
                  <button title="flag" class="button<?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
                <?}?>
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
