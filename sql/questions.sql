create schema questions;
grant usage on schema questions to get;
set local search_path to questions,api,pg_temp;
--
--
create view question with (security_barrier) as
select question_id,question_at,question_change_at,question_votes,question_poll_major_id,question_poll_minor_id,question_is_deleted,question_title,question_visible_chat_count
      ,community_id,community_name,community_display_name,community_my_power,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_image_url
     , sanction_short_description
     , account_id question_account_id
     , account_image_url question_account_image_url
     , account_derived_name question_account_name
     , coalesce(question_vote_votes,0) question_votes_from_me
     , coalesce(communicant_votes,0) question_communicant_votes
     , case when question_se_imported_at=question_change_at then 'imported' when question_change_at>question_at then 'edited' else 'asked' end question_change
     , exists(select 1 from api._answer a where a.question_id=q.question_id) question_is_answered
     , question_se_imported_at is not null question_is_imported
     , question_published_at is not null question_is_published
from api._question natural join db.question q natural join api._account natural join api._community natural join db.community natural join db.communicant natural join db.sanction natural join db.kind
     natural left join (select question_id,question_vote_votes from db.question_vote natural join db.login where login_uuid=get_login_uuid() and question_vote_votes>0) v;
--
create view tag with (security_barrier) as
select question_id,tag_id,tag_name,tag_description,tag_question_count
from db.mark qt natural join db.tag t
where not exists (select 1 from db.mark natural join db.tag where question_id=qt.question_id and tag_implies_id=t.tag_id and tag_name like t.tag_name||'%');
--
create view answer with (security_barrier) as
select community_id,question_id,answer_id,answer_at,answer_change_at,answer_markdown,answer_votes,answer_is_deleted,answer_summary,label_id,label_name,label_url
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , account_id answer_account_id
     , account_image_url answer_account_image_url
     , account_derived_name answer_account_name
     , coalesce(communicant_votes,0) answer_communicant_votes
     , case when answer_se_imported_at=answer_change_at then 'imported' when answer_change_at>answer_at then 'edited' else 'answered' end answer_change
from api._answer natural join db.answer natural join api._account natural left join db.communicant
     natural left join (select answer_id,answer_vote_votes from db.answer_vote natural join db.login where login_uuid=get_login_uuid() and answer_vote_votes>0) v
     natural left join db.label;
--
create view one with (security_barrier) as
select account_id,community_id,community_name,community_language,community_code_language,community_my_power
     , coalesce(account_is_dev,false) account_is_dev
     , coalesce(communicant_is_post_flag_crew,false) communicant_is_post_flag_crew
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
     , (select count(*) from api._question where community_id=get_community_id()) num_questions
from api._community natural join db.community
     natural left join (select account_id,account_is_dev,communicant_is_post_flag_crew,communicant_regular_font_id,communicant_monospace_font_id
                        from db.login natural join db.account natural join db.communicant
                        where login_uuid=get_login_uuid()) a
where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_room($1,(select community_room_id from db.community where community_name=$2));$$;
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,(select question_room_id from db.question where question_id=$2));$$;
--
create function parse(text) returns table (community_id integer, sanction_id integer, kind_id integer, tag_ids integer[], not_tag_ids integer[], label_ids integer[]) language sql security definer
                            set search_path=db,api,questions,x_pg_trgm,pg_temp as $$
  with f as (select trim((coalesce(regexp_match($1,'^[!+@]+ |^[!+@]+$'),array['']))[1]) flags)
     , c as (select get_community_id() community_id
             union
             select community_from_id from syndication cross join f where account_id=get_account_id() and community_to_id=get_community_id() and position('!' in flags)=0
             union
             select community_id from api._community cross join f where position('+' in flags)>0)
    , kt as (select lower(trim('{}' from m[1])) knd from regexp_matches($1,'{[^}]*}','g') m)
     , k as (select community_id,sanction_id,kind_id from sanction where (select count(1) from kt)=0 or exists(select 1 from kt where knd=lower(sanction_short_description)))
    , tt as (select lower(trim('[]' from m[1])) tag_name from regexp_matches($1,'\[[^!\]]+]','g') m)
     , t as (select community_id, array_agg(tag_id) tag_ids from tag natural join tt group by community_id)
   , ntt as (select lower(trim('[!]' from m[1])) tag_name from regexp_matches($1,'\[![^!\]]+]','g') m)
    , nt as (select community_id, array_agg(tag_id) not_tag_ids from tag natural join ntt group by community_id)
    , lt as (select lower(trim('()' from m[1])) label_prefix from regexp_matches($1,'\([^\)]+\)','g') m)
     , l as (select kind_id, array_agg(label_id) label_ids from label join lt on label_name~*('^'||label_prefix||'\y') group by kind_id)
    , u1 as (select community_id,sanction_id,kind_id,tag_ids from c natural join k natural join t where (select count(1)>0 from tt)
             union all
             select community_id,sanction_id,kind_id,null from c natural join k where (select count(1)=0 from tt))
    , u2 as (select community_id,sanction_id,kind_id,tag_ids,not_tag_ids from u1 natural left join nt where (select count(1)>0 from ntt)
             union all
             select community_id,sanction_id,kind_id,tag_ids,null from u1 where (select count(1)=0 from ntt))
    , u3 as (select community_id,sanction_id,kind_id,tag_ids,not_tag_ids,label_ids from u2 natural join l where exists(select 1 from lt)
             union all
             select community_id,sanction_id,kind_id,tag_ids,not_tag_ids,null from u2 where not exists (select 1 from lt))
  select community_id,sanction_id,kind_id,coalesce(tag_ids,array[]::integer[]),coalesce(not_tag_ids,array[]::integer[]),label_ids from u3;
