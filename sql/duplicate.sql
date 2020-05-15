create schema duplicate;
grant usage on schema duplicate to get;
set local search_path to duplicate,api,pg_temp;
--
--
create view tag with (security_barrier) as
select tag_id,tag_name,tag_question_count
from db.question_tag_x qt natural join db.tag t
where question_id=get_question_id() and not exists (select 1 from db.question_tag_x natural join db.tag where question_id=qt.question_id and tag_implies_id=t.tag_id and tag_name like t.tag_name||'%');
--
create view answer with (security_barrier) as select answer_id, community_name from api._answer natural join api._question natural join db.community where not question_is_deleted and not answer_is_deleted;
--
create view one with (security_barrier) as
select get_account_id() account_id
      ,community_name,community_language,community_my_power,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
      ,question_id,question_at,question_title,question_votes,question_account_id,question_account_name,question_communicant_votes,sanction_short_description
     , coalesce(question_vote_votes,0) question_votes_from_me
      ,answer_id,answer_at,answer_summary,answer_account_id,answer_account_name,answer_votes,answer_communicant_votes
     , coalesce(answer_vote_votes,0) answer_votes_from_me
from (select community_id,question_id,answer_id,answer_at,answer_summary,answer_votes
           , account_id answer_account_id
           , account_name answer_account_name
           , coalesce(communicant_votes,0) answer_communicant_votes
      from api._answer natural join db.answer natural join db.account natural join db.communicant
      where answer_id=get_answer_id() and not answer_is_deleted) a
     natural join db.community
     natural join api._community
     natural join (select question_id,question_at,question_title,question_votes
                        , sanction_short_description
                        , account_id question_account_id
                        , account_name question_account_name
                        , coalesce(communicant_votes,0) question_communicant_votes
                   from api._question natural join db.question natural join db.sanction natural join db.kind natural join db.account natural join db.communicant
                   where not question_is_deleted) q
     natural left join (select question_id,question_vote_votes from db.question_vote natural join db.login where login_uuid=get_login_uuid() and question_vote_votes>0) qv
     natural left join (select answer_id,answer_vote_votes from db.answer_vote natural join db.login where login_uuid=get_login_uuid() and answer_vote_votes>0) av
;
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
create function login_answer(uuid,integer) returns boolean language sql security definer as $$select api.login_answer($1,$2);$$;
--
--
revoke all on all functions in schema duplicate from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='duplicate' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='duplicate' and proname!~'^_' );
end$$;
