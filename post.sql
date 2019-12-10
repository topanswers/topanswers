create user post;
create schema post;
grant usage on schema get to post;
grant usage on schema post to post;
grant usage on schema x_pg_trgm to post;
set local schema 'post';
--
create function _error(integer,text) returns void language plpgsql as $$begin raise exception '%', $2 using errcode='H0'||$1; end;$$;
create function _error(text) returns void language sql as $$select _error(403,$1);$$;
--
--
create function login(luuid uuid) returns boolean language sql security definer set search_path=db,post,pg_temp as $$
  select shared.login(luuid);
$$;
--
create function _new_community(cname text) returns integer language plpgsql security definer set search_path=db,post,pg_temp as $$
declare
  rid integer;
  cid integer;
begin
  insert into room(community_id) values(0) returning room_id into rid;
  insert into community(community_name,community_room_id,community_display_name) values(cname,rid,initcap(cname)) returning community_id into cid;
  --
  insert into communicant(account_id,community_id,communicant_se_user_id,communicant_regular_font_id,communicant_monospace_font_id)
  select 208,cid,0,community_regular_font_id,community_monospace_font_id from community where community_id=cid;
  --
  update room set community_id=cid where room_id=rid;
  return cid;
end$$;
--
create function _create_seuser(cid integer, seuid integer, seuname text) returns integer language plpgsql security definer set search_path=db,post,pg_temp as $$
declare
  id integer;
begin
  if exists(select 1 from communicant where community_id=cid and communicant_se_user_id=seuid) then
    select account_id from communicant where community_id=cid and communicant_se_user_id=seuid into id;
  else
    insert into account(account_name,account_license_id,account_codelicense_id,account_is_imported) values(replace(seuname,'-',' '),4,1,true) returning account_id into id;
    --
    insert into communicant(account_id,community_id,communicant_se_user_id,communicant_regular_font_id,communicant_monospace_font_id)
    select id,cid,seuid,community_regular_font_id,community_monospace_font_id from community where community_id=cid;
  end if;
  return id;
end;
$$;
--
create function _ensure_communicant(aid integer, cid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  insert into communicant(account_id,community_id,communicant_regular_font_id,communicant_monospace_font_id)
  select aid,cid,community_regular_font_id,community_monospace_font_id from community where community_id=cid
  on conflict on constraint communicant_pkey do nothing;
$$;
--
create function new_chat(roomid integer, msg text, replyid integer, pingids integer[]) returns bigint language sql security definer set search_path=db,post,pg_temp as $$
  select _error('room does not exist') where not exists(select 1 from room where room_id=roomid);
  select _error('access denied') where not exists(select 1 from shared.room where room_id=roomid and room_can_chat);
  select _error(413,'message too long') where length(msg)>5000;
  select _ensure_communicant(current_setting('custom.account_id',true)::integer,community_id) from room where room_id=roomid;;
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
             select chat_id,(select account_id from chat where chat_id=replyid) from i where replyid is not null and (select account_id from chat where chat_id=replyid)<>current_setting('custom.account_id',true)::integer
             returning *)
     , a as (update account set account_notification_id = default from n where account.account_id=n.account_id)
     , p as (insert into chat_notification(chat_id,account_id)
             select chat_id,account_id
             from i cross join (select account_id from account where account_id in (select * from unnest(pingids) except select account_id from chat where chat_id=replyid) and account_id<>current_setting('custom.account_id',true)::integer) z)
     , r as (insert into room_account_x(room_id,account_id,room_account_x_latest_read_chat_id)
             select room_id,current_setting('custom.account_id',true)::integer,chat_id from i
             on conflict on constraint room_account_x_pkey do update set room_account_x_latest_chat_at=default, room_account_x_latest_read_chat_id=excluded.room_account_x_latest_read_chat_id)
  select chat_id from i;
