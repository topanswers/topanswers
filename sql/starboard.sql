create schema starboard;
grant usage on schema starboard to get;
set local search_path to starboard,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_derived_name account_name from api._account;
--
create view chat with (security_barrier) as
select chat_id,account_id,account_image_url,chat_at,chat_change_id,chat_reply_id,chat_reply_account_id,chat_reply_account_name,chat_markdown
     , chat_reply_account_id=get_account_id() chat_reply_account_is_me
     , account_derived_name account_name
     , account_id=get_account_id() chat_account_is_me
     , (select count(1) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(1) from db.chat_star where chat_id=chat.chat_id) chat_star_count
     , exists (select 1 from db.chat_flag where chat_id=chat.chat_id and account_id=get_account_id()) chat_i_flagged
     , exists (select 1 from db.chat_star where chat_id=chat.chat_id and account_id=get_account_id()) chat_i_starred
     , to_char(chat_at,'YYYY-MM-DD"T"HH24:MI:SS"Z"') chat_at_iso
     , (select count(1) from db.chat_history where chat_id=chat.chat_id)>1 chat_has_history
from (select chat_id,sum(1/(gap+1)) weight
      from (select chat_id,extract('epoch' from current_timestamp-chat_star_at) gap from db.chat_star where room_id=get_room_id() order by chat_star_at desc limit 100) s
      group by chat_id) s
     natural join db.chat
     natural join db.account
     natural join api._account
     natural left join (select chat_id chat_reply_id, account_id chat_reply_account_id, account_derived_name chat_reply_account_name from db.chat natural join api._account) c
order by weight desc limit 30;
--
create view one with (security_barrier) as
select room_id
     , (room_type='public' or x.room_id is not null) room_can_chat
from db.room r natural join db.community
     natural left join (select room_id from db.writer where account_id=get_account_id()) x
where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema starboard from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='starboard' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='starboard' and proname!~'^_' );
end$$;
