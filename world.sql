/*
\i ~/git/world.sql
*/
begin;
--
revoke usage on schema x_pg_trgm from world;
drop schema if exists world cascade;
drop role if exists world;
create user world;
create schema world;
grant usage on schema world to world;
grant usage on schema x_pg_trgm to world;
set local schema 'world';
--
create function _error(integer,text) returns void language plpgsql as $$begin raise exception '%', $2 using errcode='H0'||$1; end;$$;
create function _error(text) returns void language sql as $$select _error(403,$1);$$;
--
create view sitemap with (security_barrier) as
select community_name, question_id, greatest(question_change_at,change_at) change_at, votes::real/max(votes) over (partition by community_id) priority
from (select question_id, max(answer_change_at) change_at, sum(answer_votes) votes from db.answer group by question_id) z natural join db.question natural join db.community;
--
create view sesite with (security_barrier) as select sesite_id,sesite_url from db.sesite;
create view font with (security_barrier) as select font_id,font_name,font_is_monospace from db.font;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color,community_sesite_id,community_code_language
     , 1+trunc(log(greatest(account_community_votes,0)+1)) community_my_power
from db.community natural left join (select account_is_dev from db.account where account_id=current_setting('custom.account_id',true)::integer) y natural left join (select community_id,account_community_votes from db.account_community where account_id=current_setting('custom.account_id',true)::integer) z
where account_is_dev or not community_is_dev;
--
create view login with (security_barrier) as select account_id,login_resizer_percent, true as login_is_me from db.login where login_uuid=current_setting('custom.uuid',true)::uuid;
--
create view account with (security_barrier) as
select account_id,account_name,account_image,account_change_id,account_change_at,account_is_imported, account_id=current_setting('custom.account_id',true)::integer account_is_me from db.account;
--
create view my_account with (security_barrier) as
select account_id,account_name,account_image,account_uuid,account_is_dev,account_license_id,account_codelicense_id,account_notification_id from db.account where account_id=current_setting('custom.account_id',true)::integer;
--
create view account_community with (security_barrier) as select account_id,community_id,account_community_votes,account_community_se_user_id from db.account_community;
--
create view my_account_community with (security_barrier) as
select z.*, regular_font_name,monospace_font_name
from (select community_id,account_community_se_user_id
           , coalesce(account_community_can_import,false) account_community_can_import
           , coalesce(account_community_regular_font_id,community_regular_font_id) account_community_regular_font_id
           , coalesce(account_community_monospace_font_id,community_monospace_font_id) account_community_monospace_font_id
      from db.community
           natural left join (select * from db.account_community where account_id=current_setting('custom.account_id',true)::integer) z ) z
     natural join (select font_id account_community_regular_font_id, font_name regular_font_name from font) r
     natural join (select font_id account_community_monospace_font_id, font_name monospace_font_name from font) m;
--
create view room with (security_barrier) as
select community_id,room_id,room_image
     , coalesce(question_title,room_name,initcap(community_name)||' Chat') room_name
     , current_setting('custom.account_id',true)::integer is not null and (room_type='public' or account_id is not null) room_can_chat
     , question_title is not null room_is_for_question
     , question_id room_question_id
     , (select max(chat_at) from db.chat where room_id=room.room_id and account_id=current_setting('custom.account_id',true)::integer) room_my_last_chat
from db.room natural join world.community
     natural left join (select * from db.account_room_x where account_id=current_setting('custom.account_id',true)::integer) a
     natural left join (select question_room_id room_id, question_id, question_title from db.question) q
where room_type<>'private' or account_id is not null;
--
create view room_account_x with (security_barrier) as select room_id,account_id,room_account_x_latest_chat_at from db.room_account_x natural join world.room where room_account_x_latest_chat_at>(current_timestamp-'7d'::interval);
--
create view my_room_account_x with (security_barrier) as
select room_id, (select count(*) from db.chat where room_id=room_account_x.room_id and chat_id>room_account_x_latest_read_chat_id) room_account_unread_messages
from db.room_account_x natural join world.room
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
      ,question_poll_minor_id,question_se_question_id,question_answer_at,question_answer_change_at
     , coalesce(question_vote_votes>=community_my_power,false) question_have_voted
     , coalesce(question_vote_votes,0) question_votes_from_me
     , exists(select account_id from db.answer where question_id=question.question_id and account_id=current_setting('custom.account_id',true)::integer) question_answered_by_me
     , question_at<>question_change_at question_has_history
     , greatest(tag_at,tag_history_at) question_retag_at
     , exists(select 1 from db.subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=question.question_id) question_i_subscribed
