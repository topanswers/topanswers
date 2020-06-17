<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
if(!isset($_GET['room'])) die('room not set');
db("set search_path to chat,pg_temp");
$authenticated = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(chat_id,chat_change_id)),'[]')::json from changes($1)",$_GET['from']));
if(isset($_GET['quote'])) exit(ccdb("select quote2($1,$2)::varchar",$_GET['room'],$_GET['id']));
if(isset($_GET['minimap'])) {
  header("Content-Type: image/jpeg");
  $image = imagecreatefromstring(pg_unescape_bytea(ccdb("select room_bitmap from one2")));
  imagejpeg($image,null,90);
  exit;
}
$one = isset($_GET['from']) && isset($_GET['to']);
$limited = false;
if(isset($_GET['limit'])){
  $limit = min(100,intval($_GET['limit']));
  $limited = true;
}
extract(cdb("select community_language,room_can_chat
                  , (select coalesce(jsonb_agg(z),'[]'::jsonb)
                     from (select chat_id,account_id,account_image_url
                                 ,chat_reply_id,chat_markdown,chat_at,chat_change_id,account_is_me,account_name,reply_account_name,reply_account_is_me,chat_gap,chat_next_gap
                                 ,communicant_votes,chat_editable_age,i_flagged,i_starred,chat_account_is_repeat,chat_crew_flags,chat_flag_count,chat_star_count,chat_has_history,chat_pings
                                 ,notification_id
                                , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') chat_at_iso
                                , current_date - chat_at::date chat_days_ago
                           from range(nullif($1,'')::bigint,nullif($2,'')::bigint,nullif($3::integer,0)) z) z) chats
             from one",$_GET['from']??'',$_GET['to']??'',$limited?$limit+1:0),EXTR_PREFIX_ALL,'o');
$more = $limited && (count($o_chats)>$limit);
if($more) array_pop($o_chats);
include '../lang/chat.'.$o_community_language.'.php';
?>
<?foreach($o_chats as $n=>$r){ extract($r);?>
  <?if( !isset($_GET['to']) && ($n===0) ){?>
    <div class="spacer<?=$chat_next_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_next_gap)/4,2)?>em;" data-gap="<?=$chat_next_gap?>"></div>
  <?}?>
  <div id="c<?=$chat_id?>"
       class="message<?=$account_is_me?' mine':''?><?=$chat_account_is_repeat?' merged':''?><?=$notification_id?' notify':''?><?=($chat_crew_flags>0)?' deleted':''?>"
       data-id="<?=$chat_id?>"
       data-chat-id="<?=$chat_id?>"
       <?if($notification_id){?>data-notification-id="<?=$notification_id?>"<?}?>
       data-name="<?=$account_name?>"
       data-reply-id="<?=$chat_reply_id?$chat_reply_id:''?>"
       data-reply-name="<?=$reply_account_name?>"
       data-pings="<?=$chat_pings?>"
       data-crew-flags="<?=$chat_crew_flags?>"
       data-change-id="<?=$chat_change_id?>"
       data-at="<?=$chat_at_iso?>"
       data-days-ago="<?=$chat_days_ago?>">
    <span class="who" title="<?=$account_is_me?'Me':$account_name?><?=$chat_reply_id?' replying to '.($reply_account_is_me?'Me':$reply_account_name):''?>">
      <?=$account_is_me?'<em>Me</em>':$account_name?>
      <?=$chat_reply_id?'<a class="reply" href="#c'.$chat_reply_id.'">replying to</a> '.($reply_account_is_me?'<em>Me</em>':$reply_account_name):''?>
      <span class="when" data-at="<?=$chat_at_iso?>"></span>
    </span>
    <img title="<?=($account_name)?$account_name:'Anonymous'?> (<?=$l_stars?>: <?=$l_num($communicant_votes)?>)" class="icon" src="<?=$account_image_url?>">
    <div class="markdown" data-markdown="<?=$chat_markdown?>"><pre><?=$chat_markdown?></pre></div>
    <?if($authenticated){?>
      <span class="buttons">
        <span class="button-group show">
          <i class="stars <?=$i_starred?'me ':''?>fa fa-star<?=($o_room_can_chat&&!$i_starred&&!$account_is_me)?'-o':''?>" data-count="<?=$chat_star_count?>"></i>
          <i></i>
          <i class="flags <?=$i_flagged?'me ':''?>fa fa-flag<?=($account_is_me||$i_flagged)?'':'-o'?>" data-count="<?=$chat_flag_count?>"></i>
          <i></i>
        </span>
        <?if($o_room_can_chat){?>
          <?if($account_is_me){?>
            <span class="button-group show">
              <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
              <?if($chat_editable_age){?><i class="fa fa-edit" title="edit"></i><?}else if($chat_has_history){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
              <i class="<?=$i_flagged?'me ':''?> fa fa-flag<?=$i_flagged?'':'-o'?>" title="flag"></i>
              <i></i>
            </span>
          <?}else{?>
            <span class="button-group show">
              <i class="<?=$i_starred?'me ':''?>fa fa-star<?=$i_starred?'':'-o'?>" title="star"></i>
              <i class="fa fa-ellipsis-h" title="more actions"></i>
              <i class="<?=$i_flagged?'me ':''?> fa fa-flag<?=$i_flagged?'':'-o'?>" title="flag"></i>
              <i class="fa fa-reply fa-rotate-180" title="reply"></i>
            </span>
            <span class="button-group">
              <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
              <i class="fa fa-ellipsis-h" title="more actions"></i>
              <?if($chat_has_history){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
              <i></i>
            </span>
          <?}?>
        <?}else{?>
          <span class="button-group show">
            <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
            <i></i>
            <?if($chat_has_history){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
            <i></i>
          </span>
        <?}?>
      </span>
    <?}?>
  </div>
  <?if(!$chat_account_is_repeat&&!$one){?>
    <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div>
  <?}?>
<?}?>
<?if($more){?>
  <i class="fa fa-fw fa-spinner"></i>
<?}?>
