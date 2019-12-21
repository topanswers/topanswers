create schema community;
grant usage on schema community to get;
set local search_path to community,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_name from db.account;
--
--
--
revoke all on all functions in schema community from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='community' and viewname!~'^_');
end$$;
