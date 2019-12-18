create schema chat;
grant usage on schema chat to get;
set local search_path to chat,api,pg_temp;
--
--
create view account with (security_barrier) as select account_id,account_name from db.account;
create view chat with (security_barrier) as select chat_id,chat_at,chat_change_id,chat_reply_id,chat_markdown from db.chat where room_id=get_room_id();
--
--
create function range(startid bigint, endid bigint) 
                     returns table (chat_id bigint
                                  , account_id integer
                                  , chat_reply_id integer
                                  , chat_markdown text
                                  , chat_at timestamptz
                                  , chat_change_id bigint
                                  , account_is_me boolean
                                  , account_name text
                                  , reply_account_name text
                                  , reply_account_is_me boolean
                                  , chat_gap integer
                                  , communicant_votes integer
                                  , chat_editable_age boolean
                                  , i_flagged boolean
                                  , i_starred boolean
                                  , chat_account_will_repeat boolean
                                  , chat_flag_count integer
                                  , chat_star_count integer
                                  , chat_has_history boolean
                                  , chat_account_is_repeat boolean
                                  , rn bigint
                                   ) language sql security definer set search_path=db,api,chat,pg_temp as $$
  select *, row_number() over(order by chat_at desc) rn
  from (select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and chat_gap<60 chat_account_is_repeat
        from (select chat_id,account_id,chat_reply_id,chat_markdown,chat_at,chat_change_id
                   , account_id=get_account_id() account_is_me
                   , coalesce(nullif(account_name,''),'Anonymous') account_name
                   , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                   , (select account_id=get_account_id() from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                   , round(extract('epoch' from chat_at-(lag(chat_at) over (order by chat_at))))::integer chat_gap
                   , coalesce(communicant_votes,0) communicant_votes
                   , extract('epoch' from current_timestamp-chat_at)<240 chat_editable_age
                   , exists(select 1 from chat_flag where chat_id=c.chat_id and account_id=get_account_id()) i_flagged
                   , exists(select 1 from chat_star where chat_id=c.chat_id and account_id=get_account_id()) i_starred
                   , (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lag(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
                   , (select count(1)::integer from chat_flag where chat_id=c.chat_id) chat_flag_count
                   , (select count(1)::integer from chat_star where chat_id=c.chat_id) chat_star_count
                   , (select count(1) from chat_history where chat_id=c.chat_id)>1 chat_has_history
              from chat c natural join account natural left join (select community_id,communicant_votes from communicant where account_id=get_account_id()) v
              where room_id=get_room_id() and chat_id>=startid and (endid is null or chat_id<=endid)) z
        where get_account_id() is not null or chat_flag_count=0) z
  where chat_id>startid
  order by chat_at;
$$;
--
create function activerooms() returns table (room_id integer, room_name text, community_colour text, room_account_unread_messages bigint) language sql security definer set search_path=db,api,chat,pg_temp as $$
  select room_id,room_name,community_colour,room_account_unread_messages
  from (select room_id
             , coalesce(question_title,room_name,initcap(community_name)||' Chat') room_name
             , encode(community_light_shade,'hex') community_colour
             , (select count(1) from chat c where c.room_id=r.room_id and c.chat_id>x.room_account_x_latest_read_chat_id) room_account_unread_messages
             , (select max(chat_at) from chat c where room_id=r.room_id and account_id=get_account_id()) room_my_last_chat
        from room r natural join community natural join (select room_id,room_account_x_latest_read_chat_id from room_account_x where account_id=get_account_id()) x
             natural left join (select question_room_id room_id, question_title from question) q) z
  where room_my_last_chat>(current_timestamp-'7d'::interval)
  order by room_my_last_chat desc;
$$;
--
create function activeusers() returns table (account_id integer, account_name text, account_is_me boolean, communicant_votes integer) language sql security definer set search_path=db,api,chat,pg_temp as $$
  select account_id,account_name
       , account_id=get_account_id() account_is_me
       , coalesce(communicant_votes,0) communicant_votes
  from room natural join room_account_x natural join account natural join communicant
  where room_id=get_room_id()
  order by room_account_x_latest_chat_at desc;
$$;
--
create function quote(id bigint) returns text language sql security definer set search_path=db,api,chat,pg_temp as $$
  select _error('invalid chat id') where not exists (select 1
                                                     from chat natural join (select room_id,community_id,room_type from room) r natural join (select community_id,community_type from community) c 
                                                     where chat_id=id and (community_type='public' or exists (select 1 from member m where m.community_id=r.community_id and m.account_id=get_account_id()))
                                                                      and (room_type<>'private' or exists (select 1 from account_room_x a where a.room_id=r.room_id and a.account_id=get_account_id())));
  --
  select account_name
         ||(case when reply_account_name is not null then ' replying to '||reply_account_name else '' end)
         ||' — '
         ||to_char(chat_at,'YYYY-MM-DD"T"HH24:MI:SS"Z"')
         ||'  '
         ||chr(10)
         ||regexp_replace(chat_markdown,'^','>','mg')
  from (select chat_reply_id chat_id, chat_at, chat_markdown, account_name from chat natural join account where chat_id=id) c
       natural left join (select chat_id, account_name reply_account_name from chat natural join account) r
$$;
--
--
revoke all on all functions in schema chat from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='chat' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='chat' and proname!~'^_' );
end$$;
