<?    
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
if(!isset($_GET['room'])) die('Room not set');
db("set search_path to transcript,pg_temp");
$authenticated = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select account_id,community_name,room_id,room_derived_name,room_can_chat,room_question_id,community_code_language,my_community_regular_font_name,my_community_monospace_font_name
                   ,colour_dark,colour_mid,colour_light,colour_highlight
             from one"));
$max = 500;
$search = $_GET['search']??'';
$id = $_GET['id']??0;
if($id){ ccdb("select count(*) from chat where chat_id=$1",$id)===1 or die('invalid id'); }
if(!$search){
  if(!isset($_GET['year'])){
    if(ccdb("select sum(chat_year_count) from chat_year")>=$max){
      if($id) header("Location: ".$_SERVER['REQUEST_URI'].'&year='.ccdb("select extract('year' from chat_at) from chat where chat_id=$1",$id));
      else header("Location: ".$_SERVER['REQUEST_URI'].'&year='.ccdb("select max(chat_year) from chat_year"));
      exit;
    }
  }else{
    if(!isset($_GET['month'])){
      if(ccdb("select chat_year_count from chat_year where chat_year=$1",$_GET['year'])>=$max){
        if($id) header("Location: ".$_SERVER['REQUEST_URI'].'&month='.ccdb("select extract('month' from chat_at) from chat where chat_id=$1",$id));
        else header("Location: ".$_SERVER['REQUEST_URI'].'&month='.ccdb("select max(chat_month) from chat_month where chat_year=$1",$_GET['year']));
        exit;
      }
    }else{
      if(!isset($_GET['day'])){
        if(ccdb("select chat_month_count from chat_month where chat_year=$1 and chat_month=$2",$_GET['year'],$_GET['month'])>=$max){
          if($id) header("Location: ".$_SERVER['REQUEST_URI'].'&day='.ccdb("select extract('day' from chat_at) from chat where chat_id=$1",$id));
          else header("Location: ".$_SERVER['REQUEST_URI'].'&day='.ccdb("select max(chat_day) from chat_day where chat_year=$1 and chat_month=$2",$_GET['year'],$_GET['month']));
          exit;
        }
      }else{
        if(!isset($_GET['hour'])){
          if(ccdb("select chat_day_count from chat_day where chat_year=$1 and chat_month=$2 and chat_day=$3",$_GET['year'],$_GET['month'],$_GET['day'])>=$max){
            if($id) header("Location: ".$_SERVER['REQUEST_URI'].'&hour='.ccdb("select extract('hour' from chat_at) from chat where chat_id=$1",$id));
            else header("Location: ".$_SERVER['REQUEST_URI'].'&hour='.ccdb("select max(chat_hour) from chat_hour where chat_year=$1 and chat_month=$2 and chat_day=$3",$_GET['year'],$_GET['month'],$_GET['day']));
            exit;
          }
        }
      }
    }
  }
}
$maxday = 31;
if(isset($_GET['month'])){
  $maxday = ccdb("select extract('day' from make_timestamp($1,$2,1,0,0,0)+'1mon - 1d'::interval)",$_GET['year'],$_GET['month']);
}
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; flex: 0 0 auto; font-size: 1rem; background: #<?=$colour_dark?>; color: #<?=$colour_mid?>; white-space: nowrap; }
    header select, header input, header a:not(.icon) { margin: 3px; }
    header .icon { border: 1px solid #<?=$colour_light?>; margin: 1px; }
    header .icon>img { background: #<?=$colour_mid?>; height: 24px; border: 1px solid #<?=$colour_dark?>; display: block; padding: 1px; }
    header a { color: #<?=$colour_mid?>; }
    mark[data-markjs] { background-color: #<?=$colour_highlight?>80; }
    .period>div { margin: 0.5em; white-space: nowrap; }
    .period>div>span { font-size: smaller; font-style: italic; }
    a:not([href]) { color: #<?=$colour_highlight?>; }

    .period { border: 2px solid #<?=$colour_mid?>; border-right: none; }
    .spacer { flex: 0 0 auto; min-height: 1em; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    a[data-lightbox] img { cursor: zoom-in; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message .who { white-space: nowrap; font-size: 0.6em;<?if(!$search){?> position: absolute; top: -1.2em;<?}?> }
    .message .icon { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; margin-top: 0.1em; border-radius: 4px; }
    .message .markdown-wrapper { display: flex; position: relative; flex: 0 1 auto; max-height: 8em; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; overflow: hidden; }
    .message .markdown-wrapper .reply { position: absolute; right: 0; bottom: 0; background-color: #fffd; padding: 0.2em; padding-left: 0.4em; }

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
    .message.merged .who,
    .message.merged .icon { visibility: hidden; }
    .message.thread .markdown-wrapper { background: #<?=$colour_highlight?>40; }
    .message:target .markdown-wrapper { box-shadow: 0 0 2px 2px #<?=$colour_highlight?> inset; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 4px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/codemirror/codemirror.js"></script>
  <script src="/lib/codemirror/sql.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
  <script src="/lib/mark.js"></script>
  <script>
    $(function(){
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
      $('.markdown').renderMarkdown();
      <?if(!$search){?>threadChat();<?}?>
      $('.message .markdown :not(a)>img').each(function(i){ $(this).wrap('<a href="'+$(this).attr('src')+'" data-lightbox="'+$(this).closest('.message').attr('id')+'"></a>'); });
      $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
      $('.markdown').mark('<?=$search?>', { "separateWordSearch": false, "ignoreJoiners": true });
      setTimeout(function(){ $('.message:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
    });
  </script>
  <title><?=$room_derived_name?> Transcript - TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column;">
  <header xstyle="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div xstyle="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community_name?>">TopAnswers <?=ucfirst($community_name)?></a>
      <span>transcript for <a href="/<?=$community_name?>?<?=$room_question_id?'q='.$room_question_id:'room='.$room_id?>"><?=$room_derived_name?></a></span>
      <form action="/transcript" method="get" style="display: inline;"><input type="search" name="search" placeholder="search"><input type="hidden" name="room" value="<?=$_GET['room']?>"></form>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <a href="/profile" class="icon"><img src="/identicon?id=<?=$account_id?>"></a>
    </div>
  </header>
  <main style="display: flex; margin: 2px; background-color: #<?=$colour_light?>; overflow: hidden;">
    <?if($search){?>
      <div id="messages" style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column; padding: 1em; overflow: scroll; background-color: #<?=$colour_mid?>; scroll-behavior: smooth;">
        <?db("select set_config('pg_trgm.strict_word_similarity_threshold','0.55',false)");?>
        <?db("select set_config('pg_trgm.gin_fuzzy_search_limit','1000',false)");?>
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,account_is_me,account_name,reply_account_name,reply_account_is_me,i_flagged,i_starred,chat_flag_count,chat_star_count,chat_has_history
                           , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at_text
                      from search($1)",$search) as $r){ extract($r);?>
          <small class="who">
            <span style="color: #<?=$colour_dark?>;"><?=$chat_at_text?>&nbsp;</span>
            <?=$account_is_me?'<em>Me</em>':$account_name?>
            <?if($chat_reply_id){?>
              <a href="#c<?=$chat_reply_id?>" style="color: #<?=$colour_dark?>; text-decoration: none;">&nbsp;replying to&nbsp;</a>
              <?=$reply_account_is_me?'<em>Me</em>':$reply_account_name?>
            <?}?>
          </small>
          <div id="c<?=$chat_id?>" class="message" data-id="<?=$chat_id?>" data-name="<?=$account_name?>">
            <img class="icon" src="/identicon?id=<?=$account_id?>">
            <div class="markdown-wrapper">
              <div class="markdown" data-markdown="<?=$chat_markdown?>"></div>
            </div>
            <span class="buttons">
              <span class="button-group show">
                <i class="stars <?=$i_starred?'me ':''?>fa fa-star" data-count="<?=$chat_star_count?>"></i>
                <i></i>
                <i class="flags <?=$i_flagged?'me ':''?>fa fa-flag" data-count="<?=$chat_flag_count?>"></i>
                <i></i>
              </span>
              <span class="button-group show">
                <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
                <i></i>
                <?if($chat_has_history){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
                <i></i>
              </span>
            </span>
          </div>
        <?}?>
      </div>
    <?}else{?>
      <?if(isset($_GET['year'])){?>
        <div class="period" style="flex 0 0 auto;">
          <div>
            <div>year</div>
          </div>
          <?foreach(db("select chat_year,chat_year_count from chat_year order by chat_year") as $r){extract($r);?>
            <div>
              <a<?if($chat_year!==(int)$_GET['year']){?> href="/transcript?room=<?=$_GET['room']?>&year=<?=$chat_year?>"<?}?>><?=$chat_year?></a>
              <span>(<?=$chat_year_count?>)</span>
            </div>
          <?}?>
        </div>
      <?}?>
      <?if(isset($_GET['month'])){?>
        <div class="period" style="flex 0 0 auto;">
          <div>
            <div>month</div>
          </div>
          <?foreach(db("select chat_month,chat_month_count,to_char(to_timestamp(chat_month::text,'MM'),'Month') month_text from chat_month where chat_year=$1 order by chat_month",$_GET['year']) as $r){extract($r);?>
            <div>
              <a class="month"<?if($chat_month!==(int)$_GET['month']){?> href="/transcript?room=<?=$_GET['room']?>&year=<?=$_GET['year']?>&month=<?=$chat_month?>"<?}?>><?=$month_text?></a>
              <span>(<?=$chat_month_count?>)</span>
            </div>
          <?}?>
        </div>
      <?}?>
      <?if(isset($_GET['day'])){?>
        <div class="period" style="flex 0 0 auto;">
          <div>
            <div>day</div>
          </div>
          <?foreach(db("select chat_day,chat_day_count from chat_day where chat_year=$1 and chat_month=$2 order by chat_day",$_GET['year'],$_GET['month']) as $r){extract($r);?>
            <div>
              <a<?if($chat_day!==(int)$_GET['day']){?> href="/transcript?room=<?=$_GET['room']?>&year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$chat_day?>"<?}?>><?=$chat_day?></a>
              <span>(<?=$chat_day_count?>)</span>
            </div>
          <?}?>
        </div>
      <?}?>
      <?if(isset($_GET['hour'])){?>
        <div class="period" style="flex 0 0 auto;">
          <div>
            <div>hour</div>
          </div>
          <?foreach(db("select chat_hour,chat_hour_count from chat_hour where chat_year=$1 and chat_month=$2 and chat_day=$3 order by chat_hour",$_GET['year'],$_GET['month'],$_GET['day']) as $r){extract($r);?>
            <div>
              <a<?if($chat_hour!==(int)$_GET['hour']){?> href="/transcript?room=<?=$_GET['room']?>&year=<?=$_GET['year']?>&month=<?=$_GET['month']?>&day=<?=$_GET['day']?>&hour=<?=$chat_hour?>"<?}?>><?=$chat_hour?>:00-<?=$chat_hour+1?>:00</a>
              <span>(<?=$chat_hour_count?>)</span>
            </div>
          <?}?>
        </div>
      <?}?>
      <div id="messages" style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column; padding: 1em; overflow: scroll; background-color: #<?=$colour_mid?>; scroll-behavior: smooth;">
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,account_is_me,account_name,reply_account_name,reply_account_is_me,chat_gap,i_flagged,i_starred,chat_account_will_repeat
                            ,reply_is_different_segment,chat_flag_count,chat_star_count,chat_has_history,chat_account_is_repeat
                           , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at_text
                      from range(make_timestamp($1,$2,$3,$4,0,0),make_timestamp($5,$6,$7,$8,0,0)+'1h'::interval)
                     ",$_GET['year']??1,$_GET['month']??1,$_GET['day']??1,$_GET['hour']??0,$_GET['year']??9999,$_GET['month']??12,$_GET['day']??$maxday,$_GET['hour']??23) as $r){ extract($r);?>
          <?if(!$chat_account_is_repeat){?><div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div><?}?>
          <div id="c<?=$chat_id?>" class="message<?=$chat_account_is_repeat?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small class="who">
              <span style="color: #<?=$colour_dark?>;"><?=$chat_at_text?>&nbsp;</span>
              <?=$account_is_me?'<em>Me</em>':$account_name?>
              <?if($chat_reply_id){?>
                <?if($reply_is_different_segment){?>
                  <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_reply_id?>#c<?=$chat_reply_id?>" style="color: #<?=$colour_dark?>; text-decoration: none;">&nbsp;replying to&nbsp;</a>
                <?}else{?>
                  <a href="#c<?=$chat_reply_id?>" style="color: #<?=$colour_dark?>; text-decoration: none;">&nbsp;replying to&nbsp;</a>
                <?}?>
                <?=$reply_account_is_me?'<em>Me</em>':$reply_account_name?>
              <?}?>
            </small>
            <img class="icon" src="/identicon?id=<?=$account_id?>">
            <div class="markdown-wrapper">
              <div class="markdown" data-markdown="<?=$chat_markdown?>"></div>
            </div>
            <span class="buttons">
              <span class="button-group show">
                <i class="stars <?=$i_starred?'me ':''?>fa fa-star" data-count="<?=$chat_star_count?>"></i>
                <i></i>
                <i class="flags <?=$i_flagged?'me ':''?>fa fa-flag" data-count="<?=$chat_flag_count?>"></i>
                <i></i>
              </span>
              <span class="button-group show">
                <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
                <i></i>
                <?if($chat_has_history){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
                <i></i>
              </span>
            </span>
          </div>
        <?}?>
      </div>
    <?}?>
  </main>
</body>   
</html>   
