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
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color
from db.community
where community_name<>'meta' or current_setting('custom.account_id',true)::integer is not null;
--
create view login with (security_barrier) as select account_id,login_resizer_percent, true as login_is_me from db.login where login_uuid=current_setting('custom.uuid',true)::uuid;
create view account with (security_barrier) as select account_id,account_name,account_image,account_change_id, account_id=current_setting('custom.account_id',true)::integer account_is_me from db.account;
create view my_account with (security_barrier) as select account_id,account_name,account_image,account_uuid from db.account where account_id=current_setting('custom.account_id',true)::integer;
--
create view room with (security_barrier) as
select community_id,room_id,room_name,room_latest_change_id,room_latest_change_at, room_type='public' or account_id is not null room_can_chat
from db.room natural left outer join (select * from db.account_room_x where account_id=current_setting('custom.account_id',true)::integer) z
where room_type<>'private' or account_id is not null;
--
create view room_account_x with (security_barrier) as
select community_id,room_id,account_id,room_account_x_latest_chat_at from db.room_account_x natural join world.room where room_account_x_latest_chat_at>(current_timestamp-'7d'::interval);
--
create view chat with (security_barrier) as
select community_id,room_id,account_id,chat_id,chat_reply_id,chat_at,chat_change_id,chat_change_at,chat_markdown
     , (select count(*) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(*) from db.chat_star where chat_id=chat.chat_id) chat_star_count
from db.chat natural join room;
--
create view chat_notification with (security_barrier) as select community_id,room_id,chat_id,chat_notification_at from db.chat_notification natural join account where account_is_me;
create view chat_flag with (security_barrier) as select community_id,room_id,chat_id,chat_flag_at from db.chat_flag natural join account where account_is_me;
create view chat_star with (security_barrier) as select community_id,room_id,chat_id,chat_star_at from db.chat_star natural join account where account_is_me;
create view chat_year with (security_barrier) as select community_id,room_id,chat_year,chat_year_count from db.chat_year;
create view chat_month with (security_barrier) as select community_id,room_id,chat_year,chat_month,chat_month_count from db.chat_month;
create view chat_day with (security_barrier) as select community_id,room_id,chat_year,chat_month,chat_day,chat_day_count from db.chat_day;
create view chat_hour with (security_barrier) as select community_id,room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count from db.chat_hour;
--
create function login(luuid uuid) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('login uuid does not exist') where not exists(select * from login where login_uuid=luuid);
  select set_config('custom.uuid',luuid::text,false);
  select set_config('custom.account_id',account_id::text,false) from login where login_uuid=luuid;
