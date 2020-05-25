<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to notification,pg_temp");
$auth = ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
$limit = ccdb("select count(1) from notification where notification_dismissed_at is null")+($_GET['dismissed']??0);
$has_dismissed = ccdb("select exists(select 1 from notification where notification_dismissed_at is not null)");
extract(cdb("select room_id,room_can_chat
                  , (select coalesce(jsonb_agg(z order by notification_dismissed_at desc nulls first, notification_at desc),'[]'::jsonb)
                     from (select notification_id,notification_dismissed_at,notification_at,notification_at_iso,notification_type,notification_data
                                , count(*) over (partition by notification_type,notification_data#>array['stack_id']) notification_count
                                , row_number() over (partition by notification_type,notification_data#>array['stack_id'] order by notification_at) notification_stack_rn
                           from(select notification_id,notification_dismissed_at,notification_at,notification_at_iso
                                     , case when c is not null then 'chat'
                                            when q is not null then 'question'
                                            when qf is not null then 'question flag'
                                            when a is not null then 'answer'
                                            when af is not null then 'answer flag'
                                            when s is not null then 'system'
                                            end notification_type
                                     , coalesce(c,q,qf,a,af,s) notification_data
                                from (select notification_id,notification_dismissed_at,notification_at
                                           , to_char(notification_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') notification_at_iso
                                           , (select to_jsonb(c) from chat c where c.notification_id=n.notification_id) c
                                           , (select to_jsonb(q) from question q where q.notification_id=n.notification_id) q
                                           , (select to_jsonb(qf) from question_flag qf where qf.notification_id=n.notification_id) qf
                                           , (select to_jsonb(a) from answer a where a.notification_id=n.notification_id) a
                                           , (select to_jsonb(af) from answer_flag af where af.notification_id=n.notification_id) af
                                           , (select to_jsonb(s) from system s where s.notification_id=n.notification_id) s
                                      from notification n
                                      order by notification_dismissed_at desc nulls first limit $1) z ) z
                           where notification_type is not null) z
                     where notification_stack_rn=1) notifications
             from one",$limit));
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<?$seperator = false; $n = 0;?>
<?foreach($notifications as $notification){ extract($notification); extract($notification_data,EXTR_PREFIX_ALL,'d');?>
  <?if($notification_dismissed_at&&!$seperator){ $seperator = true; if($n>0){?><hr><?}}?>
  <?$n += $notification_count;?>
  <div id="n<?=$notification_id?>" class="notification<?=$notification_dismissed_at?' dismissed':''?><?=in_array($notification_type,['chat','system'])?' message':''?>"
       style="background: rgb(<?=$d_community_rgb_mid?>); --rgb-dark: <?=$d_community_rgb_dark?>; --rgb-mid: <?=$d_community_rgb_mid?>; --rgb-light: <?=$d_community_rgb_light?>; --rgb-warning: <?=$d_community_rgb_warning?>;"
       data-id="<?=$notification_id?>" data-type="<?=$notification_type?>"<?if($notification_type==='chat'){?> data-name="<?=$d_chat_from_account_name?>" data-chat-id="<?=$d_chat_id?>" data-reply-id="<?=$d_chat_reply_id?>"<?}?>>
    <?if($notification_type==='chat'){?>
      <span class="who" title="<?=$d_chat_from_account_name?><?=$d_chat_reply_id?' replying to '.($d_chat_reply_account_is_me?'Me':$d_chat_reply_account_name):''?> in <?=$d_chat_room_name?>">
        <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
        <span class="when" data-at="<?=$notification_at_iso?>"></span>,
        <?=$d_chat_from_account_name?>
        <?if($d_room_id!==$room_id){?>
          <?=$d_chat_reply_id?' <span>&nbsp;replying to&nbsp;</span> '.($d_chat_reply_account_is_me?'<em>Me</em>':$d_chat_reply_account_name):''?>
          <span>&nbsp;in&nbsp;</span>
          <a class="ellipsis" href="/<?=$d_community_name?>?<?=$d_chat_is_question_room?'q='.$d_room_question_id:'room='.$d_room_id?>" data-room="<?=$d_room_id?>" title="<?=$d_chat_room_name?>"><?=$d_chat_room_name?></a>
        <?}else{?>
          <?=$d_chat_reply_id?'<a class="reply" href="#c'.$d_chat_reply_id.'">&nbsp;replying to&nbsp;</a> '.($d_chat_reply_account_is_me?'<em>Me</em>':$d_chat_reply_account_name):''?>
        <?}?>
      </span>
      <img title="<?=$d_chat_from_account_name?>" class="icon" src="<?=$d_chat_from_account_image_url?>">
      <div class="markdown" data-markdown="<?=$d_chat_markdown?>"><pre><?=$d_chat_markdown?></pre></div>
      <?if($room_can_chat&&($d_room_id===$room_id)){?>
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
            <a href="/transcript?room=<?=$d_room_id?>&id=<?=$d_chat_id?>#c<?=$d_chat_id?>" class="fa fa-link" title="permalink"></a>
            <i class="fa fa-ellipsis-h" title="more actions"></i>
            <?if($d_chat_has_history){?><a href="/chat-history?id=<?=$notification_id?>" class="fa fa-clock-o" title="history"></a><?}else{?><i></i><?}?>
            <i></i>
          </span>
        </span>
      <?}?>
    <?}elseif($notification_type==='question'){?>
      <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?>question edit<?=($notification_count===1)?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/question-history?id=<?=$d_question_id?>#h<?=$d_question_history_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='question flag'){?>
      <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?> question flag<?=($notification_count===1)?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#question" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='answer'){?>
      <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <?if($d_answer_notification_is_edit){?>
        <span>, <?=($notification_count>1)?$notification_count.' ':''?>answer edit<?=($notification_count===1)?'':'s'?> on:&nbsp;</span>
        <a class="ellipsis" href="/answer-history?id=<?=$d_answer_id?>#h<?=$d_answer_history_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
      <?}else{?>
        <span>, answer posted on:&nbsp;</span>
        <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#a<?=$d_answer_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
      <?}?>
    <?}elseif($notification_type==='answer flag'){?>
      <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
      <span class="when" data-at="<?=$notification_at_iso?>"></span>
      <span>, <?=($notification_count>1)?$notification_count.' ':''?> answer flag<?=($notification_count==='1')?'':'s'?>:&nbsp;</span>
      <a class="ellipsis" href="/<?=$d_community_name?>?q=<?=$d_question_id?>#a<?=$d_answer_id?>" title="<?=$d_question_title?>"><?=$d_question_title?>&nbsp;</a>
    <?}elseif($notification_type==='system'){?>
      <span class="who" title="">
        <?if(!$notification_dismissed_at){?><i class="fa fa-times-circle" title="dismiss notification"></i><?}?>
        <span class="when" data-at="<?=$notification_at_iso?>"></span>,&nbsp;
        Message from&nbsp;
        <a href="/<?=$d_community_name?>">TopAnswers<?=$d_community_name?'/'.$d_community_name:''?></a>
      </span>
      <div class="markdown" data-markdown="<?=$d_system_notification_message?>"><pre><?=$d_system_notification_message?></pre></div>
    <?}?>
  </div>
<?}?>
<?if($has_dismissed&&($n===$limit)){?><a id="more-notifications" href="." data-dismissed="<?=isset($_GET['dismissed'])?(intval($_GET['dismissed']*2)):'10'?>">show <?=isset($_GET['dismissed'])?'more ':''?>dismissed notifications</a><?}?>
<?ob_end_flush();
