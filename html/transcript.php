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
if(!isset($_GET['room'])) die('Room not set');
$room = $_GET['room'];
$max = 500;
ccdb("select count(*) from room where room_id=$1",$room)==='1' or die('invalid room');
if(!isset($_GET['year'])){
  if(ccdb("select sum(chat_year_count) from chat_year where room_id=$1",$room)>=$max){
    header("Location: ".$_SERVER['REQUEST_URI'].'&year='.ccdb("select max(chat_year) from chat_year where room_id=$1",$room));
    exit;
  }
}else{
  if(!isset($_GET['month'])){
    if(ccdb("select chat_year_count from chat_year where room_id=$1 and chat_year=$2",$room,$_GET['year'])>=$max){
      header("Location: ".$_SERVER['REQUEST_URI'].'&month='.ccdb("select max(chat_month) from chat_month where room_id=$1 and chat_year=$2",$room,$_GET['year']));
      exit;
    }
  }else{
    if(!isset($_GET['day'])){
      if(ccdb("select chat_month_count from chat_month where room_id=$1 and chat_year=$2 and chat_month=$3",$room,$_GET['year'],$_GET['month'])>=$max){
        header("Location: ".$_SERVER['REQUEST_URI'].'&day='.ccdb("select max(chat_day) from chat_day where room_id=$1 and chat_year=$2 and chat_month=$3",$room,$_GET['year'],$_GET['month']));
        exit;
      }
    }else{
      if(!isset($_GET['hour'])){
        if(ccdb("select chat_day_count from chat_day where room_id=$1 and chat_year=$2 and chat_month=$3 and chat_day=$4",$room,$_GET['year'],$_GET['month'],$_GET['day'])>=$max){
          header("Location: ".$_SERVER['REQUEST_URI'].'&hour='.ccdb("select max(chat_hour) from chat_hour where room_id=$1 and chat_year=$2 and chat_month=$3 and chat_day=$4",$room,$_GET['year'],$_GET['month'],$_GET['day']));
          exit;
        }
      }
    }
  }
}
$maxday = 31;
if(isset($_GET['month'])){
  $maxday = ccdb("select extract('day' from make_timestamp($1,$2,1,0,0,0)+'1mon - 1d'::interval)",$_GET['year'],$_GET['month']);
}
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community natural join room
             where room_id=$1",$room));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    body>div>div { margin: 0.5em; white-space: nowrap; }
    body>div>div>span { font-size: smaller; font-style: italic; }
    a:not([href]) { color: #<?=$colour_highlight?>; }

    .button { background: none; border: none; padding: 0; outline: inherit; margin: 0; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

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

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.6em; position: absolute; top: -1.2em; }
    .message .identicon { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; }
    .message .markdown-wrapper { display: flex; position: relative; flex: 0 1 auto; max-height: 8em; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; overflow: hidden; }
    .message .markdown-wrapper .reply { position: absolute; right: 0; bottom: 0; background-color: #fffd; padding: 0.2em; padding-left: 0.4em; }
    .message .buttons { flex: 0 0 auto; max-height: 1.3em; padding: 0.05em 0; }
    .message .button { display: block; white-space: nowrap; color: #<?=$colour_dark?>; line-height: 0; }
    .message .button:not(.marked) { visibility: hidden; }
    .message.merged { margin-top: -1px; }
    .message.merged .who,
    .message.merged .identicon { visibility: hidden; }
    .message.thread .markdown-wrapper { background: #<?=$colour_highlight?>40; }
    .message:target .markdown-wrapper { box-shadow: 0 0 1px 1px #<?=$colour_highlight?> inset; }
  </style>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ linkify: true, highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }}).use(window.markdownitSup).use(window.markdownitSub);
      function threadChat(){
        $('.message').each(function(){
          var id = $(this).data('id'), rid = id;
          function foo(b){
            if(arguments.length!==0) $(this).addClass('t'+id);
            if(arguments.length===0 || b===true) if($(this).data('reply-id')) foo.call($('.message[data-id='+$(this).data('reply-id')+']')[0], true);
            if(arguments.length===0 || b===false) $('.message[data-reply-id='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
          }
          foo.call(this);
        });
      }
      $('main').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      threadChat();
      $('.message .markdown img').each(function(i){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+i+'"></a>'); });
      $('.message .markdown a').attr('rel','nofollow').attr('target','_blank');
      $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
      setTimeout(function(){ $('.message:target')[0].scrollIntoView(); }, 0);
    });
  </script>
  <title><?=ccdb("select room_name from room where room_id=$1",$room)?> Transcript | TopAnswers</title>
