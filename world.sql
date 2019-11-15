/*
\i ~/git/world.sql
*/
begin;
--
drop schema if exists world cascade;
drop role if exists world;
create user world;
create schema world;
grant usage on schema world to world;
set local schema 'world';
--
create function _error(integer,text) returns void language plpgsql as $$begin raise exception '%', $2 using errcode='H0'||$1; end;$$;
create function _error(text) returns void language sql as $$select _error(403,$1);$$;
--
create view community with (security_barrier) as
select community_id,community_name,community_room_id,community_dark_shade,community_mid_shade,community_light_shade,community_highlight_color
     , (current_setting('custom.account_id',true)::integer<100)::integer+trunc(log(greatest(account_community_votes,0)+1)) community_my_power
from db.community natural left join (select account_is_dev from db.account where account_id=current_setting('custom.account_id',true)::integer) y natural left join (select community_id,account_community_votes from db.account_community where account_id=current_setting('custom.account_id',true)::integer) z
where community_name<>'private' or account_is_dev;
--
create view login with (security_barrier) as select account_id,login_resizer_percent, true as login_is_me from db.login where login_uuid=current_setting('custom.uuid',true)::uuid;
create view account with (security_barrier) as select account_id,account_name,account_image,account_change_id,account_change_at, account_id=current_setting('custom.account_id',true)::integer account_is_me from db.account;
create view my_account with (security_barrier) as select account_id,account_name,account_image,account_uuid,account_is_dev,account_license_id,account_codelicense_id from db.account where account_id=current_setting('custom.account_id',true)::integer;
create view account_community with (security_barrier) as select account_id,community_id,account_community_votes from db.account_community;
--
create view room with (security_barrier) as
select community_id,room_id,room_name
     , current_setting('custom.account_id',true)::integer is not null and (room_type='public' or account_id is not null) room_can_chat
     , exists(select 1 from db.question where question_room_id=room_id) room_is_for_question
from db.room natural join world.community natural left outer join (select * from db.account_room_x where account_id=current_setting('custom.account_id',true)::integer) z
where room_type<>'private' or account_id is not null;
--
create view room_account_x with (security_barrier) as select room_id,account_id,room_account_x_latest_chat_at from db.room_account_x natural join world.room where room_account_x_latest_chat_at>(current_timestamp-'7d'::interval);
--
create view chat with (security_barrier) as
select community_id,room_id,account_id,chat_id,chat_reply_id,chat_at,chat_change_id,chat_change_at,chat_markdown
     , (select count(*) from db.chat_flag where chat_id=chat.chat_id) chat_flag_count
     , (select count(*) from db.chat_star where chat_id=chat.chat_id) chat_star_count
from db.chat natural join room;
--
create view chat_notification with (security_barrier) as select chat_id,chat_notification_at from db.chat_notification where account_id=current_setting('custom.account_id',true)::integer;
create view chat_flag with (security_barrier) as select chat_id,chat_flag_at from db.chat_flag where account_id=current_setting('custom.account_id',true)::integer;
create view chat_star with (security_barrier) as select chat_id,chat_star_at from db.chat_star where account_id=current_setting('custom.account_id',true)::integer;
create view chat_year with (security_barrier) as select room_id,chat_year,chat_year_count from db.chat_year;
create view chat_month with (security_barrier) as select room_id,chat_year,chat_month,chat_month_count from db.chat_month;
create view chat_day with (security_barrier) as select room_id,chat_year,chat_month,chat_day,chat_day_count from db.chat_day;
create view chat_hour with (security_barrier) as select room_id,chat_year,chat_month,chat_day,chat_hour,chat_hour_count from db.chat_hour;
create view question_type_enums with (security_barrier) as select unnest(enum_range(null::db.question_type_enum)) question_type;
--
create view question with (security_barrier) as
select question_id,community_id,account_id,question_type,question_at,question_title,question_markdown,question_room_id,question_change_at,question_votes,license_id,codelicense_id,question_poll_id
     , coalesce(question_votes>=community_my_power,false) question_have_voted
     , coalesce(question_vote_votes,0) question_votes_from_me
     , exists(select account_id from db.answer where question_id=question.question_id and account_id=current_setting('custom.account_id',true)::integer) question_answered_by_me
     , question_at<>question_change_at question_has_history
from db.question natural join community natural left join (select question_id,question_vote_votes from db.question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_votes>0) z;
--
create view question_history with (security_barrier) as
select question_id,question_history_at,question_history_markdown,question_history_title
     , coalesce(lag(account_id) over(partition by question_id order by question_history_at),first_value(account_id) over(partition by question_id order by question_history_at desc)) account_id
from (select question_id,account_id,question_history_at,question_history_markdown,question_history_title from db.question_history
      union all
      select question_id,account_id,question_change_at,question_markdown,question_title from db.question) z;
