create schema error;
grant usage on schema error to post;
set local search_path to error,api,pg_temp;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
--
create function new(ua text, err text) returns void language sql security definer set search_path=db,api,error,pg_temp as $$
  insert into error(error_ua,error_text) values(ua,err);
$$;
--
--
revoke all on all functions in schema community from public;
do $$
begin
  --execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='error' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='error' and proname!~'^_' );
end$$;
