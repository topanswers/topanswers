create schema activerooms;
grant usage on schema activerooms to ta_get;
set local search_path to activerooms,api,pg_temp;
--
--
create view room with (security_barrier) as
with r as (select room_id from db.listener where account_id=get_account_id() union select room_id from db.room where community_id=get_community_id() and room_question_id is null)
   , w as (select room_id,room_question_id,participant_latest_chat_at
                , coalesce(participant_chat_count,0) participant_chat_count
                , coalesce(listener_latest_read_chat_id,0) listener_latest_read_chat_id
                , case when room_can_listen and l.account_id is not null
                       then (select count(1) from (select 1 from db.chat c where c.room_id=l.room_id and c.chat_id>coalesce(l.listener_latest_read_chat_id,0) limit 99) z)
                       else 0 end listener_unread
           from r natural join db.room
                natural left join (select * from db.listener where account_id=get_account_id()) l
                natural left join (select * from db.participant where account_id=get_account_id()) p)
select room_id,room_derived_name,room_question_id,room_image_url
      ,community_name,community_display_name,community_rgb_light
      ,listener_unread,listener_latest_read_chat_id
      ,participant_chat_count,participant_latest_chat_at
from w natural join api._room natural join api._community natural join db.community
where (community_id=get_community_id() and room_question_id is null) or listener_unread>0 or participant_latest_chat_at+make_interval(hours=>60+least(participant_chat_count,182)*12)>current_timestamp;
--
create view one with (security_barrier) as select community_name,community_language from api._community natural join db.community where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
--
create function room(integer[])
                returns table (room_id integer
                              ,room_derived_name text
                              ,room_question_id integer
                              ,room_image_url text
                              ,community_name text
                              ,community_display_name text
                              ,community_rgb_light text
                              ,listener_unread integer
                              ,listener_latest_read_chat_id bigint
                              ,participant_chat_count integer
                              ,participant_latest_chat_at timestamptz
                              ) language sql security definer set search_path=db,api,pg_temp as $$
  with r as (select room_id, m.room_id is not null room_is_mandatory
             from (select room_id from db.pinner where account_id=get_account_id() union select room_id from db.room where community_id=get_community_id() and room_question_id is null) m
                  full join (select room_id from db.listener where account_id=get_account_id()) l using(room_id))
     , w as (select room_id,room_is_mandatory,room_question_id,participant_latest_chat_at
                  , coalesce(participant_chat_count,0) participant_chat_count
                  , coalesce(listener_latest_read_chat_id,0) listener_latest_read_chat_id
                  , case when room_can_listen and l.room_id is not null
                         then (select count(1) from (select 1 from db.chat c where c.room_id=l.room_id and c.chat_id>coalesce(l.listener_latest_read_chat_id,0) limit 99) z)
                         else 0 end listener_unread
             from r natural join db.room
                  natural left join (select room_id,greatest(listener_latest_read_chat_id,chat_id) listener_latest_read_chat_id
                                     from db.listener natural left join (select room_id, chat_id from (unnest($1) chat_id natural join db.chat) z) z
                                     where account_id=get_account_id()) l
                  natural left join (select room_id,participant_latest_chat_at,participant_chat_count from db.participant where account_id=get_account_id()) p)
  select room_id,room_derived_name,room_question_id,room_image_url
        ,community_name,community_display_name,community_rgb_light
        ,listener_unread::integer,listener_latest_read_chat_id
        ,participant_chat_count,participant_latest_chat_at
  from w natural join api._room natural join api._community natural join db.community
  where room_is_mandatory or listener_unread>0 or participant_latest_chat_at+make_interval(hours=>60+least(participant_chat_count,182)*12)>current_timestamp;
$$;
--
--
revoke all on all functions in schema activerooms from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='activerooms' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='activerooms' and proname!~'^_' );
end$$;