--
create view answer with (security_barrier) as
select answer_id,question_id,account_id,answer_at,answer_markdown,answer_change_at,answer_votes,license_id,codelicense_id
     , coalesce(answer_vote_votes>=community_my_power,false) answer_have_voted
     , coalesce(answer_vote_votes,0) answer_votes_from_me
     , answer_at<>answer_change_at answer_has_history
from db.answer natural join (select question_id,community_id from question) z natural join community natural left join (select answer_id,answer_vote_votes from db.answer_vote where account_id=current_setting('custom.account_id',true)::integer and answer_vote_votes>0) zz;
--
create view answer_history with (security_barrier) as
select answer_id,answer_history_at,answer_history_markdown
     , coalesce(lag(account_id) over(partition by answer_id order by answer_history_at),first_value(account_id) over(partition by answer_id order by answer_history_at desc)) account_id
from (select answer_id,account_id,answer_history_at,answer_history_markdown from db.answer_history
      union all
      select answer_id,account_id,answer_change_at,answer_markdown from db.answer) z;
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
--
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
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,world,pg_temp as $$
declare
  rid integer;
  cid integer;
begin
  insert into room(community_id) values(0) returning room_id into rid;
  insert into community(community_name,community_room_id) values(cname,rid) returning community_id into cid;
  update room set community_id=cid where room_id=rid;
  return cid;
end$$;
--
create function new_chat(roomid integer, msg text, replyid integer, pingids integer[]) returns bigint language sql security definer set search_path=db,world,pg_temp as $$
  select _error('room does not exist') where not exists(select 1 from room where room_id=roomid);
  select _error('access denied') where not exists(select 1 from world.room where room_id=roomid and room_can_chat);
  select _error(413,'message too long') where length(msg)>5000;
  --
  delete from chat_notification where chat_id=replyid and account_id=current_setting('custom.account_id',true)::integer;
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
     , n as (insert into chat_notification(chat_id,account_id)
             select chat_id,(select account_id from chat where chat_id=replyid) from i where replyid is not null and not (select account_is_me from chat natural join world.account where chat_id=replyid))
     , p as (insert into chat_notification(chat_id,account_id)
             select chat_id,account_id
             from i cross join (select account_id from world.account where account_id in (select * from unnest(pingids) except select account_id from chat where chat_id=replyid) and not account_is_me) z)
     , a as (insert into room_account_x(room_id,account_id)
             select roomid,current_setting('custom.account_id',true)::integer
             from room
             where room_id=roomid
             on conflict on constraint room_account_x_pkey do update set room_account_x_latest_chat_at=default)
  select chat_id from i;
