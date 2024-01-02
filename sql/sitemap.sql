create schema sitemap;
grant usage on schema sitemap to ta_get;
set local search_path to sitemap,api,pg_temp;
--
--
create view question with (security_barrier) as
select community_name, question_id, greatest(question_change_at,change_at) change_at, votes::real/max(votes) over (partition by community_id) priority
from (select question_id, max(answer_change_at) change_at, sum(answer_votes) votes from db.answer group by question_id) z natural join db.question natural join db.community
where question_crew_flags<0 or (question_crew_flags=0 and question_flags=0) and community_type='public';
--
--
revoke all on all functions in schema sitemap from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='sitemap' and viewname!~'^_');
end$$;