$$;
--
create function change_chat(id integer, msg text) returns void language sql security definer set search_path=db,post,pg_temp as $$
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
create function dismiss_chat_notification(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  with d as (delete from chat_notification where chat_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_question_notification(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  with d as (delete from question_notification where question_history_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function dismiss_answer_notification(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  with d as (delete from answer_notification where answer_history_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
  update account set account_notification_id = default from d where account.account_id=d.account_id;
$$;
--
create function new_account(luuid uuid) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(429,'rate limit') where (select count(*) from account where account_create_at>current_timestamp-'5m'::interval and account_is_imported=false)>5;
  --
  with a as (insert into account default values returning account_id)
  insert into login(account_id,login_uuid) select account_id,luuid from a returning account_id;
$$;
--
create function link_account(luuid uuid, pn bigint) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('invalid pin') where not exists (select 1 from pin where pin_number=pn);
  insert into login(account_id,login_uuid) select account_id,luuid from pin where pin_number=pn and pin_at>current_timestamp-'1 min'::interval returning account_id;
$$;
--
create function recover_account(luuid uuid, auuid uuid) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('invalid recovery key') where not exists (select 1 from account where account_uuid=auuid);
  insert into login(account_id,login_uuid) select account_id,luuid from account where account_uuid=auuid returning account_id;
$$;
--
create function regenerate_account_uuid() returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('not logged in') where current_setting('custom.account_id',true)::integer is null;
  update account set account_uuid = default where account_id = current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_name(nname text) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('invalid username') where nname is not null and not nname~'^[0-9[:alpha:]][-'' .0-9[:alpha:]]{1,25}[0-9[:alpha:]]$';
  update account set account_name = nname, account_change_id = default, account_change_at = default where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_image(image bytea) returns void language sql security definer set search_path=db,post,pg_temp as $$
  update account set account_image = image, account_change_id = default, account_change_at = default where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_license_id(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  update account set account_license_id = id where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_account_codelicense_id(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  update account set account_codelicense_id = id where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function change_resizer(perc integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('invalid percent') where perc<0 or perc>100;
  update login set login_resizer_percent = perc where login_uuid=current_setting('custom.uuid',true)::uuid;
$$;
--
create function authenticate_pin(num bigint) returns void language sql security definer set search_path=db,post,pg_temp as $$
  delete from pin where pin_number=num;
  insert into pin(pin_number,account_id) select num,account_id from account where account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function set_chat_flag(cid bigint) returns bigint language sql security definer set search_path=db,post,pg_temp as $$
  select _error('cant flag own message') where exists(select 1 from chat where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('already flagged') where exists(select 1 from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join shared.room where chat_id=cid and room_can_chat);
  insert into chat_flag(chat_id,account_id) select chat_id,current_setting('custom.account_id',true)::integer from chat where chat_id=cid;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function remove_chat_flag(cid bigint) returns bigint language sql security definer set search_path=db,post,pg_temp as $$
  select _error('not already flagged') where not exists(select 1 from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join shared.room where chat_id=cid and room_can_chat);
  delete from chat_flag where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function set_chat_star(cid bigint) returns bigint language sql security definer set search_path=db,post,pg_temp as $$
  select _error('cant star own message') where exists(select 1 from chat where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('already starred') where exists(select 1 from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join shared.room where chat_id=cid and room_can_chat);
  insert into chat_star(chat_id,account_id) select chat_id,current_setting('custom.account_id',true)::integer from chat where chat_id=cid;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function remove_chat_star(cid bigint) returns bigint language sql security definer set search_path=db,post,pg_temp as $$
  select _error('not already starred') where not exists(select 1 from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('access denied') where not exists(select 1 from chat natural join shared.room where chat_id=cid and room_can_chat);
  delete from chat_star where chat_id=cid and account_id=current_setting('custom.account_id',true)::integer;
  update chat set chat_change_id = default where chat_id=cid returning chat_change_id;
$$;
--
create function _new_question_tag(aid integer, qid integer, tid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from question natural join shared.community where question_id=qid);
  select _error('invalid tag') where not exists (select 1 from tag natural join shared.community where tag_id=tid);
  --
  select _ensure_communicant(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=qid;;
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
create function new_question_tag(qid integer, tid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1
                                                from question_tag_x_history
                                                where question_tag_x_history_added_by_account_id=current_setting('custom.account_id',true)::integer and question_tag_x_added_at>current_timestamp-'1s'::interval);
  select _new_question_tag(current_setting('custom.account_id',true)::integer,qid,tid);
$$;
--
create function new_sequestion_tag(qid integer, tid integer, uid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _new_question_tag(uid,qid,tid);
$$;
--
create function _new_question(cid integer, aid integer, typ db.question_type_enum, title text, markdown text, lic integer, codelic integer, seqid integer)
                returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid community') where not exists (select 1 from community where community_id=cid);
  select _ensure_communicant(aid,cid);
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
create function new_question(cid integer, typ db.question_type_enum, title text, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1 from question where account_id=current_setting('custom.account_id',true)::integer and question_at>current_timestamp-'5m'::interval and account_id>2);
  select _new_question(cid,current_setting('custom.account_id',true)::integer,typ,title,markdown,lic,codelic,null);
$$;
--
create function new_sequestion(cid integer, title text, markdown text, tags text, seqid integer, seuid integer, seuname text) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from question where community_id=cid and question_se_question_id=seqid);
  --
  with u as (select _create_seuser(cid,seuid,seuname) uid)
     , q as (select uid, _new_question(cid,uid,'question',title,markdown,4,1,seqid) qid from u)
     , t as (select new_sequestion_tag(qid,tag_id,uid) from q cross join tag natural join (select * from regexp_split_to_table(tags,' ') tag_name) z where community_id=cid)
  select qid from q cross join (select count(1) cn from t) z;
$$;
--
create function new_sequestionanon(cid integer, title text, markdown text, tags text, seqid integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from question where community_id=cid and question_se_question_id=seqid);
  --
  with u as (select account_id uid from communicant where community_id=cid and communicant_se_user_id=0)
     , q as (select uid, _new_question(cid,uid,'question',title,markdown,4,1,seqid) qid from u)
     , t as (select new_sequestion_tag(qid,tag_id,uid) from q cross join tag natural join (select * from regexp_split_to_table(tags,' ') tag_name) z where community_id=cid)
  select qid from q cross join (select count(1) cn from t) z;
$$;
--
create function change_question(id integer, title text, markdown text) returns void language sql security definer set search_path=db,post,pg_temp as $$
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
create function _new_answer(qid integer, aid integer, markdown text, lic integer, codelic integer, seaid integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from question natural join shared.community where question_id=qid);
  select _ensure_communicant(aid,community_id) from question where question_id=qid;
  --
  with i as (insert into answer(question_id,account_id,answer_markdown,license_id,codelicense_id,answer_se_answer_id) values(qid,aid,markdown,lic,codelic,seaid) returning answer_id)
     , h as (insert into answer_history(answer_id,account_id,answer_history_markdown) select answer_id,aid,markdown from i returning answer_id,answer_history_id)
     , n as (insert into answer_notification(answer_history_id,account_id)
             select answer_history_id,account_id from h cross join (select account_id from subscription where question_id=qid and account_id<>current_setting('custom.account_id',true)::integer) z)
  select answer_id from i;
$$;
--
create function new_answer(qid integer, markdown text, lic integer, codelic integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(429,'rate limit') where exists (select 1 from answer where account_id=current_setting('custom.account_id',true)::integer and answer_at>current_timestamp-'1m'::interval and account_id>2);
  --
  update question set question_poll_major_id = default where question_id=qid;
  select _new_answer(qid,current_setting('custom.account_id',true)::integer,markdown,lic,codelic,null);
$$;
--
create function new_seanswer(qid integer, markdown text, seaid integer, seuid integer, seuname text) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from answer natural join (select question_id,community_id from question) q where question_id=qid and answer_se_answer_id=seaid);
  select _new_answer(qid,_create_seuser(community_id,seuid,seuname),markdown,4,1,seaid) from question where question_id=qid;
$$;
--
create function new_seansweranon(qid integer, markdown text, seaid integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error(400,'already imported') where exists (select 1 from answer natural join (select question_id,community_id from question) q where question_id=qid and answer_se_answer_id=seaid);
  select _new_answer(qid,(select account_id from communicant where community_id=question.community_id and communicant_se_user_id=0),markdown,4,1,seaid) from question where question_id=qid;
$$;
--
create function change_answer(id integer, markdown text) returns void language sql security definer set search_path=db,post,pg_temp as $$
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
create function remove_question_tag(qid integer, tid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid question') where not exists (select 1 from question natural join shared.community where question_id=qid);
  select _error('invalid tag') where not exists (select 1 from tag natural join shared.community where tag_id=tid);
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
create function vote_question(qid integer, votes integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid number of votes cast') where votes<0 or votes>(select community_my_power from question natural join shared.community where question_id=qid);
  select _error('invalid question') where not exists (select 1 from question natural join shared.community where question_id=qid);
  select _error('cant vote on own question') where exists (select 1 from question where question_id=qid and account_id=current_setting('custom.account_id',true)::integer);
  select _error('cant vote on this question type') where exists (select 1 from question where question_id=qid and question_type='question');
  select _error(429,'rate limit') where (select count(1) from question_vote where account_id=current_setting('custom.account_id',true)::integer and question_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(1) from question_vote_history where account_id=current_setting('custom.account_id',true)::integer and question_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_communicant(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=qid;;
  update question set question_poll_minor_id = default where question_id=qid;
  --
  with d as (delete from question_vote where question_id=qid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select question_id,community_id,q.account_id,question_vote_votes from d join question q using(question_id))
     , q as (update question set question_votes = question_votes-question_vote_votes from d where question.question_id=qid)
     , a as (insert into communicant(account_id,community_id,communicant_votes,communicant_regular_font_id,communicant_monospace_font_id)
             select account_id,community_id,-question_vote_votes,community_regular_font_id,community_monospace_font_id from r natural join community
             on conflict on constraint communicant_pkey do update set communicant_votes = communicant.communicant_votes+excluded.communicant_votes)
  insert into question_vote_history(question_id,account_id,question_vote_history_at,question_vote_history_votes)
  select question_id,account_id,question_vote_at,question_vote_votes from d;
  --
  with i as (insert into question_vote(question_id,account_id,question_vote_votes) values(qid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , c as (insert into communicant(account_id,community_id,communicant_votes,communicant_regular_font_id,communicant_monospace_font_id)
             select account_id,community_id,question_vote_votes,community_regular_font_id,community_monospace_font_id
             from (select question_id,community_id,q.account_id,question_vote_votes from i join question q using(question_id)) z natural join community
             on conflict on constraint communicant_pkey do update set communicant_votes = communicant.communicant_votes+excluded.communicant_votes)
  update question set question_votes = question_votes+question_vote_votes from i where question.question_id=qid returning question_votes;
$$;
--
create function vote_answer(aid integer, votes integer) returns integer language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid number of votes cast') where votes<0 or votes>(select community_my_power from answer natural join (select question_id,community_id from question) q natural join shared.community where answer_id=aid);
  select _error('invalid answer') where not exists (select 1 from answer natural join (select question_id,community_id from question) q natural join shared.community where answer_id=aid);
  select _error('cant vote on own answer') where exists (select 1 from answer where answer_id=aid and account_id=current_setting('custom.account_id',true)::integer);
  select _error(429,'rate limit') where (select count(*) from answer_vote where account_id=current_setting('custom.account_id',true)::integer and answer_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(*) from answer_vote_history where account_id=current_setting('custom.account_id',true)::integer and answer_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_communicant(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=(select question_id from answer where answer_id=aid);
  update question set question_poll_minor_id = default where question_id=(select question_id from answer where answer_id=aid);
  --
  with d as (delete from answer_vote where answer_id=aid and account_id=current_setting('custom.account_id',true)::integer returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from d join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , q as (update answer set answer_votes = answer_votes-answer_vote_votes from d where answer.answer_id=aid)
     , c as (update communicant set communicant_votes = communicant_votes-answer_vote_votes from r where communicant.account_id=r.account_id and communicant.community_id=r.community_id)
  insert into answer_vote_history(answer_id,account_id,answer_vote_history_at,answer_vote_history_votes)
  select answer_id,account_id,answer_vote_at,answer_vote_votes from d;
  --
  with i as (insert into answer_vote(answer_id,account_id,answer_vote_votes) values(aid,current_setting('custom.account_id',true)::integer,votes) returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from i join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , c as (update communicant set communicant_votes = communicant_votes+answer_vote_votes from r where communicant.account_id=r.account_id and communicant.community_id=r.community_id)
  update answer set answer_votes = answer_votes+answer_vote_votes from i where answer.answer_id=aid returning answer_votes;
$$;
--
create function change_room_name(id integer, nname text) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('not authorised') from account where account_id=current_setting('custom.account_id',true)::integer and not account_is_dev;
  select _error('invalid room') where not exists (select 1 from shared.room where room_id=id);
  select _error(400,'invalid room name') where nname is not null and not nname~'^[0-9[:alpha:]][-'' ,.0-9[:alpha:]]{1,25}[0-9[:alpha:]]$';
  update room set room_name = nname where room_id=id;
$$;
--
create function change_room_image(id integer, image bytea) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('not authorised') from account where account_id=current_setting('custom.account_id',true)::integer and not account_is_dev;
  select _error('invalid room') where not exists (select 1 from shared.room where room_id=id);
  update room set room_image = image where room_id=id;
$$;
--
create function change_fonts(cid integer, regid integer, monoid integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error(400,'invalid community') where not exists (select 1 from communicant where account_id=current_setting('custom.account_id',true)::integer and community_id=cid);
  update communicant set communicant_regular_font_id=regid, communicant_monospace_font_id=monoid where account_id=current_setting('custom.account_id',true)::integer and community_id=cid;
$$;
--
create function read_room(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid room') where not exists (select 1 from shared.room where room_id=id);
  update room_account_x set room_account_x_latest_read_chat_id = (select max(chat_id) from chat where room_id=id) where room_id=id and account_id=current_setting('custom.account_id',true)::integer;
$$;
--
create function subscribe_question(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('already subscribed') where exists(select 1 from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id);
  insert into subscription(account_id,question_id) values(current_setting('custom.account_id',true)::integer,id);
$$;
--
create function unsubscribe_question(id integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('not subscribed') where not exists(select 1 from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id);
  delete from subscription where account_id=current_setting('custom.account_id',true)::integer and question_id=id;
$$;
--
create function flag_question(id integer, direction integer) returns void language sql security definer set search_path=db,post,pg_temp as $$
  select _error('access denied') where current_setting('custom.account_id',true)::integer is null;
  select _error('invalid flag direction') where direction not in(-1,0,1);
  select _error('invalid question') where not exists (select 1 from question natural join shared.community where question_id=id);
  select _error('cant flag own question') where exists (select 1 from question where question_id=id and account_id=current_setting('custom.account_id',true)::integer);
  select _error(429,'rate limit') where (select count(1) from question_flag_history where account_id=current_setting('custom.account_id',true)::integer and question_flag_history_at>current_timestamp-'1m'::interval)>6;
  --
  select _ensure_communicant(current_setting('custom.account_id',true)::integer,community_id) from question where question_id=id;
  --
  with d as (delete from question_flag where question_id=id and account_id=current_setting('custom.account_id',true)::integer returning *)
     , q as (update question set question_active_flags = question_active_flags-abs(d.question_flag_direction)
                               , question_flags = question_flags-(case when d.question_flag_is_crew then 0 else d.question_flag_direction end)
                               , question_crew_flags = question_crew_flags-(case when d.question_flag_is_crew then d.question_flag_direction else 0 end)
             from d
             where question.question_id=id)
  select question_id,account_id,question_flag_at,question_flag_direction,question_flag_is_crew from d;
  --
  with i as (insert into question_flag(question_id,account_id,question_flag_direction,question_flag_is_crew)
             select id,account_id,direction,communicant_is_post_flag_crew
             from db.communicant
             where account_id=current_setting('custom.account_id',true)::integer and community_id=(select community_id from db.question where question_id=id)
             returning *)
     , u as (update question set question_active_flags = question_active_flags+abs(i.question_flag_direction)
                               , question_flags = question_flags+(case when i.question_flag_is_crew then 0 else i.question_flag_direction end)
                               , question_crew_flags = question_crew_flags+(case when i.question_flag_is_crew then i.question_flag_direction else 0 end)
             from i
             where question.question_id=id)
  insert into question_flag_history(question_id,account_id,question_flag_history_direction,question_flag_history_is_crew)
  select question_id,account_id,question_flag_direction,question_flag_is_crew from i;
$$;
--
create function new_import(cid integer, qid text, aids text) returns void language sql security definer set search_path=db,post,pg_temp as $$
  insert into import(account_id,community_id,import_qid,import_aids) values(current_setting('custom.account_id',true)::integer,cid,coalesce(qid,''),coalesce(aids,''));
$$;
--
--
revoke all on all functions in schema post from public;
do $$
begin
  execute (select string_agg('grant select on get.'||viewname||' to post;', E'\n') from pg_views where schemaname='get' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='post' and proname!~'^_' );
end$$;
