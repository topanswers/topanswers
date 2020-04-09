create schema navigation;
grant usage on schema navigation to get;
set local search_path to navigation,api,pg_temp;
--
--
create view environment with (security_barrier) as select environment_name from db.environment;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_my_votes,community_ordinal,community_about_question_id
from api._community natural join db.community
     natural left join (select community_id,account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='public' or account_id is not null;
--
create view one with (security_barrier) as
select community_id,community_name,community_display_name,community_language,community_rgb_dark,community_rgb_mid,community_rgb_light
     , coalesce(account_is_dev,false) account_is_dev
from db.community natural join api._community
     natural left join (select account_id,account_is_dev from db.login natural join db.account where login_uuid=get_login_uuid()) a
where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_room($1,(select community_room_id from db.community where community_name=$2));$$;
--
--
revoke all on all functions in schema navigation from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='navigation' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='navigation' and proname!~'^_' );
end$$;
