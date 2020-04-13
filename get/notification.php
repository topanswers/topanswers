<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to notification,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select room_id,room_can_chat
                  , (select coalesce(jsonb_agg(z),'[]'::jsonb)
                     from (select notification_id,notification_at,notification_at_iso,notification_type,notification_data
                                , count(*) over (partition by notification_type,notification_data#>array['stack_id']) notification_count
                                , row_number() over (partition by notification_type,notification_data#>array['stack_id'] order by notification_at) notification_stack_rn
                           from(select notification_id,notification_at
                                     , to_char(notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                     , case when c.notification_id is not null then 'chat'
                                            when q.notification_id is not null then 'question'
                                            when qf.notification_id is not null then 'question flag'
                                            when a.notification_id is not null then 'answer'
                                            when af.notification_id is not null then 'answer flag'
                                            when s.notification_id is not null then 'system'
                                            end notification_type
                                     , case when c.notification_id is not null then to_jsonb(c)
                                            when q.notification_id is not null then to_jsonb(q)
                                            when qf.notification_id is not null then to_jsonb(qf)
                                            when a.notification_id is not null then to_jsonb(a)
                                            when af.notification_id is not null then to_jsonb(af)
                                            when s.notification_id is not null then to_jsonb(s)
                                            end notification_data
                                from notification n
                                     left join chat2 c using(notification_id)
                                     left join question2 q using(notification_id)
                                     left join question_flag qf using(notification_id)
                                     left join answer2 a using(notification_id)
                                     left join answer_flag af using(notification_id)
                                     left join system s using(notification_id)
                                where not notification_is_dismissed
                                      and(coalesce(c.notification_id,q.notification_id,qf.notification_id,a.notification_id,af.notification_id,s.notification_id) is not null)) z
                           order by notification_at desc) z
                     where notification_stack_rn=1) notifications
             from one"));
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<?foreach($notifications as $notification){ extract($notification); extract($notification_data,EXTR_PREFIX_ALL,'d');?>
  <div id="n<?=$notification_id?>" class="notification<?=in_array($notification_type,['chat','system'])?' message':''?>"
       style="background: rgb(<?=$d_community_rgb_mid?>); --rgb-dark: <?=$d_community_rgb_dark?>; --rgb-mid: <?=$d_community_rgb_mid?>; --rgb-light: <?=$d_community_rgb_light?>; --rgb-warning: <?=$d_community_rgb_warning?>;"
       data-id="<?=$notification_id?>" data-type="<?=$notification_type?>"<?if($notification_type==='chat'){?> data-name="<?=$d_chat_from_account_name?>" data-reply-id="<?=$d_chat_reply_id?>"<?}?>>
    <?if($notification_type==='chat'){?>
      <span class="who" title="<?=$d_chat_from_account_name?><?=$d_chat_reply_id?' replying to '.($d_chat_reply_account_is_me?'Me':$d_chat_reply_account_name):''?> in <?=$d_chat_room_name?>">
        <i class="fa fa-times-circle" title="dismiss notification"></i>
        <span class="when" data-at="<?=$notification_at_iso?>"></span>,
        <?=$d_chat_from_account_name?>
        <?if($d_room_id!==$room_id){?>
          <?=$d_chat_reply_id?' <span>&nbsp;replying to&nbsp;</span> '.($d_chat_reply_account_is_me?'<em>Me</em>':$d_chat_reply_account_name):''?>
          <span>&nbsp;in&nbsp;</span>
          <a class="ellipsis" href="/<?=$d_community_name?>?<?=$d_chat_is_question_room?'q='.$d_room_question_id:'room='.$d_room_id?>" data-room="<?=$d_room_id?>" title="<?=$d_chat_room_name?>"><?=$d_chat_room_name?></a>
        <?}else{?>
          <?=$d_chat_reply_id?'<a href="#c'.$d_chat_reply_id.'">&nbsp;replying to&nbsp;</a> '.($d_chat_reply_account_is_me?'<em>Me</em>':$d_chat_reply_account_name):''?>
        <?}?>
      </span>
      <img title="<?=$d_chat_from_account_name?>" class="icon" src="/identicon?id=<?=$d_chat_from_account_id?>">
      <div class="markdown" data-markdown="<?=$d_chat_markdown?>"><pre><?=$d_chat_markdown?></pre></div>
      <?if($d_room_can_chat&&($d_room_id===$room_id)){?>
        <span class="buttons">
          <span class="button-group show">
            <i class="stars <?=$d_chat_i_starred?'me ':''?>fa fa-star<?=$d_chat_i_starred?'':'-o'?>" data-count="<?=$d_chat_star_count?>"></i>
            <i></i>
            <i class="flags <?=$d_chat_i_flagged?'me ':''?>fa fa-flag<?=$d_chat_i_flagged?'':'-o'?>" data-count="<?=$d_chat_flag_count?>"></i>
            <i></i>
          </span>
          <span class="button-group show">
            <i class="<?=$d_chat_i_starred?'me ':''?>fa fa-star<?=$d_chat_i_starred?'':'-o'?>" title="star"></i>
            <i class="fa fa-ellipsis-h" title="more actions"></i>
            <i class="<?=$d_chat_i_flagged?'me ':''?> fa fa-flag<?=$d_chat_i_flagged?'':'-o'?>" title="flag"></i>
            <i class="fa fa-reply fa-rotate-180" title="reply"></i>
          </span>
          <span class="button-group">
            <a href="/transcript?room=<?=$d_room_id?>&id=<?=$notification_id?>#c<?=$notification_id?>" class="fa fa-link" title="permalink"></a>
            <i class="fa fa-ellipsis-h" title="more actions"></i>
            <?if($d_chat_has_history){?><a href="/chat-history?id=<?=$notification_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
            <i></i>
          </span>
        </span>
      <?}?>
    <?}elseif($notification_type==='question'){?>
      <i class="fa fa-times-circle" title="dismiss notification"></i>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?>question edit<?=($notification_count===1)?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/question-history?id=<?=$d_question_id?>#h<?=$d_question_history_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='question flag'){?>
      <i class="fa fa-times-circle" title="dismiss notification"></i>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?> question flag<?=($notification_count===1)?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#question" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='answer'){?>
      <i class="fa fa-times-circle" title="dismiss notification"></i>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <?if($d_answer_notification_is_edit){?>
        <span>, <?=($notification_count>1)?$notification_count.' ':''?>answer edit<?=($notification_count===1)?'':'s'?> on:&nbsp;</span>
        <a class="ellipsis" href="/answer-history?id=<?=$d_answer_id?>#h<?=$d_answer_history_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
      <?}else{?>
        <span>, answer posted on:&nbsp;</span>
        <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#a<?=$d_answer_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
      <?}?>
    <?}elseif($notification_type==='answer flag'){?>
      <i class="fa fa-times-circle" title="dismiss notification"></i>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?> answer flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#a<?=$d_answer_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='system'){?>
      <span class="who" title="">
        <i class="fa fa-times-circle" title="dismiss notification"></i>
        <span class="when" data-at="<?=$notification_at_iso?>"></span>,&nbsp;
        Message from&nbsp;
        <a href="/<?=$d__community_name?>">TopAnswers<?=$d_community_name?'/'.$d_community_name:''?></a>
      </span>
      <div class="markdown" data-markdown="<?=$d_system_notification_message?>"><pre><?=$d_system_notification_message?></pre></div>
    <?}?>
  </div>
<?}?>
<?ob_end_flush();
