create schema roomicon;
grant usage on schema roomicon to get;
set local search_path to roomicon,api,pg_temp;
--
--
create view one with (security_barrier) as select room_id,room_image from db.room where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select * from api.login_room($1,$2);$$;
--
--
revoke all on all functions in schema roomicon from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='roomicon' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='roomicon' and proname!~'^_' );
end$$;
