create schema profile;
grant usage on schema profile to get,post;
set local search_path to profile,api,pg_temp;
--
--
create view license with (security_barrier) as select license_id,license_name,license_href,license_is_versioned from db.license;
create view codelicense with (security_barrier) as select codelicense_id,codelicense_name,codelicense_is_versioned from db.codelicense;
create view font with (security_barrier) as select font_id,font_name,font_is_monospace from db.font;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_my_votes,community_ordinal,community_about_question_id
from api._community natural join db.community
     natural left join (select community_id,account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='public' or account_id is not null;
--
create view question with (security_barrier) as
select community_id
      ,question_id,question_at,question_title,question_votes
      ,kind_description
from db.question natural join db.kind
where community_id=get_community_id() and account_id=get_account_id();
--
create view one with (security_barrier) as
select account_id,account_name,account_license_id,account_codelicense_id,account_uuid,account_permit_later_license,account_permit_later_codelicense
     , account_image is not null account_has_image
      ,community_id,community_name,community_display_name,community_regular_font_is_locked,community_monospace_font_is_locked
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
      ,sesite_url
     , (select font_id from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_id
     , (select font_id from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_id
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
      ,communicant_se_user_id
      ,one_stackapps_secret
from (select account_id,account_name,account_image,account_license_id,account_codelicense_id,account_uuid,account_permit_later_license,account_permit_later_codelicense from db.account where account_id=get_account_id()) a
     cross join db.one
     natural left join (select * from api._community natural join db.community left join db.sesite on community_sesite_id=sesite_id where community_id=get_community_id()) c
     natural left join db.communicant;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
--
--
revoke all on all functions in schema profile from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='profile' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='profile' and proname!~'^_' );
end$$;
--
--
create function new(luuid uuid) returns uuid language sql security definer set search_path=db,api,pg_temp as $$
  select _error(429,'rate limit') where (select count(*) from account where account_create_at>current_timestamp-'5m'::interval and account_is_imported=false)>5;
  --
  with a as (insert into account default values returning account_id,account_uuid)
     , l as (insert into login(account_id,login_uuid) select account_id,luuid from a)
     , n as (insert into system_notification(account_id,system_notification_message)
             select account_id,'Please make sure your [login key](/profile?highlight-recovery) is recorded somewhere safe before dismissing this message, so you don''t ever lose access to your account.' from a)
  select account_uuid from a;
$$;
--
create function authenticate_pin(num bigint) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  delete from pin where pin_number=num;
  insert into pin(pin_number,account_id) select num,account_id from account where account_id=get_account_id();
$$;
--
create function regenerate_account_uuid() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  update account set account_uuid = default where account_id = get_account_id();
$$;
--
create function change_regular_font(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('regular font is locked for this community') from community where community_id=get_community_id() and community_regular_font_is_locked;
  select _ensure_communicant(get_account_id(),get_community_id());
  update communicant set communicant_regular_font_id=id where account_id=get_account_id() and community_id=get_community_id();
$$;
--
create function change_monospace_font(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('monospace font is locked for this community') from community where community_id=get_community_id() and community_monospace_font_is_locked;
  select _ensure_communicant(get_account_id(),get_community_id());
  update communicant set communicant_monospace_font_id=id where account_id=get_account_id() and community_id=get_community_id();
$$;
--
create function change_name(nname text) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid username') where nname is not null and not nname~'^[0-9[:alpha:]][-'' .0-9[:alpha:]]{1,25}[0-9[:alpha:]]$';
  update account set account_name = nname, account_change_id = default, account_change_at = default where account_id=get_account_id();
$$;
--
create function change_image(image bytea) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  update account set account_image = image, account_change_id = default, account_change_at = default where account_id=get_account_id();
$$;
--
create function change_license(id integer, later boolean) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('"or later" not allowed for '||license_name) from license where license_id=id and later and not license_is_versioned;
  update account set account_license_id = id, account_permit_later_license = later where account_id=get_account_id();
$$;
--
create function change_codelicense(id integer, later boolean) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('"or later" not allowed for '||codelicense_name) from codelicense where codelicense_id=id and later and not codelicense_is_versioned;
  update account set account_codelicense_id = id, account_permit_later_codelicense = later where account_id=get_account_id();
$$;
--
create function set_se_user_id(id integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null or get_community_id() is null;
  select _error('SE user id is already linked to an account') where exists(select 1 from communicant natural join account where community_id=get_community_id() and communicant_se_user_id=id and not account_is_imported);
  select _error('SE user id is already set for this account') where exists(select 1 from communicant where community_id=get_community_id() and account_id=get_account_id() and communicant_se_user_id is not null);
  select _ensure_communicant(get_account_id(),get_community_id());
  --
  with se as (update communicant
              set communicant_se_user_id = null, communicant_votes = 0
              where community_id=get_community_id() and communicant_se_user_id=id
              returning account_id)
     , ta as (update communicant
              set communicant_se_user_id = id
                , communicant_votes = communicant_votes+coalesce((select communicant_votes from communicant where community_id=get_community_id() and account_id=(select account_id from se)),0)
              where community_id=get_community_id() and account_id=get_account_id())
      , q as (update question set account_id=get_account_id() where account_id=(select account_id from se))
      , a as (update answer set account_id=get_account_id() where account_id=(select account_id from se))
  select null;
$$;
--
create function link(luuid uuid, pn bigint) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('invalid pin') where not exists (select 1 from pin where pin_number=pn);
  insert into login(account_id,login_uuid) select account_id,luuid from pin where pin_number=pn and pin_at>current_timestamp-'1 min'::interval returning account_id;
$$;
--
create function link(luuid uuid, auuid uuid) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('invalid recovery key') where not exists (select 1 from account where account_uuid=auuid);
  insert into login(account_id,login_uuid) select account_id,luuid from account where account_uuid=auuid returning account_id;
$$;
--
create function change_resizer(perc integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('invalid percent') where perc<0 or perc>100;
  update login set login_resizer_percent = perc where login_uuid=get_login_uuid();
$$;
--
create function change_chat_resizer(perc integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('invalid percent') where perc<0 or perc>100;
  update login set login_chat_resizer_percent = perc where login_uuid=get_login_uuid();
$$;
--
--
revoke all on all functions in schema profile from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='profile' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='profile' and proname!~'^_' );
end$$;
