create schema transcript;
grant usage on schema transcript to get;
set local search_path to transcript,api,pg_temp;
--
--
create view chat with (security_barrier) as select chat_id,chat_at from db.chat where room_id=get_room_id();
create view chat_year with (security_barrier) as select chat_year,chat_year_count from db.chat_year where room_id=get_room_id();
create view chat_month with (security_barrier) as select chat_year,chat_month,chat_month_count from db.chat_month where room_id=get_room_id();
create view chat_day with (security_barrier) as select chat_year,chat_month,chat_day,chat_day_count from db.chat_day where room_id=get_room_id();
create view chat_hour with (security_barrier) as select chat_year,chat_month,chat_day,chat_hour,chat_hour_count from db.chat_hour where room_id=get_room_id();
--
create view one with (security_barrier) as
select account_id,account_is_dev,account_image_url,community_id,community_name,community_code_language,room_id,room_derived_name,room_question_id,community_tables_are_monospace,community_image_url
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
     , (room_type='public' or x.account_id is not null) room_can_chat
     , '/image?hash='||encode(one_image_hash,'hex') one_image_url
from db.one cross join db.room natural join api._room natural join db.community natural join api._community
     natural left join (select account_id,account_is_dev,account_image_url from db.login natural join db.account natural join api._account where login_uuid=get_login_uuid()) a
     natural left join db.communicant
     natural left join db.writer x
where room_id=get_room_id();
--
--
create function login_room(uuid,integer) returns boolean language sql security definer as $$select * from api.login_room($1,$2);$$;
--
create function search(text) 
                     returns table (chat_id bigint
                                  , account_id integer
                                  , chat_reply_id bigint
                                  , chat_markdown text
                                  , chat_at timestamptz
                                  , account_is_me boolean
                                  , account_name text
                                  , account_image_url text
                                  , reply_account_name text
                                  , reply_account_is_me boolean
                                  , i_flagged boolean
                                  , i_starred boolean
                                  , chat_flag_count integer
                                  , chat_star_count integer
                                  , chat_has_history boolean
                                   ) language sql security definer set search_path=db,api,transcript,x_pg_trgm,pg_temp as $$
  with c as (select chat.*, strict_word_similarity($1,chat_markdown) word_similarity, similarity($1,chat_markdown) similarity from chat where room_id=get_room_id() and $1<<%chat_markdown)
  select chat_id,account_id,chat_reply_id,chat_markdown,chat_at
       , account_id=get_account_id() account_is_me
       , account_derived_name account_name
       , account_image_url
       , (select account_derived_name from chat natural join _account where chat_id=c.chat_reply_id) reply_account_name
       , (select account_id=get_account_id() from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
       , exists(select 1 from chat_flag where chat_id=c.chat_id and account_id=get_account_id()) i_flagged
       , exists(select 1 from chat_star where chat_id=c.chat_id and account_id=get_account_id()) i_starred
       , (select count(1)::integer from chat_flag where chat_id=c.chat_id) chat_flag_count
       , (select count(1)::integer from chat_star where chat_id=c.chat_id) chat_star_count
       , (select count(1) from chat_history where chat_id=c.chat_id)>1 chat_has_history
  from c natural join api._account
  where get_account_id() is not null or (select count(1)::integer from chat_flag where chat_id=c.chat_id)=0
  order by word_similarity+similarity desc, chat_at desc limit 100;
$$;
--
create function range(startat timestamptz, endat timestamptz) 
                     returns table (chat_id bigint
                                  , account_id integer
                                  , chat_reply_id bigint
                                  , chat_markdown text
                                  , chat_at timestamptz
                                  , account_is_me boolean
                                  , account_name text
                                  , account_image_url text
                                  , reply_account_name text
                                  , reply_account_is_me boolean
                                  , chat_gap integer
                                  , i_flagged boolean
                                  , i_starred boolean
                                  , chat_account_will_repeat boolean
                                  , reply_is_different_segment boolean
                                  , chat_flag_count integer
                                  , chat_star_count integer
                                  , chat_has_history boolean
                                  , chat_account_is_repeat boolean
                                   ) language sql security definer set search_path=db,api,transcript,pg_temp as $$
  select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and chat_gap<60 chat_account_is_repeat
  from (select chat_id,account_id,chat_reply_id,chat_markdown,chat_at
             , account_id=get_account_id() account_is_me
             , account_derived_name account_name
             , account_image_url
             , (select account_derived_name from chat natural join _account where chat_id=c.chat_reply_id) reply_account_name
             , (select account_id=get_account_id() from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
             , round(extract('epoch' from chat_at-(lag(chat_at) over (order by chat_at))))::integer chat_gap
             , exists(select 1 from chat_flag where chat_id=c.chat_id and account_id=get_account_id()) i_flagged
             , exists(select 1 from chat_star where chat_id=c.chat_id and account_id=get_account_id()) i_starred
             , (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lag(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
             , chat_reply_id<(min(chat_id) over()) reply_is_different_segment
             , (select count(1)::integer from chat_flag where chat_id=c.chat_id) chat_flag_count
             , (select count(1)::integer from chat_star where chat_id=c.chat_id) chat_star_count
             , (select count(1) from chat_history where chat_id=c.chat_id)>1 chat_has_history
        from chat c natural join account natural join api._account
        where room_id=get_room_id() and chat_at>=startat and chat_at<endat) z
  where get_account_id() is not null or chat_flag_count=0
  order by chat_at;
$$;
--
--
revoke all on all functions in schema transcript from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='transcript' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='transcript' and proname!~'^_' );
end$$;
