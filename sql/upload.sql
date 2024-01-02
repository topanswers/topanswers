create schema upload;
grant usage on schema upload to ta_get,ta_post;
set local search_path to upload,api,pg_temp;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
--
--
revoke all on all functions in schema upload from public;
do $$
begin
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='upload' and proname!~'^_' );
end$$;
