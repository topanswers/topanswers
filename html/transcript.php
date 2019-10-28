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
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    .button { background: none; border: none; padding: 0; cursor: pointer; }
    .message { flex: 0 0 auto; max-width: calc(100% - 1.7em); max-height: 8em; overflow: auto; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; }
    .message-wrapper { width: 100%; margin-top: 0.2em; position: relative; display: flex; flex: 0 0 auto; }
    .message-wrapper>small { font-size: 0.6em; position: absolute; top: -1.2em; width: 100%; display: flex; align-items: baseline; }
    .message-wrapper>small>span.flags { margin-left: 1em; }
    .message-wrapper>small>span.stars { margin-left: 1em; }
    .message-wrapper>small>span.buttons { margin-left: 1em; }
    .message-wrapper>small>span.buttons>button { margin-left: 0.2em; color: #<?=$colour_dark?>; }
    .message-wrapper .buttons button,.message-wrapper .reply { display: block; white-space: nowrap; color: #<?=$colour_dark?>; }
    .message-wrapper .buttons button+button { margin-top: -0.3em; }
    .message-wrapper>img { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; }
    .message-wrapper .dark { color: #<?=$colour_dark?>; }
    .thread>div { background-color: #<?=$colour_highlight?>40; }
    .highlight>div { border: 0.3em solid #<?=$colour_highlight?>60; }
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
    body>div>div { margin: 0.5em; white-space: nowrap; }
    body>div>div>span { font-size: smaller; font-style: italic; }
    a:not([href]) { color: #<?=$colour_highlight?>; }
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
      $('main').on('mouseenter', '.message-wrapper', function(){ $('.message-wrapper.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message-wrapper', function(){ $('.thread').removeClass('thread'); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).attr('data-markdown'))); });
      threadChat();
      $('.bigspacer').each(function(){ $(this).children().text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
      $('.highlight')[0].scrollIntoView();
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
    <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count
                       , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at
                       , coalesce(nullif(account_name,''),'Anonymous') account_name
                       , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                       , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                       , round(extract('epoch' from (lead(chat_at) over (order by chat_at))-chat_at)) chat_gap
                       , chat_flag_at is not null is_flagged
                       , chat_star_at is not null is_starred
                  from chat c natural join account natural left join chat_flag natural left join chat_star
                  where room_id=$1 and chat_at>=make_timestamp($2,$3,$4,$5,0,0) and chat_at<make_timestamp($6,$7,$8,$9,0,0)+'1h'::interval
                  order by chat_at",$room,$_GET['year']??1,$_GET['month']??1,$_GET['day']??1,$_GET['hour']??0,$_GET['year']??9999,$_GET['month']??12,$_GET['day']??$maxday,$_GET['hour']??23) as $r){ extract($r);?>
      <div id="c<?=$chat_id?>" class="message-wrapper<?=($chat_id===($_GET['id']??''))?' highlight':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
        <small>
          <span><?=$chat_at?>&nbsp;</span>
          <span class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<span class="dark">&nbsp;replying to&nbsp;</span>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</span>
        </small>
        <img src="/identicon.php?id=<?=$account_id?>">
        <div class="message markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
        <span class="buttons">
          <?if($chat_star_count>0){?><button title="star" class="button"><i class="fa fa-fw fa-star"></i><?=$chat_star_count?></button><?}?>
          <?if($chat_flag_count>0){?><button title="flag" class="button"><i class="fa fa-fw fa-flag"></i><?=$chat_flag_count?></button><?}?>
        </span>
      </div>
      <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="height: <?=round(log(1+$chat_gap)/4)?>em;" data-gap="<?=$chat_gap?>"><span></span></div>
    <?}?>
  </main>
</body>   
</html>   