$$;
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
create function new_chat(roomid integer, msg text, replyid integer, pingids integer[]) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select * from room where room_id=roomid);
  select _error('access denied') where not exists(select * from world.room where room_id=roomid and room_can_chat);
  select _error('message too long') where length(msg)>5000;
  --
  delete from chat_notification where chat_id=replyid and account_id=current_setting('custom.account_id',true)::integer;
  --
  insert into chat_year(community_id,room_id,chat_year,chat_year_count)
  select community_id,roomid,extract('year' from current_timestamp),1 from room where room_id=roomid on conflict on constraint chat_year_pkey do update set chat_year_count = chat_year.chat_year_count+1;
  --
  insert into chat_month(community_id,room_id,chat_year,chat_month,chat_month_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_month_pkey do update set chat_month_count = chat_month.chat_month_count+1;
  --
  insert into chat_day(community_id,room_id,chat_year,chat_month,chat_day,chat_day_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_day_pkey do update set chat_day_count = chat_day.chat_day_count+1;
  --
  insert into chat_hour(community_id,room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),extract('hour' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_hour_pkey do update set chat_hour_count = chat_hour.chat_hour_count+1;
  --
  with i as (insert into chat(community_id,room_id,account_id,chat_markdown,chat_reply_id) 
             select community_id,roomid,current_setting('custom.account_id',true)::integer,msg,replyid from room where room_id=roomid returning community_id,room_id,chat_id)
     , n as (insert into chat_notification(community_id,room_id,chat_id,account_id)
             select community_id,room_id,chat_id,(select account_id from chat where chat_id=replyid) from i where replyid is not null and not (select account_is_me from chat natural join world.account where chat_id=replyid))
     , p as (insert into chat_notification(community_id,room_id,chat_id,account_id)
             select community_id,room_id,chat_id,account_id
             from i cross join (select account_id from world.account where account_id in (select * from unnest(pingids) except select account_id from chat where chat_id=replyid) and not account_is_me) z)
     , a as (insert into room_account_x(community_id,room_id,account_id)
             select community_id,roomid,current_setting('custom.account_id',true)::integer
             from room
             where room_id=roomid
             on conflict on constraint room_account_x_pkey do update set room_account_x_latest_chat_at=default)
     , r as (update room set room_latest_change_id = default, room_latest_change_at = default where room_id=(select room_id from i))
  select chat_id from i;
$$;
--
create function new_chat(luuid uuid, roomid integer, msg text, replyid integer, pingids integer[]) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select * from room where room_id=roomid);
  select _error('access denied') where not exists(select * from world.room where room_id=roomid and room_can_chat);
  select _error('message too long') where length(msg)>5000;
  --
  delete from chat_notification where chat_id=replyid and account_id=current_setting('custom.account_id',true)::integer;
  --
  insert into chat_year(community_id,room_id,chat_year,chat_year_count)
  select community_id,roomid,extract('year' from current_timestamp),1 from room where room_id=roomid on conflict on constraint chat_year_pkey do update set chat_year_count = chat_year.chat_year_count+1;
  --
  insert into chat_month(community_id,room_id,chat_year,chat_month,chat_month_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_month_pkey do update set chat_month_count = chat_month.chat_month_count+1;
  --
  insert into chat_day(community_id,room_id,chat_year,chat_month,chat_day,chat_day_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_day_pkey do update set chat_day_count = chat_day.chat_day_count+1;
  --
  insert into chat_hour(community_id,room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count)
  select community_id,roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),extract('hour' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_hour_pkey do update set chat_hour_count = chat_hour.chat_hour_count+1;
  --
  with i as (insert into chat(community_id,room_id,account_id,chat_markdown,chat_reply_id) 
             select community_id,roomid,(select account_id from login natural join account where login_uuid=luuid),msg,replyid from room where room_id=roomid returning community_id,room_id,chat_id)
     , n as (insert into chat_notification(community_id,room_id,chat_id,account_id)
             select community_id,room_id,chat_id,(select account_id from chat where chat_id=replyid) from i where replyid is not null and not (select account_is_me from chat natural join world.account where chat_id=replyid))
     , p as (insert into chat_notification(community_id,room_id,chat_id,account_id)
             select community_id,room_id,chat_id,account_id from i cross join (select account_id from world.account where account_id in (select * from unnest(pingids) except select account_id from chat where chat_id=replyid) and not account_is_me) z)
     , a as (insert into room_account_x(community_id,room_id,account_id)
             select community_id,roomid,(select account_id from login natural join account where login_uuid=luuid)
             from room
             where room_id=roomid
             on conflict on constraint room_account_x_pkey do update set room_account_x_latest_chat_at=default)
     , r as (update room set room_latest_change_id = default, room_latest_change_at = default where room_id=(select room_id from i))
  select chat_id from i;
$$;
--
create function dismiss_notification(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  delete from chat_notification where chat_id=id and account_id=(select account_id from world.account where account_is_me);
$$;
--
create function new_account(luuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  with a as (insert into account default values returning account_id)
  insert into login(account_id,login_uuid) select account_id,luuid from a returning account_id;
$$;
--
create function link_account(luuid uuid, pn bigint) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid pin') where not exists (select * from pin where pin_number=pn);
  insert into login(account_id,login_uuid) select account_id,luuid from pin where pin_number=pn and pin_at>current_timestamp-'1 min'::interval returning account_id;
$$;
--
create function recover_account(luuid uuid, auuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid recovery key') where not exists (select * from account where account_uuid=auuid);
  insert into login(account_id,login_uuid) select account_id,luuid from account where account_uuid=auuid returning account_id;
$$;
--
create function change_account_name(nname text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid username') where nname is not null and not nname~'^[A-Za-zÀ-ÖØ-öø-ÿ][ 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$';
  update account set account_name = nname, account_change_id = default where account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
$$;
--
create function change_account_image(image bytea) returns void language sql security definer set search_path=db,world,pg_temp as $$
  update account set account_image = image, account_change_id = default  where account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
$$;
--
create function change_resizer(perc integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid percent') where perc<0 or perc>100;
  update login set login_resizer_percent = perc where login_uuid=current_setting('custom.uuid',true)::uuid;
$$;
--
create function authenticate_pin(num bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  delete from pin where pin_number=num;
  insert into pin(pin_number,account_id) select num,account_id from world.account where account_is_me;
$$;
--
create function set_chat_flag(cid bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('cant flag own message') where exists(select * from chat where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('already flagged') where exists(select * from chat_flag where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('access denied') where not exists(select * from chat natural join world.room where chat_id=cid and room_can_chat);
  update room set room_latest_change_id = default where room_id=(select room_id from chat where chat_id=cid);
  insert into chat_flag(community_id,room_id,chat_id,account_id) select community_id,room_id,chat_id,(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid) from chat where chat_id=cid;
$$;
--
create function remove_chat_flag(cid bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not already flagged') where not exists(select * from chat_flag where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('access denied') where not exists(select * from chat natural join world.room where chat_id=cid and room_can_chat);
  update room set room_latest_change_id = default where room_id=(select room_id from chat where chat_id=cid);
  delete from chat_flag where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
$$;
--
create function set_chat_star(cid bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('cant star own message') where exists(select * from chat where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('already starred') where exists(select * from chat_star where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('access denied') where not exists(select * from chat natural join world.room where chat_id=cid and room_can_chat);
  update room set room_latest_change_id = default where room_id=(select room_id from chat where chat_id=cid);
  insert into chat_star(community_id,room_id,chat_id,account_id) select community_id,room_id,chat_id,(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid) from chat where chat_id=cid;
$$;
--
create function remove_chat_star(cid bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not already starred') where not exists(select * from chat_star where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid));
  select _error('access denied') where not exists(select * from chat natural join world.room where chat_id=cid and room_can_chat);
  update room set room_latest_change_id = default where room_id=(select room_id from chat where chat_id=cid);
  delete from chat_star where chat_id=cid and account_id=(select account_id from login where login_uuid=current_setting('custom.uuid',true)::uuid);
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
