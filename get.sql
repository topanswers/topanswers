create user get;
create schema get;
grant usage on schema get to get;
grant usage on schema x_pg_trgm to get;
set local schema 'get';
--
--
create view environment with (security_barrier) as select environment_name from db.environment;
--
create view sitemap with (security_barrier) as
select community_name, question_id, greatest(question_change_at,change_at) change_at, votes::real/max(votes) over (partition by community_id) priority
from (select question_id, max(answer_change_at) change_at, sum(answer_votes) votes from db.answer group by question_id) z natural join db.question natural join db.community where community_type='public';
--
create view sesite with (security_barrier) as select sesite_id,sesite_url from db.sesite;
create view font with (security_barrier) as select font_id,font_name,font_is_monospace from db.font;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color,community_sesite_id,community_code_language,community_display_name,community_my_power
      ,community_warning_color
from shared.community;
--
create view my_community with (security_barrier) as
select z.*, my_community_regular_font_name,my_community_monospace_font_name
from (select community_id
           , communicant_se_user_id my_community_se_user_id
           , coalesce(communicant_can_import,false) my_community_can_import
           , coalesce(communicant_regular_font_id,community_regular_font_id) my_community_regular_font_id
           , coalesce(communicant_monospace_font_id,community_monospace_font_id) my_community_monospace_font_id
           , coalesce(communicant_is_post_flag_crew,false) my_community_is_post_flag_crew
      from db.community
           natural left join (select * from db.communicant where account_id=current_setting('custom.account_id',true)::integer) z ) z
     natural join (select font_id my_community_regular_font_id, font_name my_community_regular_font_name from font) r
     natural join (select font_id my_community_monospace_font_id, font_name my_community_monospace_font_name from font) m;
--
create view login with (security_barrier) as select account_id,login_resizer_percent, true as login_is_me from db.login where login_uuid=current_setting('custom.uuid',true)::uuid;
--
create view account with (security_barrier) as
select account_id,account_name,account_image,account_change_id,account_change_at,account_is_imported, account_id=current_setting('custom.account_id',true)::integer account_is_me from db.account;
--
create view my_account with (security_barrier) as
select account_id,account_name,account_image,account_uuid,account_is_dev,account_license_id,account_codelicense_id,account_notification_id from db.account where account_id=current_setting('custom.account_id',true)::integer;
--
create view communicant with (security_barrier) as select account_id,community_id,communicant_votes,communicant_se_user_id from db.communicant;
--
create view room with (security_barrier) as
select community_id,room_id,room_image,room_can_chat
     , coalesce(question_title,room_name,initcap(community_name)||' Chat') room_name
     , question_title is not null room_is_for_question
     , question_id room_question_id
     , (select max(chat_at) from db.chat where room_id=room.room_id and account_id=current_setting('custom.account_id',true)::integer) room_my_last_chat
from shared.room natural join get.community
     natural left join (select question_room_id room_id, question_id, question_title from db.question) q;
--
create view room_account_x with (security_barrier) as select room_id,account_id,room_account_x_latest_chat_at from db.room_account_x natural join get.room where room_account_x_latest_chat_at>(current_timestamp-'7d'::interval);
--
create view my_room_account_x with (security_barrier) as
select room_id, (select count(*) from db.chat where room_id=room_account_x.room_id and chat_id>room_account_x_latest_read_chat_id) room_account_unread_messages
from db.room_account_x natural join get.room
where account_id=current_setting('custom.account_id',true)::integer;
--
create view chat with (security_barrier) as
select community_id,room_id,account_id,chat_id,chat_reply_id,chat_at,chat_change_id,chat_change_at,chat_markdown
     , (select count(1) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(1) from db.chat_star where chat_id=chat.chat_id) chat_star_count
     , (select count(1) from db.chat_history where chat_id=chat.chat_id)>1 chat_has_history
from db.chat natural join room;
--
create view chat_history with (security_barrier) as select chat_history_id,chat_id,chat_history_at,chat_history_markdown from db.chat_history;
create view chat_notification with (security_barrier) as select chat_id,chat_notification_at from db.chat_notification where account_id=current_setting('custom.account_id',true)::integer;
--
create view question_notification with (security_barrier) as
select question_history_id,question_id,question_notification_at
     , question_at<>question_history_at question_notification_is_edit
from db.question_notification natural join (select question_history_id,question_id,question_history_at from db.question_history) h natural join (select question_id,question_at from db.question) q
where account_id=current_setting('custom.account_id',true)::integer;
--
create view answer_notification with (security_barrier) as
select answer_history_id,answer_id,answer_notification_at
     , answer_at<>answer_history_at answer_notification_is_edit
