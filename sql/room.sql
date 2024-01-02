create schema room;
grant usage on schema room to ta_get,ta_post;
set local search_path to room,api,pg_temp;
--
--
create view one with (security_barrier) as
select account_id,account_is_dev,account_image_url
      ,community_id,community_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
      ,community_image_url
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
      ,room_id,room_name,room_image_url
     , room_image_hash is not null room_has_image
     , (select question_id from db.question where question_room_id=room_id) question_id
from db.room natural join api._room natural join api._community natural join db.community
     natural join (select account_id,account_is_dev,account_image_url from db.login natural join db.account natural join api._account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select * from api.login_room($1,$2);$$;
--
--
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='room' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='room' and proname!~'^_' );
end$$;
--
--
create function change_name(nname text) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  select raise_error('not authorised') from room.one where not account_is_dev;
  select raise_error(400,'invalid room name') where nname is not null and not nname~'^[0-9[:alpha:]/][-'' ,.0-9[:alpha:]/<>+]{1,25}[0-9[:alpha:]/<>]$';
  update room set room_name = nname where room_id=get_room_id();
$$;
--
create function change_image(bytea) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  select raise_error('not authorised') from room.one where not account_is_dev;
  update room set room_image_hash = $1 where room_id=get_room_id();
$$;
--
create function mute() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  delete from listener where account_id=get_account_id() and room_id=get_room_id();
$$;
--
create function listen() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  select _ensure_communicant(get_account_id(),get_community_id());
  insert into listener(account_id,room_id,listener_latest_read_chat_id) select get_account_id(),get_room_id(),max(chat_id) from chat where room_id=get_room_id() on conflict do nothing;
$$;
--
create function pin() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  select _ensure_communicant(get_account_id(),get_community_id());
  insert into pinner(account_id,room_id) values (get_account_id(),get_room_id()) on conflict do nothing;
$$;
--
create function unpin() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select raise_error('access denied') where get_account_id() is null;
  delete from pinner where account_id=get_account_id() and room_id=get_room_id();
$$;
--
--
revoke all on all functions in schema room from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_post;', E'\n') from pg_views where schemaname='room' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='room' and proname!~'^_' );
end$$;