from db.question natural join community
     natural left join (select question_id,question_vote_votes from db.question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_votes>0) v
     natural left join (select question_id, max(answer_at) question_answer_at, max(answer_change_at) question_answer_change_at from db.answer group by question_id) a
     natural left join (select question_id, max(question_tag_x_at) tag_at from db.question_tag_x group by question_id) t
     natural left join (select question_id, max(greatest(question_tag_x_added_at,question_tag_x_removed_at)) tag_history_at from db.question_tag_x_history group by question_id) h;
--
create view question_history with (security_barrier) as select question_history_id,question_id,account_id,question_history_at,question_history_title,question_history_markdown from db.question_history;
--
create view answer with (security_barrier) as
select answer_id,question_id,account_id,answer_at,answer_markdown,answer_change_at,answer_votes,license_id,codelicense_id,answer_se_answer_id
     , coalesce(answer_vote_votes>=community_my_power,false) answer_have_voted
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , answer_at<>answer_change_at answer_has_history
from db.answer natural join (select question_id,community_id from question) z natural join community natural left join (select answer_id,answer_vote_votes from db.answer_vote where account_id=current_setting('custom.account_id',true)::integer and answer_vote_votes>0) zz;
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
--
--
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,world,pg_temp as $$
declare
  rid integer;
  cid integer;
begin
  insert into room(community_id) values(0) returning room_id into rid;
  insert into community(community_name,community_room_id) values(cname,rid) returning community_id into cid;
  --
  insert into account_community(account_id,community_id,account_community_se_user_id,account_community_regular_font_id,account_community_monospace_font_id)
  select 208,cid,0,community_regular_font_id,community_monospace_font_id from community where community_id=cid;
  --
  update room set community_id=cid where room_id=rid;
  return cid;
end$$;
--
create function _create_seuser(cid integer, seuid integer, seuname text) returns integer language plpgsql security definer set search_path=db,world,pg_temp as $$
declare
  id integer;
begin
  if exists(select 1 from account_community where community_id=cid and account_community_se_user_id=seuid) then
    select account_id from account_community where community_id=cid and account_community_se_user_id=seuid into id;
  else
    insert into account(account_name,account_license_id,account_codelicense_id,account_is_imported) values(replace(seuname,'-',' '),4,1,true) returning account_id into id;
    --
    insert into account_community(account_id,community_id,account_community_se_user_id,account_community_regular_font_id,account_community_monospace_font_id)
    select id,cid,seuid,community_regular_font_id,community_monospace_font_id from community where community_id=cid;
  end if;
  return id;
end;
$$;
--
create function login(luuid uuid) returns boolean language plpgsql security definer set search_path=db,world,pg_temp as $$
declare e boolean = exists(select 1 from login where login_uuid=luuid);
begin
  if e then
    perform set_config('custom.uuid',luuid::text,false);
    perform set_config('custom.account_id',account_id::text,false) from login where login_uuid=luuid;
  end if;
  return e;
