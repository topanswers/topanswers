create schema tags;
grant usage on schema tags to get,post;
set local search_path to tags,api,pg_temp;
--
--
create view tag with (security_barrier) as select tag_id,tag_name,tag_question_count,tag_implies_id,tag_code_language from db.tag where community_id=get_community_id();
--
create view one with (security_barrier) as
select account_image_url
      ,community_name,community_display_name,community_language,community_my_regular_font_name
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_black,community_rgb_white
from db.community natural join api._community
     natural left join (select account_image_url from api._account where account_id=get_account_id()) a
where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
--
--
revoke all on all functions in schema community from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='tags' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='tags' and proname!~'^_' );
end$$;
