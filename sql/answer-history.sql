create schema answer_history;
grant usage on schema answer_history to get;
set local search_path to answer_history,api,pg_temp;
--
--
create view history as
select account_id,account_image_url,answer_id,history_at,answer_history_id,answer_flag_history_id
     , account_derived_name account_name
from (select account_id,answer_id,answer_history_at history_at,answer_history_id,null::integer answer_flag_history_id from db.answer_history where answer_id=get_answer_id()
      union all
      select account_id,answer_id,answer_flag_history_at,null,answer_flag_history_id from db.answer_flag_history where answer_id=get_answer_id()) z
     natural join api._account;
--
create view answer_history as
select answer_history_id,answer_history_markdown
     , lag(answer_history_markdown) over (order by answer_history_at) prev_markdown
from db.answer_history
where answer_id=get_answer_id();
--
create view answer_flag_history as
select answer_flag_history_id,answer_flag_history_direction,answer_flag_history_is_crew
from db.answer_flag_history
where answer_id=get_answer_id();
--
create view one with (security_barrier) as
select answer_id
     , answer_se_answer_id is not null answer_is_imported
      ,question_id,question_title
      ,account_id,account_image_url
      ,community_id,community_name,community_display_name,community_code_language,community_tables_are_monospace,community_image_url
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select answer_id,question_id,answer_se_answer_id from db.answer where answer_id=get_answer_id()) a
     natural join (select community_id,question_id,question_title from _question natural join db.question) q
     natural join api._community
     natural join db.community
     natural join (select account_id,account_image_url from db.login natural join db.account natural join api._account where login_uuid=get_login_uuid()) ac
     natural left join db.communicant;
--
--
create function login_answer(uuid,integer) returns boolean language sql security definer as $$select api.login_answer($1,$2);$$;
--
--
revoke all on all functions in schema answer_history from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='answer_history' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='answer_history' and proname!~'^_' );
end$$;
