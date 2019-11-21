<?
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid'] ?? false;
if($uuid) ccdb("select login($1)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'new': exit(ccdb("select new_chat($1,$2,nullif($3,'')::integer,('{'||$4||'}')::integer[])",$_POST['room'],$_POST['msg'],$_POST['replyid']??'',isset($_POST['pings'])?implode(',',$_POST['pings']):''));
    case 'edit': exit(ccdb("select change_chat($1,$2)",$_POST['editid'],$_POST['msg']));
    case 'flag': exit(ccdb("select set_chat_flag($1)",$_POST['id']));
    case 'unflag': exit(ccdb("select remove_chat_flag($1)",$_POST['id']));
    case 'star': exit(ccdb("select set_chat_star($1)",$_POST['id']));
    case 'unstar': exit(ccdb("select remove_chat_star($1)",$_POST['id']));
    case 'dismiss': exit(ccdb("select dismiss_notification($1)",$_POST['id']));
    default: fail(400,'unrecognized action');
  }
}
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(chat_id,chat_change_id)),'[]') from chat where room_id=$1 and chat_change_id>$2",$_GET['room'],$_GET['fromid']));
if(!isset($_GET['room'])) die('room not set');
$room = $_GET['room'];
ccdb("select count(*) from room where room_id=$1",$room)==='1' or die('invalid room');
if(isset($_GET['activeusers'])){
  foreach(db("select account_id,account_name,account_is_me
                   , coalesce(account_community_votes,0) account_community_votes
              from room_account_x natural join account natural left join (select * from account_community natural join room where room_id=$1) z
              where room_id=$1
              order by room_account_x_latest_chat_at desc",$room) as $r){ extract($r);?>
    <img title="<?=($account_name)?$account_name:'Anonymous'?> (Reputation: <?=$account_community_votes?>)" class="identicon<?=($account_is_me==='f')?' pingable':''?>" data-id="<?=$account_id?>" data-name="<?=explode(' ',$account_name)[0]?>" data-fullname="<?=$account_name?>" src="/identicon.php?id=<?=$account_id?>"><?
  }
  exit;
}
extract(cdb("select community_name community
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from room natural join community
             where room_id=$1",$room));
$id = $_GET['id']??ccdb("select greatest(min(chat_id)-1,0) from (select chat_id from chat where room_id=$1 order by chat_id desc limit 100) z",$room);
?>
<?foreach(db("select *
              from(select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and chat_gap<60 chat_account_is_repeat
                   from (select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,chat_at,chat_change_id,chat_has_history
                              , coalesce(account_community_votes,0) account_community_votes
                              , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') chat_at_iso
                              , coalesce(nullif(account_name,''),'Anonymous') account_name
                              , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                              , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                              , round(extract('epoch' from chat_at-(lag(chat_at) over (order by chat_at)))) chat_gap
                              , extract('epoch' from current_timestamp-chat_at)<240 chat_editable_age
                              , chat_flag_at is not null i_flagged
                              , chat_star_at is not null i_starred
                              , (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lag(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
                         from chat c natural join account natural left join chat_flag natural left join chat_star natural left join account_community
                         where room_id=$1 and chat_id".(isset($_GET['one'])?'=':'>=')."$2".($uuid?"":" and chat_flag_count=0").") z ) z
              where chat_id".(isset($_GET['one'])?'=':'>')."$2
              order by chat_at",$room,$id) as $r){ extract($r);?>
  <?if(($chat_account_is_repeat==='f')&&!isset($_GET['one'])){?>
    <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>" data-at="<?=$chat_at_iso?>"><span></span><span></span></div>
  <?}?>
  <div id="c<?=$chat_id?>" class="message<?=($account_is_me==='t')?' mine':''?><?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>" data-change-id="<?=$chat_change_id?>" data-at="<?=$chat_at_iso?>">
    <small class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$colour_dark.'; text-decoration: none;">&nbsp;replying to&nbsp;</a>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</small>
    <img title="Reputation: <?=$account_community_votes?>" class="identicon" src="/identicon.php?id=<?=$account_id?>">
    <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
    <span class="buttons">
      <span class="button-group show">
        <i class="stars <?=($i_starred==='t')?'me ':''?>fa fa-star<?=(($account_is_me==='t')||($i_starred==='t'))?'':'-o'?>" data-count="<?=$chat_star_count?>"></i>
        <i></i>
        <i class="flags <?=($i_flagged==='t')?'me ':''?>fa fa-flag<?=(($account_is_me==='t')||($i_flagged==='t'))?'':'-o'?>" data-count="<?=$chat_flag_count?>"></i>
        <i></i>
      </span>
      <?if($account_is_me==='t'){?>
        <span class="button-group show">
          <a href="/transcript?room=<?=$room?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link"></a>
          <i></i>
          <?if($chat_editable_age==='t'){?><i class="fa fa-edit"></i><?}else if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o"></a><?}else{?><i></i><?}?>
          <i></i>
        </span>
      <?}else{?>
        <span class="button-group show">
          <i class="<?=($i_starred==='t')?'me ':''?>fa fa-star<?=($i_starred==='t')?'':'-o'?>"></i>
          <i class="fa fa-ellipsis-h"></i>
          <i class="<?=($i_flagged==='t')?'me ':''?> fa fa-flag<?=($i_flagged==='t')?'':'-o'?>"></i>
          <i class="fa fa-reply fa-rotate-180" title="reply"></i>
        </span>
        <span class="button-group">
          <a href="/transcript?room=<?=$room?>&id=<?=$chat_id?>#c<?=$chat_id?>" class="fa fa-link"></a>
          <i class="fa fa-ellipsis-h"></i>
          <?if($chat_has_history==='t'){?><a href="/chat-history?id=<?=$chat_id?>" class="fa fa-clock-o"></a><?}else{?><i></i><?}?>
          <i></i>
        </span>
      <?}?>
    </span>
  </div>
<?}?>
