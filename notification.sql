create schema notification;
grant usage on schema notification to get,post;
set local search_path to notification,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_name from db.account;
create view chat_flag with (security_barrier) as select chat_id,chat_flag_at from db.chat_flag where account_id=get_account_id();
create view chat_star with (security_barrier) as select chat_id,chat_star_at from db.chat_star where account_id=get_account_id();
create view chat_notification with (security_barrier) as select chat_id,chat_notification_at from db.chat_notification where account_id=get_account_id();
--
create view chat with (security_barrier) as
select room_id,community_id,chat_id,account_id,chat_at,chat_change_id,chat_reply_id,chat_reply_account_id,chat_markdown,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning,community_mid_shade,community_dark_shade,community_warning_color,question_id
     , chat_reply_account_id=get_account_id() chat_reply_account_is_me
     , coalesce(question_title,room_name) chat_room_name 
     , question_id is not null chat_is_question_room
     , (select count(1) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(1) from db.chat_star where chat_id=chat.chat_id) chat_star_count
     , (select count(1) from db.chat_history where chat_id=chat.chat_id)>1 chat_has_history
from chat_notification natural join db.chat natural join db.room natural join api._community natural join db.community
     natural left join (select question_room_id room_id, question_id, question_title from db.question) q
     natural left join (select chat_id chat_reply_id, account_id chat_reply_account_id from db.chat) c;
--
create view question_notification with (security_barrier) as
select question_history_id,question_id,question_notification_at
     , question_at<>question_history_at question_notification_is_edit
from db.question_notification natural join (select question_history_id,question_id,question_history_at from db.question_history) h natural join (select question_id,question_at from db.question) q
where account_id=get_account_id();
--
create view question with (security_barrier) as
select question_id,question_title,question_room_id,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning,community_mid_shade,community_dark_shade,community_warning_color
from (select distinct question_id from question_notification) n natural join db.question natural join api._community natural join db.community;
--
create view question_flag_notification with (security_barrier) as
select question_flag_history_id,question_flag_notification_at,question_id,question_title,account_id,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning,community_mid_shade,community_dark_shade,community_warning_color
from (select question_flag_history_id,question_flag_notification_at from db.question_flag_notification where account_id=get_account_id()) f
     natural join db.question_flag_history
     natural left join (select community_id,question_id,question_title from db.question) q
     natural join api._community
     natural join db.community;
--
create view answer_notification with (security_barrier) as
select answer_history_id,answer_id,answer_notification_at
     , answer_at<>answer_history_at answer_notification_is_edit
from db.answer_notification natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) h natural join (select answer_id,answer_at from db.answer) q
where account_id=get_account_id();
--
create view answer with (security_barrier) as
select answer_id,question_id,question_title,question_room_id,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning,community_mid_shade,community_dark_shade,community_warning_color
from (select distinct answer_id from answer_notification) n
     natural join db.answer
     natural join (select community_id,question_id,question_title,question_room_id from db.question) q
     natural join api._community
     natural join db.community;
--
create view answer_flag_notification with (security_barrier) as
select answer_id,answer_flag_history_id,answer_flag_notification_at,question_id,question_title,account_id,community_name,community_rgb_mid,community_rgb_dark,community_rgb_warning,community_mid_shade,community_dark_shade,community_warning_color
from (select answer_flag_history_id,answer_flag_notification_at from db.answer_flag_notification where account_id=get_account_id()) f
     natural join db.answer_flag_history
     natural join (select answer_id,question_id from db.answer) a
     natural join (select community_id,question_id,question_title from db.question) q
     natural join api._community
     natural join db.community;
--
create view system_notification with (security_barrier) as
select system_notification_id,system_notification_at,system_notification_message,community_name
     , coalesce(community_mid_shade,'\xf8f8f8') community_mid_shade
     , coalesce(community_dark_shade,'\x000000') community_dark_shade
     , coalesce(community_warning_color,'\x990000') community_warning_color
     , '248,248,248' community_rgb_mid
     , '0,0,0' community_rgb_dark
     , '153,0,0' community_rgb_warning
from db.system_notification n left join db.community c on c.community_id = n.system_notification_community_id
where system_notification_dismissed_at is null and account_id=get_account_id();
--
create view one with (security_barrier) as
select room_id
     , (room_type='public' or x.account_id is not null) room_can_chat
from db.room r natural join db.community
     natural left join db.writer x
where room_id=get_room_id();
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
create function dismiss_question(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  with d as (delete from question_notification where question_history_id=id and account_id=get_account_id() returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_question_flag(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  with d as (delete from question_flag_notification
             where question_flag_history_id in(select question_flag_history_id from question_flag_history where question_id=(select question_id from question_flag_history where question_flag_history_id=id))
                   and account_id=get_account_id() returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_answer(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  with d as (delete from answer_notification where answer_history_id=id and account_id=get_account_id() returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_answer_flag(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  with d as (delete from answer_flag_notification
             where answer_flag_history_id in(select answer_flag_history_id from answer_flag_history where answer_id=(select answer_id from answer_flag_history where answer_flag_history_id=id))
                   and account_id=get_account_id() returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_system(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  update system_notification set system_notification_dismissed_at = current_timestamp where system_notification_dismissed_at is null and system_notification_id=id and account_id=get_account_id();
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
