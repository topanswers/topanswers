create schema activeusers;
grant usage on schema activeusers to get,ta_get,post,ta_post;
set local search_path to activeusers,api,pg_temp;
--
--
create view account with (security_barrier) as
select account_id,account_derived_name,account_image_url,participant_latest_chat_at,communicant_votes
from db.room natural join db.participant natural join api._account natural join db.communicant
where room_id=get_room_id()
      and (room_type='private' or (room_type='gallery' and participant_latest_chat_at>(current_timestamp-'90d'::interval)) or participant_latest_chat_at>(current_timestamp-'14d'::interval));
--
create view one with (security_barrier) as select get_account_id() account_id, community_language from api._community where community_id=get_community_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select * from api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema activeusers from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get,ta_get;', E'\n') from pg_views where schemaname='activeusers' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get,ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='activeusers' and proname!~'^_' );
end$$;
