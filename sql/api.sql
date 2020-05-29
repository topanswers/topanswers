/*
\i ~/git/sql/api.sql
*/
begin;
--
drop schema if exists activeusers cascade;
drop schema if exists pinnedrooms cascade;
drop schema if exists activerooms cascade;
drop schema if exists usr cascade;
drop schema if exists navigation cascade;
drop schema if exists communityicon cascade;
drop schema if exists duplicate cascade;
drop schema if exists private cascade;
drop schema if exists poll cascade;
drop schema if exists indx cascade;
drop schema if exists import cascade;
drop schema if exists starboard cascade;
drop schema if exists chat_history cascade;
drop schema if exists answer_history cascade;
drop schema if exists question_history cascade;
drop schema if exists identicon cascade;
drop schema if exists roomicon cascade;
drop schema if exists sitemap cascade;
drop schema if exists upload cascade;
drop schema if exists answer cascade;
drop schema if exists profile cascade;
drop schema if exists question cascade;
drop schema if exists room cascade;
drop schema if exists questions cascade;
drop schema if exists community cascade;
drop schema if exists transcript cascade;
drop schema if exists chat cascade;
drop schema if exists notification cascade;
drop schema if exists q cascade;
drop schema if exists api cascade;
create schema api;
grant usage on schema api to get,post;
set local search_path to api,pg_temp;
--
--
create function _error(integer,text) returns void language plpgsql as $$begin raise exception '%', $2 using errcode='H0'||$1; end;$$;
create function _error(text) returns void language sql as $$select _error(403,$1);$$;
--
create function get_login_uuid() returns uuid stable language sql security definer as $$select nullif(current_setting('custom.uuid',true),'')::uuid;$$;
create function get_account_id() returns integer stable language sql security definer as $$select account_id from db.login where login_uuid=nullif(current_setting('custom.uuid',true),'')::uuid;$$;
--
create function _get_id(text) returns bigint stable language sql security definer set search_path=db,api,pg_temp as $$
  with w as (select account_encryption_key from login natural join account where login_uuid=nullif(current_setting('custom.uuid',true),'')::uuid
             union all
             select one_encryption_key from one where nullif(current_setting('custom.uuid',true),'')::uuid is null)
  select x_pgcrypto.pgp_sym_decrypt(current_setting('custom.'||$1||'_id',true)::bytea,(select account_encryption_key from w)::text||current_setting('custom.timestamp',true))::bigint
$$;
--
create function get_room_id() returns integer stable language sql security definer as $$select api._get_id('room'::text)::integer;$$;
create function get_community_id() returns integer stable language sql security definer as $$select api._get_id('community'::text)::integer;$$;
create function get_question_id() returns integer stable language sql security definer as $$select api._get_id('question'::text)::integer;$$;
create function get_answer_id() returns integer stable language sql security definer as $$select api._get_id('answer'::text)::integer;$$;
create function get_chat_id() returns bigint stable language sql security definer as $$select api._get_id('chat'::text);$$;
create function get_user_id() returns bigint stable language sql security definer as $$select api._get_id('user'::text);$$;
--
create function _set_id(text,bigint) returns void stable language sql security definer set search_path=db,api,pg_temp as $$
  with w as (select account_encryption_key from login natural join account where login_uuid=nullif(current_setting('custom.uuid',true),'')::uuid
             union all
             select one_encryption_key from one where nullif(current_setting('custom.uuid',true),'')::uuid is null)
  select set_config('custom.'||$1||'_id',x_pgcrypto.pgp_sym_encrypt($2::text,(select account_encryption_key from w)::text||current_setting('custom.timestamp',true))::text,false)
$$;
--
create function _markdownsummary(text) returns text language sql immutable security definer set search_path=db,api,pg_temp as $$
  with recursive
     m as (select regexp_replace(r[1],'([!$()*+.:<=>?[\\\]^{|}-])', '\\\1', 'g') str_from, trim(trailing chr(13) from r[2]) str_to, (row_number() over ())::integer rn 
           from regexp_matches($1,'^ *(\[[^\]]+]): ?(.*)$','ng') r)
   , w(markdown) as (select split_part(trim(leading chr(13) from $1),chr(13),1), 1 rn
                     union all
                     select regexp_replace(
                              regexp_replace(markdown,'(?<=\[[^\]]+])'||str_from,'('||str_to||')')
                             ,'(?<=(?<!\])'||str_from||')(?!\()'
                             ,'('||str_to||')')
                            ,rn+1  from w join m using(rn))
   , o as (select trim(both ' #' from markdown) markdown from w order by rn desc limit 1)
  select case when markdown~'^@@@ answer [1-9][0-9]*$'
                then (select 'see [this answer on "'||question_title||'"](/'||community_name||'?q='||question_id||'#a'||answer_id||')'
                      from (select question_id,community_id,question_title from question) q natural join community natural join answer where answer_id=substr(markdown,11)::integer)
              when markdown~'^@@@ question [1-9][0-9]*$'
                then (select 'see ["'||question_title||'"](/'||community_name||'?q='||question_id||')'
                      from question natural join community where question_id=substr(markdown,14)::integer)
              else markdown end
  from o;