from db.answer_notification natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) h natural join (select answer_id,answer_at from db.answer) q
where account_id=current_setting('custom.account_id',true)::integer;
--
create view chat_flag with (security_barrier) as select chat_id,chat_flag_at from db.chat_flag where account_id=current_setting('custom.account_id',true)::integer;
create view chat_star with (security_barrier) as select chat_id,chat_star_at from db.chat_star where account_id=current_setting('custom.account_id',true)::integer;
create view chat_year with (security_barrier) as select room_id,chat_year,chat_year_count from db.chat_year;
create view chat_month with (security_barrier) as select room_id,chat_year,chat_month,chat_month_count from db.chat_month;
create view chat_day with (security_barrier) as select room_id,chat_year,chat_month,chat_day,chat_day_count from db.chat_day;
create view chat_hour with (security_barrier) as select room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count from db.chat_hour;
create view question_type_enums with (security_barrier) as select unnest(enum_range(null::db.question_type_enum)) question_type;
--
create view question with (security_barrier) as
select question_id,community_id,account_id,question_type,question_at,question_title,question_markdown,question_room_id,question_change_at,question_votes,license_id,codelicense_id,question_poll_id,question_poll_major_id
      ,question_poll_minor_id,question_se_question_id,question_answer_at,question_answer_change_at,question_flags,question_crew_flags,question_active_flags
     , coalesce(question_vote_votes>=community_my_power,false) question_have_voted
     , coalesce(question_vote_votes,0) question_votes_from_me
     , exists(select account_id from db.answer where question_id=question.question_id and account_id=current_setting('custom.account_id',true)::integer) question_answered_by_me
     , question_at<>question_change_at question_has_history
     , greatest(tag_at,tag_history_at) question_retag_at
     , exists(select 1 from db.subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=question.question_id) question_i_subscribed
     , exists(select 1 from db.question_flag where account_id=current_setting('custom.account_id',true)::integer and question_id=question.question_id and question_flag_direction=1) question_i_flagged
     , exists(select 1 from db.question_flag where account_id=current_setting('custom.account_id',true)::integer and question_id=question.question_id and question_flag_direction=-1) question_i_counterflagged
from db.question natural join community
     natural left join (select community_id,communicant_is_post_flag_crew from db.communicant where account_id=current_setting('custom.account_id',true)::integer) c
     natural left join (select question_id,question_vote_votes from db.question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_votes>0) v
     natural left join (select question_id, max(answer_at) question_answer_at, max(answer_change_at) question_answer_change_at from db.answer group by question_id) a
     natural left join (select question_id, max(question_tag_x_at) tag_at from db.question_tag_x group by question_id) t
     natural left join (select question_id, max(greatest(question_tag_x_added_at,question_tag_x_removed_at)) tag_history_at from db.question_tag_x_history group by question_id) h
where (question_flags<=0 or current_setting('custom.account_id',true)::integer is not null) and (question_crew_flags<=0 or communicant_is_post_flag_crew);
--
create view question_history with (security_barrier) as select question_history_id,question_id,account_id,question_history_at,question_history_title,question_history_markdown from db.question_history;
--
create view answer with (security_barrier) as
select answer_id,question_id,account_id,answer_at,answer_markdown,answer_change_at,answer_votes,license_id,codelicense_id,answer_se_answer_id,answer_flags,answer_crew_flags,answer_active_flags
     , coalesce(answer_vote_votes>=community_my_power,false) answer_have_voted
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , answer_at<>answer_change_at answer_has_history
     , exists(select 1 from db.answer_flag where account_id=current_setting('custom.account_id',true)::integer and answer_id=answer.answer_id and answer_flag_direction=1) answer_i_flagged
     , exists(select 1 from db.answer_flag where account_id=current_setting('custom.account_id',true)::integer and answer_id=answer.answer_id and answer_flag_direction=-1) answer_i_counterflagged
from db.answer natural join (select question_id,community_id from question) z natural join community
     natural left join (select answer_id,answer_vote_votes from db.answer_vote where account_id=current_setting('custom.account_id',true)::integer and answer_vote_votes>0) zz;
--
create view answer_history with (security_barrier) as select answer_history_id,answer_id,account_id,answer_history_at,answer_history_markdown from db.answer_history;
--
create view tag with (security_barrier) as select tag_id,community_id,tag_name,tag_implies_id,tag_question_count from db.tag natural join community;
create view question_tag_x with (security_barrier) as select question_id,tag_id from db.question_tag_x natural join community;
--
create view question_tag_x_not_implied with (security_barrier) as
select question_id,tag_id from db.question_tag_x qt natural join db.tag t natural join community
where not exists (select 1 from db.question_tag_x natural join db.tag where question_id=qt.question_id and tag_implies_id=t.tag_id and tag_name like t.tag_name||'%');
--
create view license with (security_barrier) as select license_id,license_name,license_href from db.license;
create view codelicense with (security_barrier) as select codelicense_id,codelicense_name from db.codelicense;
create view subscription with (security_barrier) as select account_id,question_id from db.subscription;
create view question_flag with (security_barrier) as select question_id,account_id,question_flag_at,question_flag_direction,question_flag_is_crew from db.question_flag;
create view question_flag_history with (security_barrier) as select question_flag_history_id,question_id,account_id,question_flag_history_at,question_flag_history_direction,question_flag_history_is_crew from db.question_flag_history;
create view question_flag_notification with (security_barrier) as select question_flag_history_id,question_flag_notification_at from db.question_flag_notification where account_id=current_setting('custom.account_id',true)::integer;
create view answer_flag with (security_barrier) as select answer_id,account_id,answer_flag_at,answer_flag_direction,answer_flag_is_crew from db.answer_flag;
create view answer_flag_history with (security_barrier) as select answer_flag_history_id,answer_id,account_id,answer_flag_history_at,answer_flag_history_direction,answer_flag_history_is_crew from db.answer_flag_history;
create view answer_flag_notification with (security_barrier) as select answer_flag_history_id,answer_flag_notification_at from db.answer_flag_notification where account_id=current_setting('custom.account_id',true)::integer;
--
--
create function login(luuid uuid) returns boolean language sql security definer set search_path=db,get,pg_temp as $$
  select shared.login(luuid);
$$;
--
--
revoke all on all functions in schema get from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='get' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='get' and proname!~'^_' );
end$$;
