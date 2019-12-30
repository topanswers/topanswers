create schema chat_history;
grant usage on schema chat_history to get;
set local search_path to chat_history,api,pg_temp;
--
--
create view history as
select chat_history_id,chat_history_markdown
     , to_char(chat_history_at,'YYYY-MM-DD HH24:MI:SS') chat_history_at
     , lag(chat_history_markdown) over (order by chat_history_at) prev_markdown
     , row_number() over (order by chat_history_at) rn
from db.chat_history
where chat_id=get_chat_id();
--
create view one with (security_barrier) as
select chat_id
      ,room_id,room_name
      ,account_id
      ,community_id,community_name,community_display_name,community_code_language
     , encode(community_dark_shade,'hex') colour_dark
     , encode(community_mid_shade,'hex') colour_mid
     , encode(community_light_shade,'hex') colour_light
     , encode(community_highlight_color,'hex') colour_highlight
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select chat_id,community_id,room_id from db.chat where chat_id=get_chat_id()) a
     natural join db.room
     natural join db.community
     natural join (select account_id from db.login natural join db.account where login_uuid=get_login_uuid()) ac
     natural left join db.communicant;
--
--
create function login_chat(uuid,integer) returns boolean language sql security definer as $$select api.login_chat($1,$2);$$;
--
--
revoke all on all functions in schema chat_history from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='chat_history' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='chat_history' and proname!~'^_' );
end$$;
