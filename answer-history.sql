create schema answer_history;
grant usage on schema answer_history to get;
set local search_path to answer_history,api,pg_temp;
--
--
create view history as
select answer_history_id,account_id,account_name,answer_history_markdown
     , to_char(answer_history_at,'YYYY-MM-DD HH24:MI:SS') answer_history_at
     , lag(answer_history_markdown) over (order by answer_history_at) prev_markdown
     , row_number() over (order by answer_history_at) rn
from db.answer_history natural join db.account
where answer_id=get_answer_id();
--
create view one with (security_barrier) as
select answer_id
     , answer_se_answer_id is not null answer_is_imported
      ,question_id,question_title
      ,account_id
      ,community_id,community_name,community_display_name,community_code_language
     , encode(community_dark_shade,'hex') colour_dark
     , encode(community_mid_shade,'hex') colour_mid
     , encode(community_light_shade,'hex') colour_light
     , encode(community_highlight_color,'hex') colour_highlight
     , encode(community_warning_color,'hex') colour_warning
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select answer_id,question_id,answer_se_answer_id from db.answer where answer_id=get_answer_id()) a
     natural join (select community_id,question_id,question_title from _question natural join db.question) q
     natural join db.community
     natural join (select account_id from db.login natural join db.account where login_uuid=get_login_uuid()) ac
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
