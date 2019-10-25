/*
\i ~/git/world.sql
*/
begin;
--
drop schema if exists world cascade;
drop role if exists world;
create user world;
create schema world;
grant usage on schema world to world;
set local schema 'world';
--
create function _error(text) returns void language plpgsql as $$begin raise exception '%', $1 using errcode='H0403'; end;$$;
--
create view community as
select community_id,community_name,community_room_id,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color
from db.community
where community_name<>'meta' or current_setting('custom.uuid',true)::uuid is not null;
--
create view login as select account_id, login_uuid=current_setting('custom.uuid',true)::uuid login_is_me from db.login natural join db.account;
create view account as select account_id,account_name,account_image, account_id=(select account_id from login where login_is_me) account_is_me from db.account;
create view room as select community_id,room_id,room_name from db.room where room_type<>'private' or exists (select * from db.room_account_x natural join account where account_is_me);
create view chat as select community_id,room_id,account_id,chat_id,chat_reply_id,chat_at,chat_change_id,chat_change_at,chat_markdown from db.chat natural join room;
--
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,world,pg_temp as $$
declare
  rid integer;
  cid integer;
begin
  insert into room(community_id) values(0) returning room_id into rid;
  insert into community(community_name,community_room_id) values(cname,rid) returning community_id into cid;
  update room set community_id=cid where room_id=rid;
  return cid;
end$$;
--
create function new_chat(luuid uuid, roomid integer, msg text, replyid integer) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select * from room where room_id=roomid);
  select _error('not authorised to chat in this room') where (select room_type<>'public' from room where room_id=roomid) and not exists (select * from room_account_x natural join login where room_id=roomid and login_uuid=luuid);
  select _error('message too long') where length(msg)>500;
  --
  insert into chat(community_id,room_id,account_id,chat_markdown,chat_reply_id) 
  select community_id,roomid,(select account_id from login natural join account where login_uuid=luuid),msg,replyid from room where room_id=roomid returning chat_id;
$$;
--
create function new_account(luuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  with a as (insert into account default values returning account_id)
  insert into login(account_id,login_uuid) select account_id,luuid from a returning account_id;
$$;
--
create function change_account_name(nname text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid username') where nname is not null and not nname~'^[A-Za-zÀ-ÖØ-öø-ÿ][ 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$';
  update account set account_name = nname where account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
$$;
--
create function change_account_image(image bytea) returns void language sql security definer set search_path=db,world,pg_temp as $$
  update account set account_image = image where account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
$$;
--
revoke all on all functions in schema world from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to world;', E'\n') from pg_views where schemaname='world' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to world;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='world' and proname!~'^_' );
end$$;
--
commit;