$$;
--
create function dismiss_notification(id integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  delete from chat_notification where chat_id=id and account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function new_account(luuid uuid) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1 from account where account_create_at>current_timestamp-'1m'::interval);
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
create function change_account_name(nname text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('invalid username') where nname is not null and not nname~'^[A-Za-zÀ-ÖØ-öø-ÿ][ 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$';
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
create function new_question(cid integer, typ db.question_type_enum, title text, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid community') where not exists (select 1 from community where community_id=cid);
  select _error(429,'rate limit') where exists (select 1 from question where account_id=current_setting('custom.account_id',true)::integer and question_at>current_timestamp-'5m'::interval and account_id>2);
  --
  with r as (insert into room(community_id) values(cid) returning room_id)
     , q as (insert into question(community_id,account_id,question_type,question_title,question_markdown,question_room_id,license_id,codelicense_id)
             select cid, current_setting('custom.account_id',true)::integer, typ, title, markdown, room_id, lic, codelic from r returning question_id)
  select question_id from q;
$$;
--
create function change_question(id integer, title text, markdown text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('only author can edit blog post') where exists (select 1 from question where question_id=id and question_type='blog' and account_id<>current_setting('custom.account_id',true)::integer);
  select _error(429,'rate limit') where (select count(*)
                                         from question_history natural join (select question_id from question where account_id<>current_setting('custom.account_id',true)::integer) z
                                         where account_id=current_setting('custom.account_id',true)::integer and question_history_at>current_timestamp-'5m'::interval)>10;
  --
  insert into question_history(question_id,account_id,question_history_at,question_history_title,question_history_markdown)
  select question_id,current_setting('custom.account_id',true)::integer,question_change_at,question_title,question_markdown
  from question
  where question_id=id;
  --
  update question set question_title = title, question_markdown = markdown, question_change_at = default, question_poll_id = default where question_id=id;
$$;
--
create function new_answer(qid integer, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _error(429,'rate limit') where exists (select 1 from answer where account_id=current_setting('custom.account_id',true)::integer and answer_at>current_timestamp-'1m'::interval and account_id>2);
  --
  update question set question_poll_id = default where question_id=qid;
  insert into answer(question_id,account_id,answer_markdown,license_id,codelicense_id) values(qid, current_setting('custom.account_id',true)::integer, markdown, lic, codelic) returning answer_id;
$$;
--
create function change_answer(id integer, markdown text) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error(429,'rate limit') where (select count(*)
                                         from answer_history natural join (select answer_id from answer where account_id<>current_setting('custom.account_id',true)::integer) z
                                         where account_id=current_setting('custom.account_id',true)::integer and answer_history_at>current_timestamp-'5m'::interval)>10;
  --
  update question set question_poll_id = default where question_id=(select question_id from answer where answer_id=id);
  --
  insert into answer_history(answer_id,account_id,answer_history_at,answer_history_markdown) select answer_id,current_setting('custom.account_id',true)::integer,answer_change_at,answer_markdown from answer where answer_id=id;
  update answer set answer_markdown = markdown, answer_change_at = default where answer_id=id;
$$;
--
create function new_question_tag(qid integer, tid integer) returns void language sql security definer set search_path=db,world,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from world.question where question_id=qid);
  select _error('invalid tag') where not exists (select 1 from world.tag where tag_id=tid);
  select _error(429,'rate limit') where exists (select 1
                                            from question_tag_x_history
                                            where question_tag_x_history_added_by_account_id=current_setting('custom.account_id',true)::integer and question_tag_x_added_at>current_timestamp-'1s'::interval);
  --
  update question set question_poll_id = default where question_id=qid;
  --
  with recursive w(tag_id,next_id,path,cycle) as (select tag_id,tag_implies_id,array[tag_id],false from tag where tag_id=tid
                                                  union all
                                                  select tag.tag_id,tag.tag_implies_id,path||tag.tag_id,tag.tag_id=any(w.path) from w join tag on tag.tag_id=w.next_id where not cycle)
     , i as (insert into question_tag_x(question_id,tag_id,community_id,account_id)
             select qid,tag_id,community_id,current_setting('custom.account_id',true)::integer
             from w natural join tag
             where tag_id not in (select tag_id from question_tag_x where question_id=qid)
             returning tag_id)
  update tag set tag_question_count = tag_question_count+1 where tag_id in (select tag_id from i);
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
  update question set question_poll_id = default where question_id=qid;
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
  select _error(429,'rate limit') where (select 1 from question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select 1 from question_vote_history where account_id=current_setting('custom.account_id',true)::integer and question_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  update question set question_poll_id = default where question_id=qid;
  --
  with d as (delete from question_vote where question_id=qid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select question_id,community_id,q.account_id,question_vote_votes from d join question q using(question_id))
     , q as (update question set question_votes = question_votes-question_vote_votes from d where question.question_id=qid)
     , a as (insert into account_community(account_id,community_id,account_community_votes)
             select account_id,community_id,-question_vote_votes from r
             on conflict on constraint account_community_pkey do update set account_community_votes = account_community.account_community_votes+excluded.account_community_votes)
  insert into question_vote_history(question_id,account_id,question_vote_history_at,question_vote_history_votes)
  select question_id,account_id,question_vote_at,question_vote_votes from d;
  --
  with i as (insert into question_vote(question_id,account_id,question_vote_votes) values(qid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , c as (insert into account_community(account_id,community_id,account_community_votes)
             select account_id,community_id,question_vote_votes from (select question_id,community_id,q.account_id,question_vote_votes from i join question q using(question_id)) z
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
  update question set question_poll_id = default where question_id=(select question_id from answer where answer_id=aid);
  --
  with d as (delete from answer_vote where answer_id=aid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from d join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , q as (update answer set answer_votes = answer_votes-answer_vote_votes from d where answer.answer_id=aid)
     , a as (insert into account_community(account_id,community_id,account_community_votes)
             select account_id,community_id,-answer_vote_votes from r
             on conflict on constraint account_community_pkey do update set account_community_votes = account_community.account_community_votes+excluded.account_community_votes)
  insert into answer_vote_history(answer_id,account_id,answer_vote_history_at,answer_vote_history_votes)
  select answer_id,account_id,answer_vote_at,answer_vote_votes from d;
  --
  with i as (insert into answer_vote(answer_id,account_id,answer_vote_votes) values(aid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , c as (insert into account_community(account_id,community_id,account_community_votes)
             select account_id,community_id,answer_vote_votes
             from (select answer_id,community_id,a.account_id,answer_vote_votes from i join answer a using(answer_id) natural join (select question_id,community_id from question) q) z
             on conflict on constraint account_community_pkey do update set account_community_votes = account_community.account_community_votes+excluded.account_community_votes)
  update answer set answer_votes = answer_votes+answer_vote_votes from i where answer.answer_id=aid returning answer_votes;
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
