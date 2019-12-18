<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
if(!isset($_GET['room'])) die('room not set');
db("set search_path to api,chat,pg_temp");
extract(cdb("select uuid,community_name,room_name,room_can_chat,community_code_language,my_community_regular_font_name,my_community_monospace_font_name,colour_dark,colour_mid,colour_light,colour_highlight
             from login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']));
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(chat_id,chat_change_id)),'[]') from chat where chat_change_id>$1",$_GET['fromid']));
if(isset($_GET['quote'])) exit(ccdb("select quote($1)",$_GET['id']));
if(isset($_GET['activerooms'])){
  foreach(db("select room_id,room_name,community_colour,room_account_unread_messages from activerooms()") as $r){ extract($r);?>
    <a<?if($room_id!==$_GET['room']){?> href="."<?}?> data-room="<?=$room_id?>"<?if($room_account_unread_messages>0){?> data-unread="<?=$room_account_unread_messages?>"<?}?>>
      <img title="<?=($room_name)?$room_name:''?>" class="icon" data-id="<?=$room_id?>" data-name="<?=$room_name?>" src="/roomicon?id=<?=$room_id?>" style="background-color: #<?=$community_colour?>">
    </a><?
  }
  exit;
}
if(isset($_GET['activeusers'])){
  foreach(db("select account_id,account_name,account_is_me,communicant_votes from activeusers()") as $r){ extract($r);?>
    <img title="<?=($account_name)?$account_name:'Anonymous'?> (Stars: <?=$communicant_votes?>)" class="icon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" data-fullname="<?=$account_name?>" src="/identicon?id=<?=$account_id?>"><?
  }
  exit;
}
$id = $_GET['id']??ccdb("select greatest(min(chat_id)-1,0) from (select chat_id from chat order by chat_id desc limit 100) z");
?>
<?foreach(db("select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,chat_change_id,account_is_me,account_name,reply_account_name,reply_account_is_me,chat_gap,communicant_votes,chat_editable_age
                    ,i_flagged,i_starred,chat_account_will_repeat,chat_flag_count,chat_star_count,chat_has_history,chat_account_is_repeat,rn
                   , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') chat_at_iso
              from range($1,nullif($2,'')::bigint)",$id,isset($_GET['one'])?$id:'') as $r){ extract($r);?>
  <?if(($chat_account_is_repeat==='f')&&!isset($_GET['one'])){?>
    <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>"></div>
  <?}?>
  <div id="c<?=$chat_id?>" class="message<?=($account_is_me==='t')?' mine':''?><?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>" data-change-id="<?=$chat_change_id?>" data-at="<?=$chat_at_iso?>">
    <span class="who" title="<?=($account_is_me==='t')?'Me':$account_name?> <?=$chat_reply_id?'replying to '.(($reply_account_is_me==='t')?'Me':$reply_account_name):''?>">
      <?=($account_is_me==='t')?'<em>Me</em>':$account_name?>
      <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$colour_dark.'; text-decoration: none;">replying to</a> '.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>
      <span class="when" data-at="<?=$chat_at_iso?>"></span>
    </span>
    <img title="<?=($account_name)?$account_name:'Anonymous'?> (Stars: <?=$communicant_votes?>)" class="icon" src="/identicon?id=<?=$account_id?>">
    <div class="markdown<?=($rn==="1")?'':' nofiddle'?>" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
    <?if($uuid){?>
      <span class="buttons">
        <span class="button-group show">
          <i class="stars <?=($i_starred==='t')?'me ':''?>fa fa-star<?=(($account_is_me==='t')||($i_starred==='t'))?'':'-o'?>" data-count="<?=$chat_star_count?>"></i>
          <i></i>
          <i class="flags <?=($i_flagged==='t')?'me ':''?>fa fa-flag<?=(($account_is_me==='t')||($i_flagged==='t'))?'':'-o'?>" data-count="<?=$chat_flag_count?>"></i>
          <i></i>
        </span>
        <?if($room_can_chat==='t'){?>
          <?if($account_is_me==='t'){?>
            <span class="button-group show">
              <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
              <i></i>
              <?if($chat_editable_age==='t'){?><i class="fa fa-edit" title="edit"></i><?}else if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
              <i></i>
            </span>
          <?}else{?>
            <span class="button-group show">
              <i class="<?=($i_starred==='t')?'me ':''?>fa fa-star<?=($i_starred==='t')?'':'-o'?>" title="star"></i>
              <i class="fa fa-ellipsis-h" title="more actions"></i>
              <i class="<?=($i_flagged==='t')?'me ':''?> fa fa-flag<?=($i_flagged==='t')?'':'-o'?>" title="flag"></i>
              <i class="fa fa-reply fa-rotate-180" title="reply"></i>
            </span>
            <span class="button-group">
              <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
              <i class="fa fa-ellipsis-h" title="more actions"></i>
              <?if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
              <i></i>
            </span>
          <?}?>
        <?}else{?>
          <span class="button-group show">
            <a href="/transcript?room=<?=$_GET['room']?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link" title="permalink"></a>
            <i></i>
            <?if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
            <i></i>
          </span>
        <?}?>
      </span>
    <?}?>
  </div>
<?}?>
<?if(!isset($_GET['one'])){?><div class="spacer" style="line-height: 0; min-height: 0;"></div><?}?>