end;
$$;
--
create function _ensure_account_community(aid integer, cid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  insert into account_community(account_id,community_id,account_community_regular_font_id,account_community_monospace_font_id)
  select aid,cid,community_regular_font_id,community_monospace_font_id from community where community_id=cid
  on conflict on constraint account_community_pkey do nothing;
$$;
--
create function new_chat(roomid integer, msg text, replyid integer, pingids integer[]) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select 1 from room where room_id=roomid);
  select _error('access denied') where not exists(select 1 from world.room where room_id=roomid and room_can_chat);
  select _error(413,'message too long') where length(msg)>5000;
  select _ensure_account_community(current_setting('custom.account_id',true)::integer,community_id) from room where room_id=roomid;;
  --
  with d as (delete from chat_notification where chat_id=replyid and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
  --
  insert into chat_year(room_id,chat_year,chat_year_count)
  select roomid,extract('year' from current_timestamp),1 from room where room_id=roomid on conflict on constraint chat_year_pkey do update set chat_year_count = chat_year.chat_year_count+1;
  --
  insert into chat_month(room_id,chat_year,chat_month,chat_month_count)
  select roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),1 from room where room_id=roomid on conflict on constraint chat_month_pkey do update set chat_month_count = chat_month.chat_month_count+1;
  --
  insert into chat_day(room_id,chat_year,chat_month,chat_day,chat_day_count)
  select roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_day_pkey do update set chat_day_count = chat_day.chat_day_count+1;
  --
  insert into chat_hour(room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count)
  select roomid,extract('year' from current_timestamp),extract('month' from current_timestamp),extract('day' from current_timestamp),extract('hour' from current_timestamp),1
  from room
  where room_id=roomid
  on conflict on constraint chat_hour_pkey do update set chat_hour_count = chat_hour.chat_hour_count+1;
  --
  with i as (insert into chat(community_id,room_id,account_id,chat_markdown,chat_reply_id)
             select community_id,roomid,current_setting('custom.account_id',true)::integer,msg,replyid from room where room_id=roomid returning community_id,room_id,chat_id)
     , h as (insert into chat_history(chat_id,chat_history_markdown) select chat_id,msg from i)
     , n as (insert into chat_notification(chat_id,account_id)
             select chat_id,(select account_id from chat where chat_id=replyid) from i where replyid is not null and not (select account_is_me from chat natural join world.account where chat_id=replyid)
             returning *)
     , a as (update account set account_notification_id = default from n where account.account_id=n.account_id)
     , p as (insert into chat_notification(chat_id,account_id)
             select chat_id,account_id
             from i cross join (select account_id from world.account where account_id in (select * from unnest(pingids) except select account_id from chat where chat_id=replyid) and not account_is_me) z)
     , r as (insert into room_account_x(room_id,account_id,room_account_x_latest_read_chat_id)
             select room_id,current_setting('custom.account_id',true)::integer,chat_id from i
             on conflict on constraint room_account_x_pkey do update set room_account_x_latest_chat_at=default, room_account_x_latest_read_chat_id=excluded.room_account_x_latest_read_chat_id)
  select chat_id from i;
