create schema feed;
grant usage on schema feed to get,ta_get;
set local search_path to feed,api,pg_temp;
--
--
create view one with (security_barrier) as select community_name,community_display_name from db.community where community_id=get_community_id();
--
create view question with (security_barrier) as
select question_id,question_at,question_title
from db.question
where community_id=get_community_id() and question_published_at is not null and (question_crew_flags<0 or (question_crew_flags=0 and question_flags=0));
--
--
create function login_community(text) returns void language sql security definer as $$select api.login_community(null,(select community_id from db.community where community_name=$1));$$;
--
--
revoke all on all functions in schema feed from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get,ta_get;', E'\n') from pg_views where schemaname='feed' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get,ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='feed' and proname!~'^_' );
end$$;
