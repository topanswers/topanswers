create schema poll;
grant usage on schema poll to get;
set local search_path to poll,api,pg_temp;
--
--
create view one with (security_barrier) as
with w as (select get_room_id() room_id, get_community_id() community_id)
   , c as (select get_community_id() community_id union all select community_from_id from db.syndication where account_id=get_account_id() and community_to_id=get_community_id())
select account_notification_id
     , (select max(chat_id) from db.chat natural join w) chat_max_id
     , (select max(chat_change_id) from db.chat natural join w) chat_max_change_id
     , (select max(question_poll_major_id) from db.question natural join c) question_max_poll_major_id
     , (select max(question_poll_minor_id) from db.question natural join w) question_max_poll_minor_id
     , (select coalesce(max(room_latest_chat_id),0) from db.listener natural join db.room where account_id=get_account_id() and room_latest_chat_id is not null) chat_active_room_max_id
from db.account where account_id=get_account_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema poll from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='poll' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='poll' and proname!~'^_' );
end$$;
