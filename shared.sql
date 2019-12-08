/*
\i ~/git/shared.sql
*/
begin;
--
revoke usage on schema x_pg_trgm from get;
drop schema if exists get cascade;
drop role if exists get;
--
revoke usage on schema x_pg_trgm from post;
drop schema if exists post cascade;
drop role if exists post;
--
revoke usage on schema x_pg_trgm from shared;
drop schema if exists shared cascade;
drop role if exists shared;
create user shared;
create schema shared;
grant usage on schema shared to shared;
grant usage on schema x_pg_trgm to shared;
set local schema 'shared';
--
--
create view community with (security_barrier) as
select community.*
     , 1+trunc(log(greatest(account_community_votes,0)+1)) community_my_power
from db.community
     natural left join (select community_id,account_community_votes from db.account_community where account_id=current_setting('custom.account_id',true)::integer) a
     natural left join (select community_id, account_id from db.member where account_id=current_setting('custom.account_id',true)::integer) m
where community_type='public' or account_id is not null;
--
create view room with (security_barrier) as
select room.*
     , current_setting('custom.account_id',true)::integer is not null and (room_type='public' or account_id is not null) room_can_chat
from db.room natural join shared.community natural left join (select * from db.account_room_x where account_id=current_setting('custom.account_id',true)::integer) a
where room_type<>'private' or account_id is not null;
--
--
create function login(luuid uuid) returns boolean language plpgsql security definer set search_path=db,shared,pg_temp as $$
declare e boolean = exists(select 1 from login where login_uuid=luuid);
begin
  if e then
    perform set_config('custom.uuid',luuid::text,false);
    perform set_config('custom.account_id',account_id::text,false) from login where login_uuid=luuid;
  end if;
  return e;
end;
$$;
--
--
revoke all on all functions in schema shared from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to shared;', E'\n') from pg_views where schemaname='shared' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to shared;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='shared' and proname!~'^_' );
end$$;
--
\i ~/git/get.sql
\i ~/git/post.sql
--
commit;
