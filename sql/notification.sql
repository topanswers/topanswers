create schema notification;
grant usage on schema notification to get,post;
set local search_path to notification,api,pg_temp;
--
--
create view notification with (security_barrier) as
select notification_id,notification_at,notification_dismissed_at
     , notification_dismissed_at is not null notification_is_dismissed
from db.notification
where account_id=get_account_id();
--
create view chat with (security_barrier) as
select notification_id,room_id,community_id,chat_id,chat_at,chat_change_id,chat_reply_id,chat_reply_account_id,chat_markdown,community_name,room_question_id
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , account_id chat_from_account_id
     , account_image_url chat_from_account_image_url
     , chat_reply_account_id=get_account_id() chat_reply_account_is_me
     , room_derived_name chat_room_name 
     , room_question_id is not null chat_is_question_room
     , (select count(1) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(1) from db.chat_star where chat_id=chat.chat_id) chat_star_count
     , (select count(1) from db.chat_history where chat_id=chat.chat_id)>1 chat_has_history
     , (select account_derived_name from api._account where account_id=chat.account_id) chat_from_account_name
     , (select account_derived_name from api._account where account_id=chat_reply_account_id) chat_reply_account_name
     , chat_flag_at is not null chat_i_flagged
     , chat_star_at is not null chat_i_starred
     , notification_id stack_id
from notification natural join (select notification_id,chat_id from db.chat_notification) n
                  natural join db.chat natural join db.room natural join api._room natural join api._community natural join db.community natural join api._account
     natural left join (select chat_id chat_reply_id, account_id chat_reply_account_id from db.chat) c
     natural left join (select chat_id,chat_flag_at from db.chat_flag where account_id=get_account_id()) f
     natural left join (select chat_id,chat_star_at from db.chat_star where account_id=get_account_id()) s;
--
create view question with (security_barrier) as
select notification_id,question_id,question_history_id,question_title,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , question_id stack_id
from notification
     natural join (select notification_id,question_history_id from db.question_notification) qn
     natural join (select question_history_id,question_id from db.question_history) h
     natural join db.question
     natural join api._community
     natural join db.community;
--
create view question_flag with (security_barrier) as
select notification_id,question_id,question_title,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , question_id stack_id
from notification
     natural join (select notification_id,question_flag_history_id from db.question_flag_notification) f
     natural join (select question_flag_history_id,question_id from db.question_flag_history) h
     natural join (select community_id,question_id,question_title from db.question) q
     natural join api._community
     natural join db.community;
--
create view answer with (security_barrier) as
select notification_id,answer_id,answer_history_id,question_id,question_title,question_room_id
      ,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , answer_at<>answer_history_at answer_notification_is_edit
     , answer_id stack_id
from notification
     natural join (select notification_id,answer_history_id from db.answer_notification) n
     natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) h
     natural join db.answer
     natural join (select community_id,question_id,question_title,question_room_id from db.question) q
     natural join api._community
     natural join db.community;
--
create view answer_flag with (security_barrier) as
select notification_id,answer_id,question_id,question_title,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , answer_id stack_id
from notification
     natural join (select notification_id,answer_flag_history_id from db.answer_flag_notification) f
     natural join (select answer_flag_history_id,answer_id from db.answer_flag_history) h
     natural join db.answer
     natural join (select community_id,question_id,question_title from db.question) q
     natural join api._community
     natural join db.community;
--
create view system with (security_barrier) as
select notification_id,system_notification_message,community_name
     , coalesce(community_rgb_dark,'0,0,0') community_rgb_dark
     , coalesce(community_rgb_mid,'248,248,248') community_rgb_mid
     , coalesce(community_rgb_light,'255,255,255') community_rgb_light
     , coalesce(community_rgb_warning,'153,0,0') community_rgb_warning
     , notification_id stack_id
from notification
     natural join db.system_notification n
     left join (select community_id,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_warning
                from api._community natural join db.community) c on c.community_id = n.system_notification_community_id;
--
create view one with (security_barrier) as select room_id,room_can_chat from api._room where room_id=get_room_id();
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
--
--
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='notification' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='notification' and proname!~'^_' );
end$$;
--
--
create function dismiss(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('invalid notification id') where not exists (select 1 from notification where notification_id=id and account_id=get_account_id());
  --
  with q as (select question_id from question_notification natural join (select question_history_id,question_id from question_history) h where notification_id=id)
     , n as (select notification_id from notification natural join question_notification natural join (select question_history_id,question_id from question_history) h natural join q where notification_dismissed_at is null and account_id=get_account_id())
     , u as (update notification set notification_dismissed_at = current_timestamp from n where notification.notification_id=n.notification_id returning account_id)
  update account set account_notification_id = default from u where account.account_id=u.account_id;
  --
  with q as (select question_id from question_flag_notification natural join (select question_flag_history_id,question_id from question_flag_history) h where notification_id=id)
     , n as (select notification_id from notification natural join question_flag_notification natural join (select question_flag_history_id,question_id from question_flag_history) h natural join q where notification_dismissed_at is null and account_id=get_account_id())
     , u as (update notification set notification_dismissed_at = current_timestamp from n where notification.notification_id=n.notification_id returning account_id)
  update account set account_notification_id = default from u where account.account_id=u.account_id;
  --
  with a as (select answer_id from answer_notification natural join (select answer_history_id,answer_id from answer_history) h where notification_id=id)
     , n as (select notification_id from notification natural join answer_notification natural join (select answer_history_id,answer_id from answer_history) h natural join a where notification_dismissed_at is null and account_id=get_account_id())
     , u as (update notification set notification_dismissed_at = current_timestamp from n where notification.notification_id=n.notification_id returning account_id)
  update account set account_notification_id = default from u where account.account_id=u.account_id;
  --
  with a as (select answer_id from answer_flag_notification natural join (select answer_flag_history_id,answer_id from answer_flag_history) h where notification_id=id)
     , n as (select notification_id from notification natural join answer_flag_notification natural join (select answer_flag_history_id,answer_id from answer_flag_history) h natural join a where notification_dismissed_at is null and account_id=get_account_id())
     , u as (update notification set notification_dismissed_at = current_timestamp from n where notification.notification_id=n.notification_id returning account_id)
  update account set account_notification_id = default from u where account.account_id=u.account_id;
  --
  with u as (update notification set notification_dismissed_at = current_timestamp where notification_dismissed_at is null and notification_id=id returning account_id)
  update account set account_notification_id = default from u where account.account_id=u.account_id;
$$;
--
create function dismiss_all() returns void language sql security definer set search_path=db,api,pg_temp as $$
  update notification set notification_dismissed_at = current_timestamp where notification_dismissed_at is null and account_id=get_account_id();
  update account set account_notification_id = default where account_id=get_account_id();
$$;
--
--
revoke all on all functions in schema notification from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='notification' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='notification' and proname!~'^_' );
end$$;
