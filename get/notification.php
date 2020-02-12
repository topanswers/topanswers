<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to notification,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select room_id,room_can_chat from one"));
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
$notification_count = ccdb("select count(1) from chat_notification")
                    + ccdb("select count(1) from question_notification")
                    + ccdb("select count(1) from answer_notification")
                    + ccdb("select count(1) from question_flag_notification")
                    + ccdb("select count(1) from answer_flag_notification")
                    + ccdb("select count(1) from system_notification");
?>
<div id="notification-wrapper">
  <?if($notification_count>0){?>
    <div class="label container"><div class="element"><?=$notification_count?> Notification<?=($notification_count===1)?'':'s'?></div></div>
    <div id="notifications">
      <?foreach(db("with c as (select 'chat' notification_type
                                    , 1 notification_count
                                    , chat_id notification_id
                                    , chat_at notification_at
                                    , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
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
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
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
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , null::integer
                                    , null::boolean
                                    , null::integer
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from (select question_id,question_title,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning
                                          , max(question_flag_history_id) question_flag_history_id
                                          , max(question_flag_notification_at) question_flag_notification_at
                                          , count(distinct account_id) question_flag_count
                                     from question_flag_notification
                                     group by question_id,question_title,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning) n )
                       , a as (select 'answer' notification_type
                                    , 1 notification_count
                                    , answer_history_id notification_id
                                    , answer_notification_at notification_at
                                    , to_char(answer_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
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
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
                                    , community_name notification_community_name
                                    , question_id
                                    , question_title
                                    , answer_id
                                    , null::boolean
                                    , null::integer
                                    , null::integer, null::integer, null::text, null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from (select answer_id,question_id,question_title,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning
                                          , max(answer_flag_history_id) answer_flag_history_id
                                          , max(answer_flag_notification_at) answer_flag_notification_at
                                          , count(distinct account_id) answer_flag_count
                                     from answer_flag_notification
                                     group by answer_id,question_id,question_title,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning) n)
                       , s as (select 'system' notification_type
                                    , 1 notification_count
                                    , system_notification_id notification_id
                                    , system_notification_at notification_at
                                    , to_char(system_notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                    , community_rgb_mid notification_rgb_mid
                                    , community_rgb_dark notification_rgb_dark
                                    , community_rgb_warning notification_rgb_warning
                                    , community_name notification_community_name
                                    , null::integer question_id
                                    , null::text question_title
                                    , null::integer answer_id
                                    , null::boolean answer_notification_is_edit
                                    , null::integer notification_room_id
                                    , null::integer chat_from_account_id
                                    , null::integer chat_reply_id
                                    , system_notification_message chat_markdown
                                    , null::integer, null::integer, null::boolean, null::boolean, null::text, null::text, null::text, null::boolean, null::boolean, null::boolean
                               from system_notification)
                    select * from c union all select * from q union all select * from qf union all select * from a union all select * from af union all select * from s
                    order by notification_at desc limit 50") as $r){ extract($r);?>
        <div id="n<?=$notification_id?>" class="notification<?=in_array($notification_type,['chat','system'])?' message':''?>"
             style="background: rgb(<?=$notification_rgb_mid?>); --rgb-dark: <?=$notification_rgb_dark?>; --rgb-warning: <?=$notification_rgb_warning?>;"
             data-id="<?=$notification_id?>" data-type="<?=$notification_type?>"<?if($notification_type==='chat'){?> data-name="<?=$chat_from_account_name?>" data-reply-id="<?=$chat_reply_id?>"<?}?>>
          <?if($notification_type==='chat'){?>
            <span class="who" title="<?=$chat_from_account_name?><?=$chat_reply_id?' replying to '.($chat_reply_account_is_me?'Me':$chat_reply_account_name):''?> in <?=$chat_room_name?>">
              <i class="fa fa-times-circle" title="dismiss notification"></i>
              <span class="when" data-at="<?=$notification_at_iso?>"></span>,
              <?=$chat_from_account_name?>
              <?if($notification_room_id!==$room_id){?>
                <?=$chat_reply_id?' <span>&nbsp;replying to&nbsp;</span> '.($chat_reply_account_is_me?'<em>Me</em>':$chat_reply_account_name):''?>
                <span>&nbsp;in&nbsp;</span>
                <a class="ellipsis" href="/<?=$notification_community_name?>?<?=$chat_is_question_room?'q='.$question_id:'room='.$notification_room_id?>" data-room="<?=$notification_room_id?>" title="<?=$chat_room_name?>"><?=$chat_room_name?></a>
              <?}else{?>
                <?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'">&nbsp;replying to&nbsp;</a> '.($chat_reply_account_is_me?'<em>Me</em>':$chat_reply_account_name):''?>
              <?}?>
            </span>
            <img title="<?=($chat_from_account_name)?$chat_from_account_name:'Anonymous'?>" class="icon" src="/identicon?id=<?=$chat_from_account_id?>">
            <div class="markdown" data-markdown="<?=$chat_markdown?>"><pre><?=$chat_markdown?></pre></div>
            <?if($room_can_chat&&($notification_room_id===$room_id)){?>
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
                  <i class="fa fa-reply fa-rotate-180" title="reply"></i>
                </span>
                <span class="button-group">
                  <a href="/transcript?room=<?=$notification_room_id?>&id=<?=$notification_id?>#c<?=$notification_id?>" class="fa fa-link" title="permalink"></a>
                  <i class="fa fa-ellipsis-h" title="more actions"></i>
                  <?if($chat_has_history){?><a href="/chat-history?id=<?=$notification_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
                  <i></i>
                </span>
              </span>
            <?}?>
          <?}elseif($notification_type==='question'){?>
            <i class="fa fa-times-circle" title="dismiss notification"></i>
            <span class="when" data-at="<?=$notification_at_iso?>"></span>
            <span>, question edit:&nbsp;</span>
            <a class="ellipsis" href="/question-history?id=<?=$question_id?>#h<?=$notification_id?>" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
          <?}elseif($notification_type==='question flag'){?>
            <i class="fa fa-times-circle" title="dismiss notification"></i>
            <span class="when" data-at="<?=$notification_at_iso?>"></span>
            <span>, <?=($notification_count>1)?$notification_count.' ':''?> question flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
            <a class="ellipsis" href="/<?=$notification_community_name?>?q=<?=$question_id?>#question" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
          <?}elseif($notification_type==='answer'){?>
            <i class="fa fa-times-circle" title="dismiss notification"></i>
            <span class="when" data-at="<?=$notification_at_iso?>"></span>
            <span>, answer <?=$answer_notification_is_edit?'edit':'posted'?> on:&nbsp;</span>
            <a class="ellipsis" href="/<?=$answer_notification_is_edit?('answer-history?id='.$answer_id.'#h'.$notification_id):($notification_community_name.'?q='.$question_id.'#a'.$answer_id)?>" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
          <?}elseif($notification_type==='answer flag'){?>
            <i class="fa fa-times-circle" title="dismiss notification"></i>
            <span class="when" data-at="<?=$notification_at_iso?>"></span>
            <span>, <?=($notification_count>1)?$notification_count.' ':''?> answer flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
            <a class="ellipsis" href="/<?=$notification_community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" title="<?=$question_title?>"><?=$question_title?>&nbsp;</a>
          <?}elseif($notification_type==='system'){?>
            <span class="who" title="">
              <i class="fa fa-times-circle" title="dismiss notification"></i>
              <span class="when" data-at="<?=$notification_at_iso?>"></span>,&nbsp;
              Message from&nbsp;
              <a href="/<?=$notification_community_name?>">TopAnswers<?=$notification_community_name?'/'.$notification_community_name:''?></a>
            </span>
            <div class="markdown" data-markdown="<?=$chat_markdown?>"><pre><?=$chat_markdown?></pre></div>
          <?}?>
        </div>
      <?}?>
    </div>
  <?}?>
</div>
<?ob_end_flush();
