create schema question_history;
grant usage on schema question_history to get;
set local search_path to question_history,api,pg_temp;
--
--
create view history as
select question_history_id,account_id,account_name,question_history_markdown,question_history_title
     , to_char(question_history_at,'YYYY-MM-DD HH24:MI:SS') question_history_at
     , lag(question_history_markdown) over (order by question_history_at) prev_markdown
     , lag(question_history_title) over (order by question_history_at) prev_title
     , row_number() over (order by question_history_at) rn
from db.question_history natural join db.account
where question_id=get_question_id();
--
create view one with (security_barrier) as
select question_id,question_title
     , question_se_question_id is not null question_is_imported
      ,account_id
      ,community_id,community_name,community_display_name,community_code_language
     , encode(community_dark_shade,'hex') colour_dark
     , encode(community_mid_shade,'hex') colour_mid
     , encode(community_light_shade,'hex') colour_light
     , encode(community_highlight_color,'hex') colour_highlight
     , encode(community_warning_color,'hex') colour_warning
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select community_id,question_id,question_title,question_se_question_id from _question natural join db.question) q
     natural join db.community
     natural join (select account_id from db.login natural join db.account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
where question_id=get_question_id();
--
--
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
--
--
revoke all on all functions in schema question_history from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='question_history' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='question_history' and proname!~'^_' );
end$$;
