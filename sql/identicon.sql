create schema identicon;
grant usage on schema identicon to get;
set local search_path to identicon,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_change_at from db.account;
--
--
revoke all on all functions in schema identicon from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='identicon' and viewname!~'^_');
end$$;
