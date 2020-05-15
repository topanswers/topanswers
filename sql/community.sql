create schema community;
grant usage on schema community to get;
set local search_path to community,api,pg_temp;
--
--
create view environment with (security_barrier) as select environment_name from db.environment;
create view sesite with (security_barrier) as select sesite_id,sesite_url,source_is_default from db.source natural join db.sesite where community_id=get_community_id();
--
create view private with (security_barrier) as
select community_name
from db.community natural left join (select community_id, account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='private' and account_id is null;
--
create view community with (security_barrier) as
select community_id,community_name,community_language,community_room_id,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_my_votes,community_ordinal,community_about_question_id
from api._community natural join db.community
     natural left join (select community_id,account_id from db.login natural join db.member where login_uuid=get_login_uuid()) m
where community_type='public' or account_id is not null;
--
create view room with (security_barrier) as
select community_id,room_id
     , coalesce(room_name,community_display_name||' Chat') room_name
from db.room natural join community natural left join (select * from db.login natural join db.writer where login_uuid=get_login_uuid()) a
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
     , account_derived_name question_flag_account_name
from db.question_flag natural join api._account
where question_id=get_question_id() and question_flag_direction<>0;
--
create view answer with (security_barrier) as
select answer_id,answer_at,answer_markdown,answer_votes,answer_se_answer_id,answer_crew_flags,answer_active_flags,answer_is_deleted,answer_summary,answer_change_at
     , account_is_imported answer_account_is_imported
     , account_id answer_account_id
     , account_derived_name answer_account_name
     , license_name||(case when answer_permit_later_license then ' or later' else '' end) answer_license_name
     , license_description answer_license_description
     , license_href answer_license_href
     , codelicense_id answer_codelicense_id
     , codelicense_name||(case when answer_permit_later_codelicense then ' or later' else '' end) answer_codelicense_name
     , codelicense_description answer_codelicense_description
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , answer_at<>answer_change_at or exists(select 1 from db.answer_flag_history where answer_id=a.answer_id) answer_has_history
     , selink_user_id answer_selink_user_id
     , coalesce(communicant_votes,0) answer_communicant_votes
     , exists(select 1 from db.answer_flag f natural join db.login where login_uuid=get_login_uuid() and f.answer_id=a.answer_id and answer_flag_direction=1) answer_i_flagged
     , exists(select 1 from db.answer_flag f natural join db.login where login_uuid=get_login_uuid() and f.answer_id=a.answer_id and answer_flag_direction=-1) answer_i_counterflagged
     , case when answer_se_imported_at=answer_change_at then 'imported' when answer_change_at>answer_at then 'edited' else 'answered' end answer_change
from api._answer natural join db.answer a natural join api._account natural join db.account natural join (select question_id,community_id,question_sesite_id from db.question) q natural join db.license natural join db.codelicense natural join db.communicant
     natural left join (select account_id,community_id,sesite_id question_sesite_id,selink_user_id from db.selink) s
     natural left join (select answer_id,answer_vote_votes from db.answer_vote natural join db.login where login_uuid=get_login_uuid() and answer_vote_votes>0) v
where question_id=get_question_id()
order by answer_votes desc, communicant_votes desc, answer_id desc;
--
create view answer_flag with (security_barrier) as
select answer_id,answer_flag_at,answer_flag_direction,answer_flag_is_crew
     , account_id answer_flag_account_id
     , account_derived_name answer_flag_account_name
from db.answer_flag natural join (select answer_id from db.answer where question_id=get_question_id()) a natural join api._account
where answer_flag_direction<>0;
--
create view one with (security_barrier) as
select account_id
      ,community_id,community_name,community_display_name,community_language,community_my_power,community_code_language,community_tio_language,room_id,community_tables_are_monospace,community_about_question_id,community_ask_button_text,community_banner_markdown
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
      ,question_id,question_at,question_title,question_markdown,question_votes,question_license_name,question_license_description,question_se_question_id,question_crew_flags,question_active_flags
      ,question_has_history,question_is_deleted,question_votes_from_me,question_answered_by_me,question_is_answered,question_answer_count,question_i_subscribed,question_i_flagged,question_i_counterflagged
      ,question_when,question_account_id,question_account_name,question_account_is_imported
      ,sanction_short_description
      ,kind_can_all_edit,kind_has_answers,kind_has_question_votes,kind_has_answer_votes,kind_minimum_votes_to_answer,kind_allows_question_multivotes,kind_allows_answer_multivotes
      ,kind_show_answer_summary_toc
     , selink_user_id question_selink_user_id
      ,question_communicant_votes
      ,question_license_href,question_has_codelicense,question_codelicense_name,question_codelicense_description
     , question_account_id is not distinct from account_id question_account_is_me
     , coalesce(login_resizer_percent,70) login_resizer_percent
     , coalesce(login_chat_resizer_percent,30) login_chat_resizer_percent
     , coalesce(account_is_dev,false) account_is_dev
     , coalesce(account_notification_id,0) account_notification_id
     , coalesce(question_title,room_name,community_display_name||' Chat') room_name
     , exists(select 1 from db.chat c where c.room_id=r.room_id) room_has_chat
     , coalesce(communicant_is_post_flag_crew,false) communicant_is_post_flag_crew
     , coalesce(communicant_can_import,false) communicant_can_import
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
     , a.account_id is not null and (room_type='public' or x.account_id is not null) room_can_chat
     , sesite_url
     , l.account_id is not null and room_can_listen room_can_mute
     , l.account_id is null and room_can_listen room_can_listen
     , p.account_id is not null room_is_pinned
from db.room r natural join db.community natural join api._community
     natural left join (select login_resizer_percent,login_chat_resizer_percent,account_id,account_is_dev,account_notification_id from db.login natural join db.account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
     natural left join db.listener l
     natural left join db.pinner p
     natural left join db.writer x
     natural left join (select question_id,question_at,question_title,question_markdown,question_votes,question_se_question_id,question_crew_flags,question_active_flags,question_is_deleted
                              ,sesite_url,selink_user_id
                              ,kind_can_all_edit,kind_has_answers,kind_has_question_votes,kind_has_answer_votes,kind_minimum_votes_to_answer,kind_allows_question_multivotes,kind_allows_answer_multivotes
                              ,kind_show_answer_summary_toc
                             , sanction_short_description
                             , license_name||(case when question_permit_later_license then ' or later' else '' end) question_license_name
                             , license_description question_license_description
                             , license_href question_license_href
                             , codelicense_name||(case when question_permit_later_codelicense then ' or later' else '' end) question_codelicense_name
                             , codelicense_description question_codelicense_description
                             , account_id question_account_id
                             , account_derived_name question_account_name
                             , account_is_imported question_account_is_imported
                             , coalesce(question_vote_votes,0) question_votes_from_me
                             , exists(select 1 from db.answer a natural join db.login where login_uuid=get_login_uuid() and a.question_id=q.question_id) question_answered_by_me
                             , exists(select 1 from api._answer a where a.question_id=q.question_id) question_is_answered
                             , (select count(1) from api._answer a where a.question_id=q.question_id) question_answer_count
                             , question_at<>question_change_at or exists(select 1 from db.question_flag_history where question_id=q.question_id) question_has_history
                             , exists(select 1 from db.subscription s natural join db.login where login_uuid=get_login_uuid() and s.question_id=q.question_id) question_i_subscribed
                             , exists(select 1 from db.question_flag f natural join db.login where login_uuid=get_login_uuid() and f.question_id=q.question_id and question_flag_direction=1) question_i_flagged
                             , exists(select 1 from db.question_flag f natural join db.login where login_uuid=get_login_uuid() and f.question_id=q.question_id and question_flag_direction=-1) question_i_counterflagged
                             , coalesce(communicant_votes,0) question_communicant_votes
                             , codelicense_id<>1 and codelicense_name<>license_name question_has_codelicense
                             , extract('epoch' from current_timestamp-question_at)::bigint question_when
                        from api._question natural join db.question q natural join db.sanction natural join db.kind natural join api._account natural join db.account natural join db.community
                                           natural join db.license natural join db.codelicense natural join db.communicant
                             natural left join (select account_id,community_id,sesite_id question_sesite_id,selink_user_id,sesite_url from db.selink natural join db.sesite) s
                             natural left join (select question_id,question_vote_votes from db.question_vote natural join db.login where login_uuid=get_login_uuid() and question_vote_votes>0) v
                        where question_id=get_question_id()) q
where room_id=get_room_id();
--
--
create function login(uuid) returns boolean language sql security definer as $$select api.login($1);$$;
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
