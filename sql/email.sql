/*
\i ~/git/sql/email.sql
*/
begin;
--
drop schema if exists email cascade;
create schema email;
grant usage on schema email to email;
set local search_path to email,pg_temp;
--
--
create view notification as
select notification_id,account_email
     , case when cn.notification_id is not null then 'chat message from '||cn.account_name
            when an.notification_id is not null then 'answer by '||an.account_name||' on "'||an.question_title||'"'
            when ane.notification_id is not null then 'answer edit by '||ane.account_name||' on "'||ane.question_title||'"'
            when qn.notification_id is not null then 'question edit by '||qn.account_name||' on "'||qn.question_title||'"'
            else 'other' end notification_subject
     , case when cn.notification_id is not null then 'https://topanswers.xyz/'||cn.community_name||'?room='||cn.room_id||'#c'||cn.chat_id||chr(10)||chr(10)||cn.chat_markdown
            when an.notification_id is not null then 'https://topanswers.xyz/'||an.community_name||'?q='||an.question_id||'#a'||an.answer_id
            when ane.notification_id is not null then 'https://topanswers.xyz/answer-history?id='||ane.answer_id||'#h'||ane.answer_history_id
            when qn.notification_id is not null then 'https://topanswers.xyz/question-history?id='||qn.question_id||'#h'||qn.question_history_id
            else 'other' end notification_message
from db.notification
     natural join db.account
     left join (select notification_id,chat_markdown,community_name,chat_id,room_id,account_name from db.chat_notification natural join db.chat natural join db.account natural join db.community) cn using (notification_id)
     left join (select notification_id,question_title,community_name,question_id,answer_id,account_name
                from db.answer_notification
                     natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) ah
                     natural join (select answer_id,question_id,account_name,answer_at from db.answer natural join db.account) a
                     natural join (select question_id,question_title,community_name from db.question natural join db.community) q
                where answer_history_at=answer_at) an using (notification_id)
     left join (select notification_id,question_title,answer_history_id,answer_id,account_name
                from db.answer_notification
                     natural join (select answer_history_id,answer_id,answer_history_at from db.answer_history) ah
                     natural join (select answer_id,question_id,account_name,answer_at from db.answer natural join db.account) a
                     natural join (select question_id,question_title,community_name from db.question natural join db.community) q
                where answer_history_at<>answer_at) ane using (notification_id)
     left join (select notification_id,question_title,question_history_id,question_id,account_name
                from db.question_notification
                     natural join (select question_history_id,question_id,account_name from db.question_history natural join db.account) ah
                     natural join (select question_id,question_title,community_name from db.question natural join db.community) q) qn using (notification_id)
where notification_email_is_processed = false and notification_dismissed_at is null;
--
--
create function process(id bigint) returns void language sql security definer set search_path=db,email,pg_temp as $$
  update notification set notification_email_is_processed = true where notification_id=id;
$$;
--
--
revoke all on all functions in schema email from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to email;', E'\n') from pg_views where schemaname='email' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to email;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='email' and proname!~'^_' );
end$$;
--
--
commit;
