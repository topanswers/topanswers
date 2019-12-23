<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to notification,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select colour_dark,room_id,room_can_chat from one"));
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<div id="notification-wrapper">
  <?if(ccdb("select count(*)>0 from chat_notification")
     ||ccdb("select count(*)>0 from question_notification")
     ||ccdb("select count(*)>0 from answer_notification")
     ||ccdb("select count(*)>0 from question_flag_notification")
     ||ccdb("select count(*)>0 from answer_flag_notification")){?>
    <div id="notifications">
      <div class="label">Notifications:</div>
      <?foreach(db("with c as (select 'chat' notification_type
                                    , 1 notification_count
                                    , chat_id notification_id
                                    , chat_at notification_at
                                    , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , encode(community_mid_shade,'hex') notification_mid_shade
                                    , encode(community_dark_shade,'hex') notification_dark_shade
                                    , community_name notification_community_name
                                    , question_id
                                    , null::text question_title
                                    , null::integer answer_id
                                    , null::boolean answer_notification_is_edit
                                    , room_id notification_room_id
                                    , account_id chat_from_account_id
                                    , chat_reply_id
                                    , chat_markdown
                                    , chat_flag_count
                                    , chat_star_count
                                    , chat_has_history
                                    , chat_is_question_room
                                    , chat_room_name 
                                    , coalesce(nullif(account_name,''),'Anonymous') chat_from_account_name
                                    , (select coalesce(nullif(account_name,''),'Anonymous') from account where account_id=c.chat_reply_account_id) chat_reply_account_name
                                    , chat_reply_account_is_me
                                    , chat_flag_at is not null chat_i_flagged
                                    , chat_star_at is not null chat_i_starred
                               from chat_notification natural join chat c natural join account
                                    natural left join chat_flag
                                    natural left join chat_star)
                       , q as (select 'question' notification_type
                                    , 1 notification_count
                                    , question_history_id notification_id
                                    , question_notification_at notification_at
                                    , to_char(question_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , encode(community_mid_shade,'hex') notification_mid_shade
                                    , encode(community_dark_shade,'hex') notification_dark_shade
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , null::integer
                                    , null::boolean
                                    , question_room_id
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from question_notification natural join question)
                      , qf as (select 'question flag' notification_type
                                    , question_flag_count notification_count
                                    , question_flag_history_id notification_id
                                    , question_flag_notification_at notification_at
                                    , to_char(question_flag_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , encode(community_mid_shade,'hex') notification_mid_shade
                                    , encode(community_dark_shade,'hex') notification_dark_shade
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , null::integer
                                    , null::boolean
                                    , null::integer
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from (select question_id,question_title,community_name,community_mid_shade,community_dark_shade
                                          , max(question_flag_history_id) question_flag_history_id
                                          , max(question_flag_notification_at) question_flag_notification_at
                                          , count(distinct account_id) question_flag_count
                                     from question_flag_notification
                                     group by question_id,question_title,community_name,community_mid_shade,community_dark_shade) n )
                       , a as (select 'answer' notification_type
                                    , 1 notification_count
                                    , answer_history_id notification_id
                                    , answer_notification_at notification_at
                                    , to_char(answer_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , encode(community_mid_shade,'hex') notification_mid_shade
                                    , encode(community_dark_shade,'hex') notification_dark_shade
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , answer_id
                                    , answer_notification_is_edit
                                    , question_room_id
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from answer_notification natural join answer)
                      , af as (select 'answer flag' notification_type
                                    , answer_flag_count notification_count
                                    , answer_flag_history_id notification_id
                                    , answer_flag_notification_at notification_at
                                    , to_char(answer_flag_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , encode(community_mid_shade,'hex') notification_mid_shade
                                    , encode(community_dark_shade,'hex') notification_dark_shade
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , answer_id
                                    , null::boolean
                                    , null::integer
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from (select answer_id,question_id,question_title,community_name,community_mid_shade,community_dark_shade
                                          , max(answer_flag_history_id) answer_flag_history_id
                                          , max(answer_flag_notification_at) answer_flag_notification_at
                                          , count(distinct account_id) answer_flag_count
                                     from answer_flag_notification
                                     group by answer_id,question_id,question_title,community_name,community_mid_shade,community_dark_shade) n)
                    select * from c union all select * from q union all select * from qf union all select * from a union all select * from af
                    order by notification_at limit 20") as $r){ extract($r);?>
        <div id="n<?=$notification_id?>" class="message" style="background: #<?=$notification_mid_shade?>;" data-id="<?=$notification_id?>" data-type="<?=$notification_type?>"<?if($notification_type==='chat'){?> data-name="<?=$chat_from_account_name?>" data-reply-id="<?=$chat_reply_id?>"<?}?>>
          <?if($notification_type==='chat'){?>
            <span class="who" title="<?=$chat_from_account_name?><?=$chat_reply_id?' replying to '.($chat_reply_account_is_me?'Me':$chat_reply_account_name):''?> in <?=$chat_room_name?>">
              <?=$chat_from_account_name?>
              <?if($notification_room_id!==$room_id){?>
                <?=$chat_reply_id?' replying to '.($chat_reply_account_is_me?'<em>Me</em>':$chat_reply_account_name):''?>
                <span style="color: #<?=$notification_dark_shade?>;"> in </span>
                <a href="/<?=$notification_community_name?>?<?=$chat_is_question_room?'q='.$question_id:'room='.$notification_room_id?>" data-room="<?=$notification_room_id?>" style="color: #<?=$notification_dark_shade?>;" title="<?=$chat_room_name?>"><?=$chat_room_name?></a>
              <?}else{?>
                <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$notification_dark_shade.'; text-decoration: none;">&nbsp;replying to&nbsp;</a> '.($chat_reply_account_is_me?'<em>Me</em>':$chat_reply_account_name):''?>
              <?}?>
              <span class="when element" style="color: #<?=$notification_dark_shade?>b0" data-at="<?=$notification_at_iso?>"></span>
              — 
              <span style="color: #<?=$notification_dark_shade?>;">(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
            </span>
            <img title="<?=($chat_from_account_name)?$chat_from_account_name:'Anonymous'?>" class="icon" src="/identicon?id=<?=$chat_from_account_id?>">
            <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
            <span class="buttons">
              <span class="button-group show">
                <i class="stars <?=$chat_i_starred?'me ':''?>fa fa-star<?=$chat_i_starred?'':'-o'?>" data-count="<?=$chat_star_count?>"></i>
                <i></i>
                <i class="flags <?=$chat_i_flagged?'me ':''?>fa fa-flag<?=$chat_i_flagged?'':'-o'?>" data-count="<?=$chat_flag_count?>"></i>
                <i></i>
              </span>
              <span class="button-group show">
                <i class="<?=$chat_i_starred?'me ':''?>fa fa-star<?=$chat_i_starred?'':'-o'?>" title="star"></i>
                <i class="fa fa-ellipsis-h" title="more actions"></i>
                <i class="<?=$chat_i_flagged?'me ':''?> fa fa-flag<?=$chat_i_flagged?'':'-o'?>" title="flag"></i>
                <?if($room_can_chat&&($notification_room_id===$room_id)){?><i class="fa fa-fw fa-reply fa-rotate-180" title="reply"></i><?}else{?><i></i><?}?>
              </span>
              <span class="button-group">
                <a href="/transcript?room=<?=$notification_room_id?>&id=<?=$notification_id?>#c<?=$notification_id?>" class="fa fa-link" title="permalink"></a>
                <i class="fa fa-ellipsis-h" title="more actions"></i>
                <?if($chat_has_history){?><a href="/chat-history?id=<?=$notification_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
                <i></i>
              </span>
            </span>
          <?}elseif($notification_type==='question'){?>
            <div style="display: flex; overflow: hidden; font-size: 12px; white-space: nowrap;">
              <span class="when" style="color: #<?=$notification_dark_shade?>b0" data-at="<?=$notification_at_iso?>"></span>
              <span style="flex: 0 0 auto;">, question edit:&nbsp;</span>
              <a href="/question-history?id=<?=$question_id?>#h<?=$notification_id?>" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
              —
              <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
            </div>
          <?}elseif($notification_type==='question flag'){?>
            <div style="display: flex; overflow: hidden; font-size: 12px; white-space: nowrap;">
              <span class="when" style="color: #<?=$notification_dark_shade?>b0" data-at="<?=$notification_at_iso?>"></span>
              <span style="flex: 0 0 auto;">, <?=($notification_count>1)?$notification_count.' ':''?> question flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
              <a href="/<?=$notification_community_name?>?q=<?=$question_id?>#question" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
              —
              <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
            </div>
          <?}elseif($notification_type==='answer'){?>
            <div style="display: flex; overflow: hidden; font-size: 12px; white-space: nowrap;">
              <span class="when" style="color: #<?=$notification_dark_shade?>b0" data-at="<?=$notification_at_iso?>"></span>
              <span style="flex: 0 0 auto;">, answer <?=$answer_notification_is_edit?'edit':'posted'?> on:&nbsp;</span>
              <a href="/answer-history?id=<?=$answer_id?>#h<?=$notification_id?>" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
              —
              <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
            </div>
          <?}elseif($notification_type==='answer flag'){?>
            <div style="display: flex; overflow: hidden; font-size: 12px; white-space: nowrap;">
              <span class="when" style="color: #<?=$notification_dark_shade?>b0" data-at="<?=$notification_at_iso?>"></span>
              <span style="flex: 0 0 auto;">, <?=($notification_count>1)?$notification_count.' ':''?> answer flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
              <a href="/<?=$notification_community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" style="flex: 0 1 auto; overflow: hidden; text-overflow: ellipsis; color: #<?=$notification_dark_shade?>;" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
              —
              <span style="flex: 0 0 auto; color: #<?=$notification_dark_shade?>;">&nbsp;(<a href='.' class="dismiss" style="color: #<?=$notification_dark_shade?>;" title="dismiss notification">dismiss</a>)</span>
            </div>
          <?}?>
        </div>
      <?}?>
    </div>
    <div style="position: relative;"><div id="notification-gradient"></div></div>
  <?}?>
</div>
<?ob_end_flush();
