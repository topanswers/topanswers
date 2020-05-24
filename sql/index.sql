create schema indx;
grant usage on schema indx to get;
set local search_path to indx,api,pg_temp;
--
--
create view community with (security_barrier) as
select community_name,community_display_name,community_type,community_image_url
     , get_byte(community_dark_shade,0)||','||get_byte(community_dark_shade,1)||','||get_byte(community_dark_shade,2) community_rgb_dark
     , get_byte(community_mid_shade,0)||','||get_byte(community_mid_shade,1)||','||get_byte(community_mid_shade,2) community_rgb_mid
     , get_byte(community_light_shade,0)||','||get_byte(community_light_shade,1)||','||get_byte(community_light_shade,2) community_rgb_light
from db.community natural join api._community
where community_type='public' or community_is_coming_soon;
--
create view one with (security_barrier) as
select (select account_id from db.login natural join db.account where login_uuid=get_login_uuid()) account_id, '/image?hash='||encode(one_image_hash,'hex') one_image_url from db.one;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
--
--
revoke all on all functions in schema indx from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='indx' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='indx' and proname!~'^_' );
end$$;
