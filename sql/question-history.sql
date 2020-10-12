create schema question_history;
grant usage on schema question_history to get,post;
set local search_path to question_history,api,pg_temp;
--
--
create view history as
select account_id,account_image_url,question_id,history_at,question_history_id,question_flag_history_id,mark_history_id
     , account_derived_name account_name
from (select account_id,question_id,question_history_at history_at,question_history_id, null::integer question_flag_history_id, null::integer mark_history_id
      from db.question_history
      where question_id=get_question_id()
      union all
      select account_id,question_id,question_flag_history_at,null,question_flag_history_id,null from db.question_flag_history where question_id=get_question_id()
      union all
      select account_id,question_id,mark_history_at,null,null,mark_history_id from db.mark_history where question_id=get_question_id()
      ) z
     natural join api._account
where history_at>=(select question_published_at from db.question where question_id=get_question_id()) or (select account_id from db.question where question_id=get_question_id())=get_account_id();
--
create view question_history as
select question_history_id,question_history_markdown,question_history_title
     , lag(question_history_markdown) over (order by question_history_at) prev_markdown
     , lag(question_history_title) over (order by question_history_at) prev_title
from db.question_history
where question_id=get_question_id()
      and (question_history_at>=(select question_published_at from db.question where question_id=get_question_id())
           or (select account_id from db.question where question_id=get_question_id())=get_account_id());
--
create view question_flag_history as
select question_flag_history_id,question_flag_history_direction,question_flag_history_is_crew
from db.question_flag_history
where question_id=get_question_id();
--
create view mark_history as
select mark_history_id,tag_name,mark_history_is_removal
from db.mark_history natural join db.tag
where question_id=get_question_id()
      and (mark_history_at>=(select question_published_at from db.question where question_id=get_question_id())
           or (select account_id from db.question where question_id=get_question_id())=get_account_id());
--
create view one with (security_barrier) as
select question_id,question_title,question_published_at
     , question_se_question_id is not null question_is_imported
     , question_account_id=get_account_id() and question_se_imported_at is null and question_published_at is not null and question_at<>question_published_at question_can_purge_drafts
      ,account_id,account_image_url
      ,community_id,community_name,community_display_name,community_code_language,community_tables_are_monospace,community_image_url
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select community_id,question_at,question_se_imported_at,question_id,question_title,question_se_question_id,question_published_at
           , account_id question_account_id
      from _question natural join db.question) q
     natural join api._community
     natural join db.community
     natural join (select account_id,account_image_url from db.login natural join db.account natural join api._account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
where question_id=get_question_id();
--
--
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
--
--
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='question_history' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='question_history' and proname!~'^_' );
end$$;
--
--
create function remove_drafts() returns void as $$
  select raise_error('not published') where exists (select 1 from question where question_id=get_question_id() and question_published_at is null);
  select raise_error('no drafts exist') where exists (select 1 from question where question_id=get_question_id() and (question_se_imported_at is not null or question_published_at=question_at));
  select raise_error('only author can remove drafts') where exists (select 1 from question where question_id=get_question_id() and account_id<>get_account_id());
  delete from question_history where question_id=get_question_id() and question_history_at<(select question_published_at from question where question_id=get_question_id());
  update question set question_at = question_published_at where question_id=get_question_id();
$$ language sql security definer set search_path=db,api,pg_temp;
--
--
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='question_history' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='question_history' and proname!~'^_' );
end$$;
--
--
revoke all on all functions in schema community from public;
