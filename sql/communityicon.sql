create schema communityicon;
grant usage on schema communityicon to ta_get;
set local search_path to communityicon,api,pg_temp;
--
--
create view one with (security_barrier) as select community_id,community_dark_shade from db.community where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
--
--
revoke all on all functions in schema communityicon from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='communityicon' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='communityicon' and proname!~'^_' );
end$$;
