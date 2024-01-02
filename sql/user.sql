create schema usr;
grant usage on schema usr to ta_get;
set local search_path to usr,api,pg_temp;
--
--
create view community with (security_barrier) as
select community_id,community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_my_votes,community_ordinal,community_about_question_id
      ,community_question_count,community_answer_count
     , coalesce(community_question_votes,0)+coalesce(community_answer_votes,0) community_votes
from api._community
     natural join db.community
     natural left join (select community_id,account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
     natural left join (select community_id, sum(question_votes) community_question_votes, count(*) community_question_count
                        from api._question natural join db.question
                        where account_id=get_user_id()
                        group by community_id) q
     natural left join (with w as (select answer_id,answer_votes from db.answer natural join (select question_id,community_id from db.question) q where account_id=get_user_id())
                        select community_id, sum(answer_votes) community_answer_votes, count(*) community_answer_count from api._answer natural join w group by community_id) a
where (community_type='public' or account_id is not null) and (community_question_count>0 or community_answer_count>0);
--
create view question with (security_barrier) as
select community_id
      ,question_id,question_at,question_title,question_votes
      ,sanction_id,sanction_description,kind_has_question_votes
from api._question natural join db.question natural join db.sanction natural join db.kind
where community_id=get_community_id() and account_id=get_user_id();
--
create view answer with (security_barrier) as
select community_id
      ,question_id,question_at,question_title,question_votes
      ,answer_id,answer_at,answer_votes
      ,sanction_description
from (with w as (select *
                 from (select community_id,question_id,question_title,question_at,question_votes,kind_id from db.question) q natural join db.sanction natural join db.kind natural join db.answer
                 where community_id=get_community_id() and account_id=get_user_id())
      select * from w natural join api._answer) z;
--
create view one with (security_barrier) as
select a.my_account_id account_id
     , a.my_account_image_url account_image_url
     , u.account_id user_account_id
     , u.account_image_url user_account_image_url
     , u.account_name is distinct from account_derived_name user_account_name_is_derived
     , u.account_derived_name user_account_name
      ,community_id,community_name,community_display_name,community_image_url
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
     , (select font_id from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_id
     , (select font_id from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_id
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select account_id,account_name,account_derived_name,account_image_url from db.account natural join api._account where account_id=get_user_id()) u
     cross join (select community_id,community_name,community_display_name,community_regular_font_id,community_monospace_font_id
                              ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                              ,community_image_url
                        from api._community natural join db.community
                        where community_id=get_community_id()) c
     natural left join (select account_id my_account_id, account_image_url my_account_image_url from db.account natural join api._account where account_id=get_account_id()) a
     natural left join (select community_id,account_id my_account_id,communicant_regular_font_id,communicant_monospace_font_id from db.communicant) co;
--
--
create function login_communityuser(uuid,text,integer) returns boolean language sql security definer as
$$select api.login_communityuser($1,(select community_id from db.community where community_name=$2),$3);$$;
--
--
revoke all on all functions in schema usr from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to ta_get;', E'\n') from pg_views where schemaname='usr' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to ta_get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='usr' and proname!~'^_' );
end$$;