$$;
--
create function change_chat(id integer, msg text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('chat does not exist') where not exists(select 1 from chat where chat_id=id);
  select _error('message not mine') from chat where chat_id=id and account_id<>current_setting('custom.account_id',true)::integer;
  select _error('too late') from chat where chat_id=id and extract('epoch' from current_timestamp-chat_at)>300;
  select _error(413,'message too long') where length(msg)>5000;
  insert into chat_history(chat_id,chat_history_markdown) values(id,msg);
  --
  with w as (select chat_reply_id from chat natural join (select chat_id chat_reply_id, account_id reply_account_id from chat) z where chat_id=id and chat_reply_id is not null)
  update account set account_notification_id = default where account_id in(select account_id from w);
  --
  update chat set chat_markdown = msg, chat_change_id = default, chat_change_at = default where chat_id=id;
$$;
--
create function dismiss_chat_notification(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  with d as (delete from chat_notification where chat_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_question_notification(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  with d as (delete from question_notification where question_history_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_answer_notification(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  with d as (delete from answer_notification where answer_history_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function new_account(luuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(429,'rate limit') where (select count(*) from account where account_create_at>current_timestamp-'5m'::interval)>5;
  --
  with a as (insert into account default values returning account_id)
  insert into login(account_id,login_uuid) select account_id,luuid from a returning account_id;
$$;
--
create function link_account(luuid uuid, pn bigint) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid pin') where not exists (select 1 from pin where pin_number=pn);
  insert into login(account_id,login_uuid) select account_id,luuid from pin where pin_number=pn and pin_at>current_timestamp-'1 min'::interval returning account_id;
$$;
--
create function recover_account(luuid uuid, auuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid recovery key') where not exists (select 1 from account where account_uuid=auuid);
  insert into login(account_id,login_uuid) select account_id,luuid from account where account_uuid=auuid returning account_id;
$$;
--
create function regenerate_account_uuid() returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not logged in') where current_setting('custom.account_id',true)::integer is null;
  update account set account_uuid = default where account_id = current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_name(nname text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid username') where nname is not null and not nname~'^[0-9[:alpha:]][-'' .0-9[:alpha:]]{1,25}[0-9[:alpha:]]$';
  update account set account_name = nname, account_change_id = default, account_change_at = default where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_image(image bytea) returns void language sql security definer set search_path=db,world,pg_temp as $$
  update account set account_image = image, account_change_id = default, account_change_at = default where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_license_id(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  update account set account_license_id = id where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_codelicense_id(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  update account set account_codelicense_id = id where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_resizer(perc integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid percent') where perc<0 or perc>100;
  update login set login_resizer_percent = perc where login_uuid=current_setting('custom.uuid',true)::uuid;
$$;
--
create function authenticate_pin(num bigint) returns void language sql security definer set search_path=db,world,pg_temp as $$
  delete from pin where pin_number=num;
  insert into pin(pin_number,account_id) select num,account_id from world.account where account_is_me;
$$;
--
create function set_chat_flag(cid bigint) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('cant flag own message') where exists(select 1 from chat where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('already flagged') where exists(select 1 from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join world.room where chat_id=cid and room_can_chat);
  insert into chat_flag(chat_id,account_id) select chat_id,current_setting('custom.account_id',true)::integer from chat where chat_id=cid;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function remove_chat_flag(cid bigint) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not already flagged') where not exists(select 1 from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join world.room where chat_id=cid and room_can_chat);
  delete from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function set_chat_star(cid bigint) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('cant star own message') where exists(select 1 from chat where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('already starred') where exists(select 1 from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join world.room where chat_id=cid and room_can_chat);
  insert into chat_star(chat_id,account_id) select chat_id,current_setting('custom.account_id',true)::integer from chat where chat_id=cid;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function remove_chat_star(cid bigint) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not already starred') where not exists(select 1 from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join world.room where chat_id=cid and room_can_chat);
  delete from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function _new_question_tag(aid integer, qid integer, tid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _error('invalid tag') where not exists (select 1 from world.tag where tag_id=tid);
  --
  select _ensure_account_community(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=qid;;
  update question set question_poll_minor_id = default where question_id=qid;
  --
  with recursive w(tag_id,next_id,path,cycle) as (select tag_id,tag_implies_id,array[tag_id],false from tag where tag_id=tid
                                                  union all
                                                  select tag.tag_id,tag.tag_implies_id,path||tag.tag_id,tag.tag_id=any(w.path) from w join tag on tag.tag_id=w.next_id where not cycle)
     , i as (insert into question_tag_x(question_id,tag_id,community_id,account_id)
             select qid,tag_id,community_id,aid
             from w natural join tag
             where tag_id not in (select tag_id from question_tag_x where question_id=qid)
             returning tag_id)
  update tag set tag_question_count = tag_question_count+1 where tag_id in (select tag_id from i);
$$;
--
create function new_question_tag(qid integer, tid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1
                                                from question_tag_x_history
                                                where question_tag_x_history_added_by_account_id=current_setting('custom.account_id',true)::integer and question_tag_x_added_at>current_timestamp-'1s'::interval);
  select _new_question_tag(current_setting('custom.account_id',true)::integer,qid,tid);
$$;
--
create function new_sequestion_tag(qid integer, tid integer, uid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _new_question_tag(uid,qid,tid);
$$;
--
create function _new_question(cid integer, aid integer, typ db.question_type_enum, title text, markdown text, lic integer, codelic integer, seqid integer)
                returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid community') where not exists (select 1 from community where community_id=cid);
  select _ensure_account_community(aid,cid);
  --
  with r as (insert into room(community_id) values(cid) returning room_id)
     , q as (insert into question(community_id,account_id,question_type,question_title,question_markdown,question_room_id,license_id,codelicense_id,question_se_question_id)
             select cid,aid,typ,title,markdown,room_id,lic,codelic,seqid from r returning question_id)
     , h as (insert into question_history(question_id,account_id,question_history_title,question_history_markdown)
             select question_id,aid,title,markdown from q)
     , s as (insert into subscription(account_id,question_id) select aid,question_id from q)
  select question_id from q;
$$;
--
create function new_question(cid integer, typ db.question_type_enum, title text, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1 from question where account_id=current_setting('custom.account_id',true)::integer and question_at>current_timestamp-'5m'::interval and account_id>2);
  select _new_question(cid,current_setting('custom.account_id',true)::integer,typ,title,markdown,lic,codelic,null);
$$;
--
create function new_sequestion(cid integer, title text, markdown text, tags text, seqid integer, seuid integer, seuname text) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from question where community_id=cid and question_se_question_id=seqid);
  --
  with u as (select _create_seuser(cid,seuid,seuname) uid)
     , q as (select uid, _new_question(cid,uid,'question',title,markdown,4,1,seqid) qid from u)
     , t as (select new_sequestion_tag(qid,tag_id,uid) from q cross join tag natural join (select * from regexp_split_to_table(tags,' ') tag_name) z where community_id=cid)
  select qid from q cross join (select count(1) cn from t) z;
$$;
--
create function new_sequestionanon(cid integer, title text, markdown text, tags text, seqid integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from question where community_id=cid and question_se_question_id=seqid);
  --
  with u as (select account_id uid from account_community where community_id=cid and account_community_se_user_id=0)
     , q as (select uid, _new_question(cid,uid,'question',title,markdown,4,1,seqid) qid from u)
     , t as (select new_sequestion_tag(qid,tag_id,uid) from q cross join tag natural join (select * from regexp_split_to_table(tags,' ') tag_name) z where community_id=cid)
  select qid from q cross join (select count(1) cn from t) z;
$$;
--
create function change_question(id integer, title text, markdown text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('only author can edit blog post') where exists (select 1 from question where question_id=id and question_type='blog' and account_id<>current_setting('custom.account_id',true)::integer);
  select _error(429,'rate limit') where (select count(*)
                                         from question_history natural join (select question_id from question where account_id<>current_setting('custom.account_id',true)::integer) z
                                         where account_id=current_setting('custom.account_id',true)::integer and question_history_at>current_timestamp-'5m'::interval)>10;
  --
  with h as (insert into question_history(question_id,account_id,question_history_title,question_history_markdown) values(id,current_setting('custom.account_id',true)::integer,title,markdown) returning question_id,question_history_id)
  insert into question_notification(question_history_id,account_id)
  select question_history_id,account_id from h natural join (select question_id,account_id from question) z where account_id<>current_setting('custom.account_id',true)::integer
  union
  select question_history_id,account_id from h natural join subscription where account_id<>current_setting('custom.account_id',true)::integer;
  --
  update question set question_title = title, question_markdown = markdown, question_change_at = default, question_poll_major_id = default where question_id=id;
$$;
--
create function _new_answer(qid integer, aid integer, markdown text, lic integer, codelic integer, seaid integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _ensure_account_community(aid,community_id) from question where question_id=qid;
  --
  with i as (insert into answer(question_id,account_id,answer_markdown,license_id,codelicense_id,answer_se_answer_id) values(qid,aid,markdown,lic,codelic,seaid) returning answer_id)
     , h as (insert into answer_history(answer_id,account_id,answer_history_markdown) select answer_id,aid,markdown from i returning answer_id,answer_history_id)
     , n as (insert into answer_notification(answer_history_id,account_id)
             select answer_history_id,account_id from h cross join (select account_id from subscription where question_id=qid and account_id<>current_setting('custom.account_id',true)::integer) z)
  select answer_id from i;
$$;
--
create function new_answer(qid integer, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1 from answer where account_id=current_setting('custom.account_id',true)::integer and answer_at>current_timestamp-'1m'::interval and account_id>2);
  --
  update question set question_poll_major_id = default where question_id=qid;
  select _new_answer(qid,current_setting('custom.account_id',true)::integer,markdown,lic,codelic,null);
$$;
--
create function new_seanswer(qid integer, markdown text, seaid integer, seuid integer, seuname text) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from answer natural join (select question_id,community_id from question) q where question_id=qid and answer_se_answer_id=seaid);
  select _new_answer(qid,_create_seuser(community_id,seuid,seuname),markdown,4,1,seaid) from question where question_id=qid;
$$;
--
create function new_seansweranon(qid integer, markdown text, seaid integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from answer natural join (select question_id,community_id from question) q where question_id=qid and answer_se_answer_id=seaid);
  select _new_answer(qid,(select account_id from account_community where community_id=question.community_id and account_community_se_user_id=0),markdown,4,1,seaid) from question where question_id=qid;
$$;
--
create function change_answer(id integer, markdown text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error(429,'rate limit') where (select count(*)
                                         from answer_history natural join (select answer_id from answer where account_id<>current_setting('custom.account_id',true)::integer) z
                                         where account_id=current_setting('custom.account_id',true)::integer and answer_history_at>current_timestamp-'5m'::interval)>10;
  --
  update question set question_poll_major_id = default where question_id=(select question_id from answer where answer_id=id);
  --
  with h as (insert into answer_history(answer_id,account_id,answer_history_markdown) values(id,current_setting('custom.account_id',true)::integer,markdown) returning answer_id,answer_history_id)
  insert into answer_notification(answer_history_id,account_id)
  select answer_history_id,account_id from h natural join (select answer_id,question_id,account_id from answer) z where account_id<>current_setting('custom.account_id',true)::integer
  union
  select answer_history_id,account_id from h natural join (select answer_id,question_id from answer) z natural join subscription where account_id<>current_setting('custom.account_id',true)::integer;
  --
  update answer set answer_markdown = markdown, answer_change_at = default where answer_id=id;
$$;
--
create function remove_question_tag(qid integer, tid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _error('invalid tag') where not exists (select 1 from world.tag where tag_id=tid);
  select _error(429,'rate limit') where exists (select 1
                                            from question_tag_x_history
                                            where question_tag_x_history_removed_by_account_id=current_setting('custom.account_id',true)::integer and question_tag_x_removed_at>current_timestamp-'1s'::interval);
  --
  update question set question_poll_minor_id = default where question_id=qid;
  --
  select remove_question_tag(qid,tag_implies_id)
  from question_tag_x natural join tag t natural join (select tag_id tag_implies_id, tag_name parent_name from tag) z
  where question_id=qid and tag_id=tid and tag_name like parent_name||'%' and not exists(select 1 from question_tag_x natural join tag where question_id=qid and tag_id<>tid and tag_implies_id=t.tag_implies_id);
  --
  insert into question_tag_x_history(question_id,tag_id,community_id,question_tag_x_history_added_by_account_id,question_tag_x_history_removed_by_account_id,question_tag_x_added_at)
  select qid,tid,community_id,account_id,current_setting('custom.account_id',true)::integer,question_tag_x_at from question_tag_x where question_id=qid and tag_id=tid;
  --
  delete from question_tag_x where question_id=qid and tag_id=tid;
  update tag set tag_question_count = tag_question_count-1 where tag_id=tid;
$$;
--
create function vote_question(qid integer, votes integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid number of votes cast') where votes<0 or votes>(select community_my_power from question natural join world.community where question_id=qid);
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _error('cant vote on own question') where exists (select 1 from world.question where question_id=qid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('cant vote on this question type') where exists (select 1 from world.question where question_id=qid and question_type='question');
  select _error(429,'rate limit') where (select count(1) from question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(1) from question_vote_history where account_id=current_setting('custom.account_id',true)::integer and question_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_account_community(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=qid;;
  update question set question_poll_minor_id = default where question_id=qid;
  --
  with d as (delete from question_vote where question_id=qid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select question_id,community_id,q.account_id,question_vote_votes from d join question q using(question_id))
     , q as (update question set question_votes = question_votes-question_vote_votes from d where question.question_id=qid)
     , a as (insert into account_community(account_id,community_id,account_community_votes,account_community_regular_font_id,account_community_monospace_font_id)
             select account_id,community_id,-question_vote_votes,community_regular_font_id,community_monospace_font_id from r natural join community
             on conflict on constraint account_community_pkey do update set account_community_votes = account_community.account_community_votes+excluded.account_community_votes)
  insert into question_vote_history(question_id,account_id,question_vote_history_at,question_vote_history_votes)
  select question_id,account_id,question_vote_at,question_vote_votes from d;
  --
  with i as (insert into question_vote(question_id,account_id,question_vote_votes) values(qid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , c as (insert into account_community(account_id,community_id,account_community_votes,account_community_regular_font_id,account_community_monospace_font_id)
             select account_id,community_id,question_vote_votes,community_regular_font_id,community_monospace_font_id
             from (select question_id,community_id,q.account_id,question_vote_votes from i join question q using(question_id)) z natural join community
             on conflict on constraint account_community_pkey do update set account_community_votes = account_community.account_community_votes+excluded.account_community_votes)
  update question set question_votes = question_votes+question_vote_votes from i where question.question_id=qid returning question_votes;
$$;
--
create function vote_answer(aid integer, votes integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid number of votes cast') where votes<0 or votes>(select community_my_power from answer natural join (select question_id,community_id from question) q natural join world.community where answer_id=aid);
  select _error('invalid answer') where not exists (select 1 from world.answer where answer_id=aid);
  select _error('cant vote on own answer') where exists (select 1 from world.answer where answer_id=aid and account_id=current_setting('custom.account_id',true)::integer);
  select _error(429,'rate limit') where (select count(*) from answer_vote where account_id=current_setting('custom.account_id',true)::integer and answer_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(*) from answer_vote_history where account_id=current_setting('custom.account_id',true)::integer and answer_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_account_community(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=(select question_id from answer where answer_id=aid);
  update question set question_poll_minor_id = default where question_id=(select question_id from answer where answer_id=aid);
  --
  with d as (delete from answer_vote where answer_id=aid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from d join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , q as (update answer set answer_votes = answer_votes-answer_vote_votes from d where answer.answer_id=aid)
     , c as (update account_community set account_community_votes = account_community_votes-answer_vote_votes from r where account_community.account_id=r.account_id and account_community.community_id=r.community_id)
  insert into answer_vote_history(answer_id,account_id,answer_vote_history_at,answer_vote_history_votes)
  select answer_id,account_id,answer_vote_at,answer_vote_votes from d;
  --
  with i as (insert into answer_vote(answer_id,account_id,answer_vote_votes) values(aid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from i join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , c as (update account_community set account_community_votes = account_community_votes+answer_vote_votes from r where account_community.account_id=r.account_id and account_community.community_id=r.community_id)
  update answer set answer_votes = answer_votes+answer_vote_votes from i where answer.answer_id=aid returning answer_votes;
$$;
--
create function change_room_name(id integer, nname text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('not authorised') from my_account where not account_is_dev;
  select _error(400,'invalid room name') where nname is not null and not nname~'^[A-Za-zÀ-ÖØ-öø-ÿ]['' 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$';
  update room set room_name = nname where room_id=id;
$$;
--
create function change_room_image(id integer, image bytea) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('not authorised') from my_account where not account_is_dev;
  update room set room_image = image where room_id=id;
$$;
--
create function change_fonts(cid integer, regid integer, monoid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error(400,'invalid community') where not exists (select 1 from account_community where account_id=current_setting('custom.account_id',true)::integer and community_id=cid);
  update account_community set account_community_regular_font_id=regid, account_community_monospace_font_id=monoid where account_id=current_setting('custom.account_id',true)::integer and community_id=cid;
$$;
--
create function read_room(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  update room_account_x set room_account_x_latest_read_chat_id = (select max(chat_id) from chat where room_id=id) where room_id=id and account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function subscribe_question(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('already subscribed') where exists(select 1 from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id);
  insert into subscription(account_id,question_id) values(current_setting('custom.account_id',true)::integer,id);
$$;
--
create function unsubscribe_question(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('not subscribed') where not exists(select 1 from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id);
  delete from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id;
$$;
--
--
revoke all on all functions in schema world from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to world;', E'\n') from pg_views where schemaname='world' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to world;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='world' and proname!~'^_' );
end$$;
--
commit;
