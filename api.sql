/*
\i ~/git/api.sql
*/
begin;
--
drop schema if exists transcript cascade;
drop schema if exists chat cascade;
drop schema if exists notification cascade;
drop schema if exists api cascade;
create schema api;
grant usage on schema api to get;
grant usage on schema api to post;
set local search_path to api,pg_temp;
--
--
create function _error(integer,text) returns void language plpgsql as $$begin raise exception '%', $2 using errcode='H0'||$1; end;$$;
create function _error(text) returns void language sql as $$select _error(403,$1);$$;
--
create function get_login_uuid() returns uuid stable language sql security definer as $$select current_setting('custom.uuid',true)::uuid;$$;
--
create function _get_id(text) returns integer stable language sql security definer set search_path=db,api,pg_temp as $$
  with w as (select account_encryption_key from login natural join account where login_uuid=get_login_uuid() union all select one_encryption_key from one where get_login_uuid() is null)
  select x_pgcrypto.pgp_sym_decrypt(current_setting('custom.'||$1||'_id',true)::bytea,(select account_encryption_key from w)::text||current_setting('custom.timestamp',true))::integer
$$;
--
create function get_account_id() returns integer stable language sql security definer as $$select api._get_id('account'::text);$$;
create function get_room_id() returns integer stable language sql security definer as $$select api._get_id('room'::text);$$;
create function get_community_id() returns integer stable language sql security definer as $$select api._get_id('community'::text);$$;
--
create function _set_id(text,integer) returns void stable language sql security definer set search_path=db,api,pg_temp as $$
  with w as (select account_encryption_key from login natural join account where login_uuid=get_login_uuid() union all select one_encryption_key from one where get_login_uuid() is null)
  select set_config('custom.'||$1||'_id',x_pgcrypto.pgp_sym_encrypt($2::text,(select account_encryption_key from w)::text||current_setting('custom.timestamp',true))::text,false)
$$;
--
create function _set_account_id() returns void stable language sql security definer set search_path=db,api,pg_temp as $$
  select _set_id('account'::text,account_id) from login natural join (select account_id,account_uuid,account_encryption_key from account) a where login_uuid=get_login_uuid();
$$;
--
create function login(uuid uuid) returns uuid language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.uuid',login_uuid::text,false) from login where login_uuid=uuid;
  select set_config('custom.timestamp',current_timestamp::text,false);
  select _set_account_id();
  select get_login_uuid();
$$;
--
create function login_community(inout uuid uuid
                              , in cid integer
                              , out community_name text
                              , out community_code_language text
                              , out my_community_regular_font_name text
                              , out my_community_monospace_font_name text
                              , out colour_dark text
                              , out colour_mid text
                              , out colour_light text
                              , out colour_highlight text) language sql security definer set search_path=db,api,chat,pg_temp as $$
  --
  select login(uuid);
  --
  select _error('invalid community')
  where not exists (select 1 from community c where community_id=cid and (community_type='public' or exists (select 1 from member m where m.community_id=c.community_id and m.account_id=get_account_id())));
  --
  select _set_id('community',community_id) from community where community_id=cid;
  --
  select get_login_uuid()
       , community_name
       , community_code_language
       , regular_font_name
       , monospace_font_name
       , encode(community_dark_shade,'hex')
       , encode(community_mid_shade,'hex')
       , encode(community_light_shade,'hex')
       , encode(community_highlight_color,'hex')
  from (select community_id,community_name,community_code_language,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color
             , coalesce(communicant_regular_font_id,community_regular_font_id) regular_font_id
             , coalesce(communicant_monospace_font_id,community_monospace_font_id) monospace_font_id
        from community natural join (select community_id,communicant_regular_font_id,communicant_monospace_font_id from communicant where account_id=get_account_id()) z) c
       natural join (select font_id regular_font_id, font_name regular_font_name from font) r
       natural join (select font_id monospace_font_id, font_name monospace_font_name from font) m
  where community_id=cid;
$$;
--
create function login_room(inout uuid uuid
                         , in rid integer
                         , out community_name text
                         , out room_name text
                         , out room_can_chat boolean
                         , out community_code_language text
                         , out my_community_regular_font_name text
                         , out my_community_monospace_font_name text
                         , out colour_dark text
                         , out colour_mid text
                         , out colour_light text
                         , out colour_highlight text) language sql security definer set search_path=db,api,chat,pg_temp as $$
  --
  select login(uuid);
  --
  select _error('invalid room') where not exists (select 1
                                                  from room r natural join (select community_id,community_type from community) c 
                                                  where room_id=rid and (community_type='public' or exists (select 1 from member m where m.community_id=r.community_id and m.account_id=get_account_id()))
                                                                    and (room_type<>'private' or exists (select 1 from account_room_x a where a.room_id=r.room_id and a.account_id=get_account_id())));
  --
  select _set_id('room',room_id),_set_id('community',community_id) from room where room_id=rid;
  --
  select current_setting('custom.uuid',true)::uuid
       , community_name
       , room_name
       , (room_type='public' or exists (select 1 from account_room_x a where a.room_id=rid and a.account_id=get_account_id()))
       , community_code_language
       , my_community_regular_font_name
       , my_community_monospace_font_name
       , colour_dark
       , colour_mid
       , colour_light
       , colour_highlight
  from room cross join login_community(uuid,get_community_id())
  where room_id=rid;
$$;
--
--
revoke all on all functions in schema api from public;
do $$
begin
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='api' and proname!~'^_' );
end$$;
--
\i ~/git/transcript.sql
\i ~/git/chat.sql
\i ~/git/notification.sql
--
commit;