$$;
--
--
create view _account with (security_barrier) as
select account_id
     , coalesce(nullif(account_name,''),'Anonymous') account_derived_name
     , case when account_image_hash is null then '/identicon?id='||account_id else '/image?hash='||encode(account_image_hash,'hex') end account_image_url
from db.account;
--
create view _community with (security_barrier) as
select community_id
     , split_part(community_name,'-',1) community_root_name
     , coalesce(nullif(substr(community_name,length(split_part(community_name,'-',1))+2),''),'en') community_language
     , 1+trunc(log(greatest(communicant_votes,1))) community_my_power
     , communicant_votes community_my_votes
     , get_byte(community_dark_shade,0)||','||get_byte(community_dark_shade,1)||','||get_byte(community_dark_shade,2) community_rgb_dark
     , get_byte(community_mid_shade,0)||','||get_byte(community_mid_shade,1)||','||get_byte(community_mid_shade,2) community_rgb_mid
     , get_byte(community_light_shade,0)||','||get_byte(community_light_shade,1)||','||get_byte(community_light_shade,2) community_rgb_light
     , get_byte(community_highlight_color,0)||','||get_byte(community_highlight_color,1)||','||get_byte(community_highlight_color,2) community_rgb_highlight
     , get_byte(community_warning_color,0)||','||get_byte(community_warning_color,1)||','||get_byte(community_warning_color,2) community_rgb_warning
     , case when community_image_hash is null then '/communityicon?community='||community_name else '/image?hash='||encode(community_image_hash,'hex') end community_image_url
from (select community_id,community_name,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color,community_warning_color,community_image_hash
      from db.community natural left join (select community_id,account_id from db.member where account_id=get_account_id()) m where community_type='public' or account_id is not null) c
     natural left join (select community_id,communicant_votes from db.communicant where account_id=get_account_id()) a;
--
create view _question with (security_barrier) as
select question_id,community_id
     , question_crew_flags>0 or (question_crew_flags=0 and question_flags>0) question_is_deleted
from db.question natural join _community
     natural left join (select community_id,communicant_is_post_flag_crew from db.communicant where account_id=get_account_id()) a
where communicant_is_post_flag_crew or question_crew_flags<0 or ((get_account_id() is not null or question_flags=0) and question_crew_flags=0) or account_id=get_account_id();
--
create view _room with (security_barrier) as
select room_id,community_id,room_can_chat
     , coalesce(question_title,room_name,community_display_name||' Chat') room_derived_name
     , case when room_image_hash is null then '/roomicon?id='||room_id else '/image?hash='||encode(room_image_hash,'hex') end room_image_url
from (select room_id,community_id,room_name,room_question_id,room_image_hash
           , get_account_id() is not null and (room_type='public' or account_id is not null) room_can_chat
      from db.room natural left join (select * from db.writer where account_id=get_account_id()) a
      where room_type<>'private' or account_id is not null) r
     natural join (select community_id,community_display_name from api._community natural join db.community) c
     natural left join (select question_id room_question_id,question_title, question_room_id room_id from db.question natural join api._question) q
where r.room_question_id is null or question_title is not null;
--
create view _answer with (security_barrier) as
select answer_id,question_id,community_id
     , answer_crew_flags>0 or (answer_crew_flags=0 and answer_flags>0) answer_is_deleted
from db.answer natural join _question
     natural left join (select community_id,communicant_is_post_flag_crew from db.communicant where account_id=get_account_id()) a
where communicant_is_post_flag_crew or answer_crew_flags<0 or ((get_account_id() is not null or answer_flags=0) and answer_crew_flags=0) or account_id=get_account_id();
--
create view _chat with (security_barrier) as
select chat_id,room_id,community_id
from db.chat natural join _room
     natural left join (select community_id,communicant_is_post_flag_crew from db.communicant where account_id=get_account_id()) a
