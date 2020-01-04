create schema community;
grant usage on schema community to get;
set local search_path to community,api,pg_temp;
--
--
create view environment with (security_barrier) as select environment_name from db.environment;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_display_name
from db.community natural left join (select community_id, account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='public' or account_id is not null;
--
create view room with (security_barrier) as
select community_id,room_id
     , coalesce(room_name,community_display_name||' Chat') room_name
from db.room natural join community natural left join (select * from db.login natural join db.account_room_x where login_uuid=get_login_uuid()) a
where (room_type<>'private' or account_id is not null) and not exists(select 1 from db.question where question_room_id=room.room_id);
--
create view tag with (security_barrier) as
select tag_id,tag_name,tag_implies_id,tag_question_count
     , q.tag_id is not null tag_is
from db.tag natural left join (select tag_id from db.question_tag_x where question_id=get_question_id()) q
where community_id=get_community_id();
--
create view question_flag with (security_barrier) as
select question_flag_at,question_flag_direction,question_flag_is_crew
     , account_id question_flag_account_id
     , account_name question_flag_account_name
from db.question_flag natural join db.account
where question_id=get_question_id() and question_flag_direction<>0;
--
create view answer with (security_barrier) as
select answer_id,answer_at,answer_markdown,answer_votes,answer_se_answer_id,answer_crew_flags,answer_active_flags
     , account_is_imported answer_account_is_imported
     , account_id answer_account_id
     , account_name answer_account_name
     , license_name answer_license_name
     , license_href answer_license_href
     , codelicense_id answer_codelicense_id
     , codelicense_name answer_codelicense_name
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , answer_at<>answer_change_at answer_has_history
     , communicant_se_user_id answer_communicant_se_user_id
     , coalesce(communicant_votes,0) answer_communicant_votes
     , exists(select 1 from db.answer_flag f natural join db.login where login_uuid=get_login_uuid() and f.answer_id=a.answer_id and answer_flag_direction=1) answer_i_flagged
     , exists(select 1 from db.answer_flag f natural join db.login where login_uuid=get_login_uuid() and f.answer_id=a.answer_id and answer_flag_direction=-1) answer_i_counterflagged
from db.answer a natural join db.account natural join (select question_id,community_id from db.question) q natural join db.license natural join db.codelicense natural join db.communicant
     natural left join (select answer_id,answer_vote_votes from db.answer_vote natural join db.login where login_uuid=get_login_uuid() and answer_vote_votes>0) v
where question_id=get_question_id()
order by answer_votes desc, communicant_votes desc, answer_id desc;
--
create view answer_flag with (security_barrier) as
select answer_id,answer_flag_at,answer_flag_direction,answer_flag_is_crew
     , account_id answer_flag_account_id
     , account_name answer_flag_account_name
from db.answer_flag natural join (select answer_id from db.answer where question_id=get_question_id()) a natural join db.account
where answer_flag_direction<>0;
--
create view one with (security_barrier) as
select account_id,community_id,community_name,community_code_language,room_id
      ,question_id,question_title,question_markdown,question_votes,question_license_name,question_se_question_id,question_crew_flags,question_active_flags
      ,question_has_history,question_is_deleted,question_votes_from_me,question_answered_by_me,question_i_subscribed,question_i_flagged,question_i_counterflagged,question_is_votable,question_is_blog,question_is_meta
      ,question_when,question_account_id,question_account_name,question_account_is_imported
      ,question_communicant_se_user_id,question_communicant_votes
      ,question_license_href,question_has_codelicense,question_codelicense_name
     , question_account_id is not distinct from account_id question_account_is_me
     , coalesce(login_resizer_percent,70) login_resizer_percent
     , coalesce(login_chat_resizer_percent,30) login_chat_resizer_percent
     , coalesce(account_is_dev,false) account_is_dev
     , coalesce(account_notification_id,0) account_notification_id
     , coalesce(question_title,room_name,community_display_name||' Chat') room_name
     , exists(select 1 from db.chat c where c.room_id=r.room_id) room_has_chat
     , coalesce(communicant_is_post_flag_crew,false) communicant_is_post_flag_crew
     , coalesce(communicant_can_import,false) communicant_can_import
     , encode(community_dark_shade,'hex') colour_dark
     , encode(community_mid_shade,'hex') colour_mid
     , encode(community_light_shade,'hex') colour_light
     , encode(community_highlight_color,'hex') colour_highlight
     , encode(community_warning_color,'hex') colour_warning
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
     , a.account_id is not null and (room_type='public' or x.account_id is not null) room_can_chat
     , 1+trunc(log(greatest(communicant_votes,0)+1)) community_my_power
     , sesite_url
from db.room r natural join db.community
     natural left join (select login_resizer_percent,login_chat_resizer_percent,account_id,account_is_dev,account_notification_id from db.login natural join db.account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
     natural left join db.account_room_x x
     natural left join (select sesite_id community_sesite_id, sesite_url from db.sesite) s
     natural left join (select question_id,question_title,question_markdown,question_votes,question_se_question_id,question_crew_flags,question_active_flags
                             , license_name question_license_name
                             , license_href question_license_href
                             , codelicense_name question_codelicense_name
                             , account_id question_account_id
                             , account_name question_account_name
                             , account_is_imported question_account_is_imported
                             , communicant_se_user_id question_communicant_se_user_id
                             , coalesce(question_vote_votes,0) question_votes_from_me
                             , exists(select 1 from db.answer a natural join db.login where login_uuid=get_login_uuid() and a.question_id=q.question_id) question_answered_by_me
                             , question_at<>question_change_at question_has_history
                             , exists(select 1 from db.subscription s natural join db.login where login_uuid=get_login_uuid() and s.question_id=q.question_id) question_i_subscribed
                             , exists(select 1 from db.question_flag f natural join db.login where login_uuid=get_login_uuid() and f.question_id=q.question_id and question_flag_direction=1) question_i_flagged
                             , exists(select 1 from db.question_flag f natural join db.login where login_uuid=get_login_uuid() and f.question_id=q.question_id and question_flag_direction=-1) question_i_counterflagged
                             , question_crew_flags>0 question_is_deleted
                             , coalesce(communicant_votes,0) question_communicant_votes
                             , codelicense_id<>1 and codelicense_name<>license_name question_has_codelicense
                             , question_type<>'question' question_is_votable
                             , question_type='blog' question_is_blog
                             , question_type='meta' question_is_meta
                             , extract('epoch' from current_timestamp-question_at) question_when
                        from db.question q natural join db.account natural join db.community natural join db.license natural join db.codelicense natural join db.communicant
                             natural left join (select question_id,question_vote_votes from db.question_vote natural join db.login where login_uuid=get_login_uuid() and question_vote_votes>0) v
                        where question_id=get_question_id()) q
where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select api.login_room($1,$2);$$;
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_room($1,(select community_room_id from db.community where community_name=$2));$$;
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
--
--
revoke all on all functions in schema community from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='community' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='community' and proname!~'^_' );
end$$;