$$;
--
create function simple_recent(text,integer,integer) returns table (question_id integer, question_ordinal integer, question_count integer) language sql security definer set search_path=db,api,questions,x_pg_trgm,pg_temp as $$
  with f as (select trim((coalesce(regexp_match($1,'^[!+@]+ |^[!+@]+$'),array['']))[1]) flags)
  select question_id, (row_number() over (order by question_poll_major_id desc))::integer, (count(1) over ())::integer
  from questions.parse($1) natural join question q cross join f
  where question_tag_ids@>tag_ids and not question_tag_ids&&not_tag_ids and (position('@' in flags)=0 or question_se_question_id is null)
        and (label_ids is null or exists (select 1 from answer a where a.question_id=q.question_id and label_id = any(label_ids)))
  order by question_poll_major_id desc offset ($2-1)*$3 limit $3;
$$;
--
create function fuzzy_closest(text,integer,integer) returns table (question_id integer, question_ordinal integer, question_count integer) language sql security definer set search_path=db,api,questions,x_pg_trgm,pg_temp as $$
  with f as (select trim((coalesce(regexp_match($1,'^[!+@]+ |^[!+@]+$'),array['']))[1]) flags)
     , c as (select distinct community_id from parse($1))
     , e as (select '%'||trim('"' from m[1])||'%' exacts from regexp_matches($1,'"[^%."]*"','g') m)
     , w as (select trim(regexp_replace($1,'\[[^\]]+]|{[^}]+}','','g')) search_text)
     , q as (select question_id, question_markdown txt, strict_word_similarity($1,question_markdown) word_similarity, similarity($1,question_markdown) similarity
             from c natural join db.question
             where (select search_text from w)<<%question_markdown and ((select exacts from e) is null or question_markdown ilike all((select exacts from e))))
    , qt as (select question_id, question_title txt, strict_word_similarity($1,question_title)*2 word_similarity, similarity($1,question_title)*2 similarity
             from c natural join db.question
             where (select search_text from w)<<%question_title and ((select exacts from e) is null or question_title ilike all((select exacts from e))))
     , a as (select question_id, answer_markdown txt, strict_word_similarity($1,answer_markdown) word_similarity, similarity($1,answer_markdown) similarity
             from c natural join db.answer natural join (select question_id,community_id from db.question) z
             where (select search_text from w)<<%answer_markdown and ((select exacts from e) is null or answer_markdown ilike all((select exacts from e))))
     , s as (select question_id, bool_or(txt like '%'||(select search_text from w)||'%') exact, max(word_similarity+similarity) similarity from (select * from q union all select * from qt union all select * from a) z group by question_id)
  select question_id, (row_number() over (order by exact desc, similarity desc))::integer, (count(1) over ())::integer
  from s natural join db.question q natural join parse($1) cross join f
  where question_tag_ids@>tag_ids and not question_tag_ids&&not_tag_ids and (position('@' in flags)=0 or question_se_question_id is null)
        and (label_ids is null or exists (select 1 from answer a where a.question_id=q.question_id and label_id = any(label_ids)))
  order by exact desc, similarity desc offset ($2-1)*$3 limit $3;
$$;
--
revoke all on all functions in schema questions from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='questions' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='questions' and proname!~'^_' );
end$$;