where communicant_is_post_flag_crew or chat_crew_flags<0 or ((get_account_id() is not null or chat_flags=0) and chat_crew_flags=0) or account_id=get_account_id();
--
--
create function login(uuid uuid) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_community(uuid uuid, cid integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid community') where not exists (select 1 from _community c where community_id=cid);
  select _set_id('community',community_id) from community where community_id=cid;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_communityuser(uuid uuid, cid integer, aid integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid community') where not exists (select 1 from _community c where community_id=cid);
  select _error('invalid account') where not exists (select 1 from _account a where account_id=aid);
  select _set_id('community',community_id) from community where community_id=cid;
  select _set_id('user',account_id) from account where account_id=aid;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_room(uuid uuid, rid integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid room') where not exists (select 1 from _room where room_id=rid);
  select _set_id('room',room_id),_set_id('community',community_id) from room where room_id=rid;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_question(uuid uuid, qid integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid question') where not exists (select 1 from _question where question_id=qid);
  select _set_id('question',question_id),_set_id('room',question_room_id),_set_id('community',community_id) from question where question_id=qid;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_answer(uuid uuid, id integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid answer') where not exists (select 1 from _answer where answer_id=id);
  select _set_id('answer',answer_id),_set_id('question',question_id),_set_id('room',question_room_id),_set_id('community',community_id) from _answer natural join question where answer_id=id;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function login_chat(uuid uuid, id integer) returns boolean language sql security definer set search_path=db,api,pg_temp as $$
  select set_config('custom.timestamp',current_timestamp::text,false);
  select set_config('custom.uuid',uuid::text,false) from login where login_uuid=uuid;
  select _error('invalid chat') where not exists (select 1 from _chat where chat_id=id);
  select _set_id('chat',chat_id),_set_id('room',room_id),_set_id('community',community_id) from _chat where chat_id=id;
  select exists(select 1 from login where login_uuid=uuid);
$$;
--
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,post,pg_temp as $$
declare
  rid integer;
  cid integer;
  sid integer;
begin
  with r as (insert into room(community_id) values(0) returning room_id)
     , a as (insert into account(account_name) values('Community') returning account_id)
     , c as (insert into community(community_name,community_room_id,community_display_name,community_wiki_account_id)
             select cname,room_id,initcap(cname),account_id
             from a cross join r
             returning community_id,community_room_id,community_regular_font_id,community_monospace_font_id,community_wiki_account_id)
     , m as (insert into member(account_id,community_id) select 2,community_id from c)
     , k as (insert into sanction(kind_id,community_id,sanction_description,sanction_ordinal,sanction_is_default)
             select 1,community_id,'Question',10,true from c returning community_id,sanction_id)
    , k2 as (insert into sanction(kind_id,community_id,sanction_description,sanction_short_description,sanction_ordinal)
             select 2,community_id,'Meta Question','Meta',20 from c)
    , cm as (insert into communicant(account_id,community_id,communicant_regular_font_id,communicant_monospace_font_id)
             select community_wiki_account_id,community_id,community_regular_font_id,community_monospace_font_id from c)
  select community_id,community_room_id,sanction_id into strict cid,rid,sid from c natural join k;
  --
  update community set community_import_sanction_id = sid where community_id=cid;
  update room set community_id = cid where room_id=rid;
  return cid;
end$$;
--
create function _ensure_communicant(aid integer, cid integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  with i as (insert into communicant(account_id,community_id,communicant_regular_font_id,communicant_monospace_font_id)
             select aid,cid,community_regular_font_id,community_monospace_font_id from community where community_id=cid
             on conflict on constraint communicant_pkey do nothing
             returning account_id,community_id)
     , a as (select community_id,community_name,community_display_name,community_about_question_id from i natural join community where community_about_question_id is not null)
     , n as (insert into notification(account_id) select aid from a returning notification_id)
     , s as (insert into system_notification(notification_id,system_notification_message,system_notification_community_id)
             select notification_id
                  , 'If you haven''t already done so, please take a look at [the ''about'' post](/'||community_name||'?q='||community_about_question_id||') for '||community_display_name||'.'
                  , community_id
             from a cross join n)
  select null;
  --
  with s as (select syndicate_from_community_id
             from syndicate
             where syndicate_to_community_id=cid and not exists (select 1 from communicant where account_id=aid and community_id=syndicate_from_community_id))
  select _ensure_communicant(aid,syndicate_from_community_id) from s;
  --
  insert into syndication(account_id,community_to_id,community_from_id)
  select aid,cid,syndicate_from_community_id
  from syndicate
  where syndicate_to_community_id=cid
  on conflict do nothing;
$$;
--
--
revoke all on all functions in schema api from public;
do $$
begin
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get,post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='api' and proname!~'^_' );
end$$;
--
\i ~/git/sql/q.sql
\i ~/git/sql/transcript.sql
\i ~/git/sql/chat.sql
\i ~/git/sql/notification.sql
\i ~/git/sql/community.sql
\i ~/git/sql/questions.sql
\i ~/git/sql/room.sql
\i ~/git/sql/question.sql
\i ~/git/sql/profile.sql
\i ~/git/sql/answer.sql
\i ~/git/sql/upload.sql
\i ~/git/sql/sitemap.sql
\i ~/git/sql/roomicon.sql
\i ~/git/sql/identicon.sql
\i ~/git/sql/question-history.sql
\i ~/git/sql/answer-history.sql
\i ~/git/sql/chat-history.sql
\i ~/git/sql/starboard.sql
\i ~/git/sql/import.sql
\i ~/git/sql/index.sql
\i ~/git/sql/poll.sql
\i ~/git/sql/private.sql
\i ~/git/sql/duplicate.sql
\i ~/git/sql/communityicon.sql
\i ~/git/sql/navigation.sql
\i ~/git/sql/user.sql
\i ~/git/sql/activerooms.sql
\i ~/git/sql/pinnedrooms.sql
\i ~/git/sql/activeusers.sql
--
commit;
