create schema pinnedrooms;
grant usage on schema pinnedrooms to ta_get;
set local search_path to pinnedrooms,api,pg_temp;
--
--
create view room with (security_barrier) as
select room_id,room_derived_name,room_question_id,room_image_url,community_name,community_display_name,community_rgb_light,participant_chat_count,participant_latest_chat_at
from db.pinner natural join db.room natural join api._room natural join api._community natural join db.community
     natural left join db.participant
where account_id=get_account_id();
--
create view one with (security_barrier) as select community_name from db.community where community_id=get_community_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema pinnedrooms from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='pinnedrooms' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='pinnedrooms' and proname!~'^_' );
end$$;
