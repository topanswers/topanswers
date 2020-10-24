create schema feedq;
grant usage on schema feedq to get;
set local search_path to feedq,api,pg_temp;
--
--
create view one with (security_barrier) as
select community_name,question_id,question_title,question_published_at from db.question natural join db.community where question_id=get_question_id();
--
create view answer with (security_barrier) as
select answer_id,answer_at,answer_change_at,answer_summary
from db.answer
where question_id=get_question_id() and (answer_crew_flags<0 or (answer_crew_flags=0 and answer_flags=0));
--
--
create function login_question(integer) returns void language sql security definer as $$select api.login_question(null,$1);$$;
--
--
revoke all on all functions in schema feedq from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='feedq' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='feedq' and proname!~'^_' );
end$$;
