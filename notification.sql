create schema notification;
grant usage on schema notification to get;
set local search_path to notification,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_name from db.account;
create view chat_flag with (security_barrier) as select chat_id,chat_flag_at from db.chat_flag where account_id=get_account_id();
create view chat_star with (security_barrier) as select chat_id,chat_star_at from db.chat_star where account_id=get_account_id();
create view chat_notification with (security_barrier) as select chat_id,chat_notification_at from db.chat_notification where account_id=get_account_id();
--
create view chat with (security_barrier) as
select room_id,community_id,chat_id,account_id,chat_at,chat_change_id,chat_reply_id,chat_reply_account_id,chat_markdown,community_name,community_mid_shade,community_dark_shade,question_id
     , chat_reply_account_id=get_account_id() chat_reply_account_is_me
     , coalesce(question_title,room_name) chat_room_name 
     , question_id is not null chat_is_question_room
     , (select count(1) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(1) from db.chat_star where chat_id=chat.chat_id) chat_star_count
     , (select count(1) from db.chat_history where chat_id=chat.chat_id)>1 chat_has_history
from chat_notification natural join db.chat natural join db.room natural join db.community
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
select question_id,question_title,question_room_id,community_name,community_mid_shade,community_dark_shade
from question_notification natural join db.question natural join db.community;
--
create view question_flag_notification with (security_barrier) as
select question_flag_history_id,question_flag_notification_at,question_id,question_title,account_id,community_name,community_mid_shade,community_dark_shade
from (select question_flag_history_id,question_flag_notification_at from db.question_flag_notification where account_id=get_account_id()) f
     natural join db.question_flag_history
     natural left join (select community_id,question_id,question_title from db.question) q
     natural join db.community;
--
create view answer_notification with (security_barrier) as
select answer_history_id,answer_id,answer_notification_at
     , answer_at<>answer_history_at answer_notification_is_edit
from db.answer_notification natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) h natural join (select answer_id,answer_at from db.answer) q
where account_id=get_account_id();
--
create view answer with (security_barrier) as
select answer_id,question_id,question_title,question_room_id,community_name,community_mid_shade,community_dark_shade
from answer_notification natural join db.answer natural left join (select community_id,question_id,question_title,question_room_id from db.question) q natural join db.community;
--
create view answer_flag_notification with (security_barrier) as
select answer_id,answer_flag_history_id,answer_flag_notification_at,question_id,question_title,account_id,community_name,community_mid_shade,community_dark_shade
from (select answer_flag_history_id,answer_flag_notification_at from db.answer_flag_notification where account_id=get_account_id()) f
     natural join db.answer_flag_history
     natural left join (select community_id,question_id,question_title from db.question) q
     natural join db.community;
--
create view one with (security_barrier) as
select room_id
     , (room_type='public' or x.account_id is not null) room_can_chat
     , encode(community_dark_shade,'hex') colour_dark
from db.room r natural join db.community
     natural left join db.account_room_x x
where room_id=get_room_id();
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema notification from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='notification' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='notification' and proname!~'^_' );
end$$;
