<?    
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
if(!isset($_GET['room'])) die('Room not set');
db("set search_path to transcript,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select account_id,community_name,room_id,room_derived_name,room_can_chat,room_question_id,community_tables_are_monospace,community_code_language,my_community_regular_font_name,my_community_monospace_font_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
             from one"));
$max = 500;
$search = $_GET['search']??'';
$id = $_GET['id']??0;
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
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
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="icon" href="/communityicon?community=<?=$community_name?>" type="image/png">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 14px; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    mark[data-markjs] { background-color: rgb(var(--rgb-highlight))80; }
    a:not([href]) { color: rgb(var(--rgb-highlight)); }
    a[data-lightbox] img { cursor: zoom-in; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }
    .period { border: 2px solid rgb(var(--rgb-mid)); border-right: none; flex: 0 0 auto; overflow: auto; }
    .period>div { margin: 7px; white-space: nowrap; }
    .period>div>span { font-size: smaller; font-style: italic; }
    .spacer { flex: 0 0 auto; min-height: 13px; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: rgba(var(--rgb-dark),0.5); }

    #messages { flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column; padding: 13px; overflow: auto; background-color: rgb(var(--rgb-mid)); scroll-behavior: smooth; }

    .message { width: 100%; position: relative; flex: 0 0 auto; display: flex; align-items: flex-start; }
    .message:not(.processed) { opacity: 0; }
    .message .who { white-space: nowrap; font-size: 10px;<?if(!$search){?> position: absolute; top: -1.2em;<?}?> }
    .message .markdown { flex: 0 1 auto; max-height: 50vh; padding: 0.25rem; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; background: white; overflow: auto; transition: background linear 0.1s; }

    .message .button-group { display: grid; grid-template: 11px 11px / 12px 12px; align-items: center; justify-items: start; font-size: 11px; margin-left: 1px; margin-top: 1px; }
    .message .button-group:first-child { grid-template: 11px 11px / 22px 2px; }
    .message .button-group .fa { color: rgb(var(--rgb-dark)); cursor: pointer; text-decoration: none; }
    .message .button-group .fa.me { color: rgb(var(--rgb-highlight)); }
    .message:hover .button-group:first-child { display: none; }
    .message .button-group:not(.show) { display: none; }
    .message:not(:hover) .button-group:not(:first-child) { display: none; }
    .message .button-group:first-child .fa[data-count]:not([data-count^="0"])::after { content: attr(data-count); font-family: inherit }
    .message .button-group:first-child .fa[data-count][data-count="0"] { visibility: hidden; }

    .message.merged { margin-top: -1px; }
    .message.merged .who,
    .message.merged .icon { visibility: hidden; }
    .message.thread .markdown { background: rgba(var(--rgb-highlight),0.25); }
    .message:target .markdown { box-shadow: 0 0 2px 2px rgb(var(--rgb-highlight)) inset; }
  </style>
  <script src="/lib/js.cookie.js"></script>
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
      var promises = [];
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
      $('.markdown').renderMarkdown(promises);
      Promise.all(promises).then(() => {
        $('.message').addClass('processed');
      });
      <?if(!$search){?>threadChat();<?}?>
      $('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()+' later'); });
      $('.markdown').mark('<?=$search?>', { "separateWordSearch": false, "ignoreJoiners": true });
      setTimeout(function(){ $('.message:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
    });
  </script>
  <title><?=$room_derived_name?> Transcript - TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column;">
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div class="container">
      <span class="element">transcript for <a href="/<?=$community_name?>?<?=$room_question_id?'q='.$room_question_id:'room='.$room_id?>"><?=$room_derived_name?></a></span>
      <form class="element" action="/transcript" method="get" style="display: inline;"><input type="search" name="search" placeholder="search"><input type="hidden" name="room" value="<?=$_GET['room']?>"></form>
    </div>
    <div>
      <?if($auth){?><a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a><?}?>
    </div>
  </header>
  <main style="display: flex; flex: 1 1 auto; background-color: rgb(var(--rgb-light)); overflow: hidden;">
    <?if($search){?>
      <div id="messages">
        <?db("select set_config('pg_trgm.strict_word_similarity_threshold','0.55',false)");?>
        <?db("select set_config('pg_trgm.gin_fuzzy_search_limit','1000',false)");?>
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,account_is_me,account_name,reply_account_name,reply_account_is_me,i_flagged,i_starred,chat_flag_count,chat_star_count,chat_has_history
                           , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at_text
                      from search($1)",$search) as $r){ extract($r);?>
          <small class="who">
            <span style="color: rgb(var(--rgb-dark));"><?=$chat_at_text?>&nbsp;</span>
            <?=$account_is_me?'<em>Me</em>':$account_name?>
            <?if($chat_reply_id){?>
              <a href="#c<?=$chat_reply_id?>" style="color: rgb(var(--rgb-dark)); text-decoration: none;">&nbsp;replying to&nbsp;</a>
              <?=$reply_account_is_me?'<em>Me</em>':$reply_account_name?>
            <?}?>
          </small>
          <div id="c<?=$chat_id?>" class="message" data-id="<?=$chat_id?>" data-name="<?=$account_name?>">
            <img class="icon" src="/identicon?id=<?=$account_id?>">
            <div class="markdown" data-markdown="<?=$chat_markdown?>"></div>
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
        <div class="period">
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
        <div class="period">
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
        <div class="period">
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
      <div id="messages">
        <?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,account_is_me,account_name,reply_account_name,reply_account_is_me,chat_gap,i_flagged,i_starred,chat_account_will_repeat
                            ,reply_is_different_segment,chat_flag_count,chat_star_count,chat_has_history,chat_account_is_repeat
                           , to_char(chat_at at time zone 'UTC','YYYY-MM-DD HH24:MI:SS') chat_at_text
                      from range(make_timestamp($1,$2,$3,$4,0,0),make_timestamp($5,$6,$7,$8,0,0)+'1h'::interval)
                     ",$_GET['year']??1,$_GET['month']??1,$_GET['day']??1,$_GET['hour']??0,$_GET['year']??9999,$_GET['month']??12,$_GET['day']??$maxday,$_GET['hour']??23) as $r){ extract($r);?>
          <?if(!$chat_account_is_repeat){?><div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div><?}?>
          <div id="c<?=$chat_id?>" class="message<?=$chat_account_is_repeat?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
            <small class="who">
              <span style="color: rgb(var(--rgb-dark));"><?=$chat_at_text?>&nbsp;</span>
              <?=$account_is_me?'<em>Me</em>':$account_name?>
              <?if($chat_reply_id){?>
                <?if($reply_is_different_segment){?>
                  <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_reply_id?>#c<?=$chat_reply_id?>" style="color: rgb(var(--rgb-dark)); text-decoration: none;">&nbsp;replying to&nbsp;</a>
                <?}else{?>
                  <a href="#c<?=$chat_reply_id?>" style="color: rgb(var(--rgb-dark)); text-decoration: none;">&nbsp;replying to&nbsp;</a>
                <?}?>
                <?=$reply_account_is_me?'<em>Me</em>':$reply_account_name?>
              <?}?>
            </small>
            <img class="icon" src="/identicon?id=<?=$account_id?>">
            <div class="markdown" data-markdown="<?=$chat_markdown?>"></div>
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
