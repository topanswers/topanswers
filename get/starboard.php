<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to starboard,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select room_id,room_can_chat from one"));
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<?foreach(db("select * from chat limit 30") as $r){ extract($r);?>
  <div id="s<?=$chat_id?>" class="message" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>">
    <span class="who" title="<?=($chat_account_is_me?'Me':$account_name)?><?=$chat_reply_id?' replying to '.($chat_reply_account_is_me?'Me':$chat_reply_account_name):''?>">
      <?=($chat_account_is_me?'<em>Me</em>':$account_name)?>
      <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: rgb(var(--rgb-dark)); text-decoration: none;">&nbsp;replying to&nbsp;</a> '.($chat_reply_account_is_me?'<em>Me</em>':$chat_reply_account_name):''?>
      <span class="when" data-at="<?=$chat_at_iso?>"></span>
    </span>
    <img title="<?=($account_name)?$account_name:'Anonymous'?>" class="icon" src="/identicon?id=<?=$account_id?>">
    <div class="markdown" data-markdown="<?=$chat_markdown?>"><pre><?=$chat_markdown?></pre></div>
    <span class="buttons">
      <span class="button-group show">
        <i></i>
        <i></i>
        <i></i>
        <i></i>
      </span>
      <span class="button-group show">
        <i class="stars <?=$chat_i_starred?'me ':''?>fa fa-star<?=($room_can_chat&&!$chat_i_starred&&!$chat_account_is_me)?'-o':''?>" data-count="<?=$chat_star_count?>"></i>
        <i></i>
        <a href="/transcript?room=<?=$room_id?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
        <i></i>
      </span>
    </span>
  </div>
<?}?>
<?ob_end_flush();
