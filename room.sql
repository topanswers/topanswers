create schema room;
grant usage on schema room to get,post;
set local search_path to room,api,pg_temp;
--
--
create view one with (security_barrier) as
select account_id,account_is_dev
      ,community_id,community_name
     , encode(community_dark_shade,'hex') colour_dark
     , encode(community_mid_shade,'hex') colour_mid
     , encode(community_light_shade,'hex') colour_light
     , encode(community_highlight_color,'hex') colour_highlight
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
      ,room_id,room_name
     , room_image is not null room_has_image
     , (select question_id from db.question where question_room_id=room_id) question_id
from db.room natural join db.community
     natural join (select account_id,account_is_dev from db.login natural join db.account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select * from api.login_room($1,$2);$$;
--
--
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='room' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='room' and proname!~'^_' );
end$$;
--
--
create function change_name(nname text) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('not authorised') from room.one where not account_is_dev;
  select _error(400,'invalid room name') where nname is not null and not nname~'^[0-9[:alpha:]][-'' ,.0-9[:alpha:]]{1,25}[0-9[:alpha:]]$';
  update room set room_name = nname where room_id=get_room_id();
$$;
--
create function change_image(image bytea) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('not authorised') from room.one where not account_is_dev;
  update room set room_image = image where room_id=get_room_id();
$$;
--
create function read() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  update room_account_x set room_account_x_latest_read_chat_id = (select max(chat_id) from chat where room_id=get_room_id()) where room_id=get_room_id() and account_id=get_account_id();
$$;
--
--
revoke all on all functions in schema room from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='room' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='room' and proname!~'^_' );
end$$;
