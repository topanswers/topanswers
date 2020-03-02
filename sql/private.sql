create schema private;
grant usage on schema private to get;
set local search_path to private,api,pg_temp;
--
--
create view community with (security_barrier) as
select community_name,community_display_name
from db.community natural left join (select community_id, account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='private' and account_id is null;
--
create view one with (security_barrier) as
select (select account_id from db.login natural join db.account where login_uuid=get_login_uuid()) account_id;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
--
--
revoke all on all functions in schema private from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='private' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='private' and proname!~'^_' );
end$$;