</head>
<body style="display: flex; background-color: #<?=$colour_light?>; padding: 2em;">
  <?if(isset($_GET['year'])){?>
    <div style="flex 0 0 auto;">
      <div>
        <div>year</div>
      </div>
      <?foreach(db("select chat_year,chat_year_count from chat_year where room_id=$1 order by chat_year",$room) as $r){extract($r);?>
        <div>
          <a<?if($chat_year!==$_GET['year']){?>  href="/transcript?room=<?=$room?>&year=<?=$chat_year?>"<?}?>><?=$chat_year?></a>
          <span>(<?=$chat_year_count?>)</span>
        </div>
      <?}?>
    </div>
  <?}?>
  <?if(isset($_GET['month'])){?>
    <div style="flex 0 0 auto;">
      <div>
        <div>month</div>
      </div>
      <?foreach(db("select chat_month,chat_month_count from chat_month where room_id=$1 and chat_year=$2 order by chat_month",$room,$_GET['year']) as $r){extract($r);?>
        <div>
          <a<?if($chat_month!==$_GET['month']){?>  href="/transcript?room=<?=$room?>&year=<?=$_GET['year']?>&month=<?=$chat_month?>"<?}?>><?=$chat_month?></a>
          <span>(<?=$chat_month_count?>)</span>
        </div>
      <?}?>
    </div>
  <?}?>
  <?if(isset($_GET['day'])){?>
    <div style="flex 0 0 auto;">
      <div>
        <div>day</div>
      </div>
      <?foreach(db("select chat_day,chat_day_count from chat_day where room_id=$1 and chat_year=$2 and chat_month=$3 order by chat_day",$room,$_GET['year'],$_GET['month']) as $r){extract($r);?>
        <div>
          <a<?if($chat_day!==$_GET['day']){?>  href="/transcript?room=<?=$room?>&year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$chat_day?>"<?}?>><?=$chat_day?></a>
          <span>(<?=$chat_day_count?>)</span>
        </div>
      <?}?>
    </div>
  <?}?>
  <?if(isset($_GET['hour'])){?>
    <div style="flex 0 0 auto;">
      <div>
        <div>hour</div>
      </div>
      <?foreach(db("select chat_hour,chat_hour_count from chat_hour where room_id=$1 and chat_year=$2 and chat_month=$3 and chat_day=$4 order by chat_hour",$room,$_GET['year'],$_GET['month'],$_GET['day']) as $r){extract($r);?>
        <div>
          <a<?if($chat_hour!==$_GET['hour']){?> href="/transcript?room=<?=$room?>&year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>&hour=<?=$chat_hour?>"<?}?>><?=$chat_hour?>:00-<?=$chat_hour+1?>:00</a>
          <span>(<?=$chat_hour_count?>)</span>
        </div>
      <?}?>
    </div>
  <?}?>
  <main style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column; padding: 1em; overflow: scroll; background-color: #<?=$colour_mid?>;">
    <?foreach(db("select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and chat_gap<60 chat_account_is_repeat
                  from (select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,chat_at
                             , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at_text
                             , coalesce(nullif(account_name,''),'Anonymous') account_name
                             , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                             , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                             , round(extract('epoch' from chat_at-(lag(chat_at) over (order by chat_at)))) chat_gap
                             , chat_flag_at is not null is_flagged
                             , chat_star_at is not null is_starred
                             , (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lag(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
                        from chat c natural join account natural left join chat_flag natural left join chat_star
                        where room_id=$1 and chat_at>=make_timestamp($2,$3,$4,$5,0,0) and chat_at<make_timestamp($6,$7,$8,$9,0,0)+'1h'::interval".($uuid?"":" and chat_flag_count=0").") z
                  order by chat_at",$room,$_GET['year']??1,$_GET['month']??1,$_GET['day']??1,$_GET['hour']??0,$_GET['year']??9999,$_GET['month']??12,$_GET['day']??$maxday,$_GET['hour']??23) as $r){ extract($r);?>
      <?if($chat_account_is_repeat==='f'){?><div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div><?}?>
      <div id="c<?=$chat_id?>" class="message<?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
        <small class="who">
          <span style="color: #<?=$colour_dark?>;"><?=$chat_at_text?>&nbsp;</span>
          <?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$colour_dark.'; text-decoration: none;">&nbsp;replying to&nbsp;</a>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:
        </small>
        <img class="identicon" src="/identicon.php?id=<?=$account_id?>">
        <div class="markdown-wrapper">
          <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
        </div>
        <span class="buttons">
          <button class="button<?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star"></i><?=($chat_star_count>0)?$chat_star_count:''?></button>
          <button class="button<?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag"></i><?=($chat_flag_count>0)?$chat_flag_count:''?></button>
        </span>
      </div>
    <?}?>
  </main>
</body>   
</html>   
