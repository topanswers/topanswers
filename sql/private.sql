create schema private;
grant usage on schema private to get;
set local search_path to private,api,pg_temp;
--
--
create view community with (security_barrier) as select community_name,community_display_name from db.community where community_type='private';
--
create view one with (security_barrier) as
select account_id, account_image_url, '/image?hash='||encode(one_image_hash,'hex') one_image_url from db.one cross join api._account where account_id = get_account_id();
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
