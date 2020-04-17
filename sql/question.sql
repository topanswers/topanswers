create schema question;
grant usage on schema question to get,post;
set local search_path to question,api,pg_temp;
--
--
create view license with (security_barrier) as select license_id,license_name,license_href,license_is_versioned from db.license;
create view codelicense with (security_barrier) as select codelicense_id,codelicense_name,codelicense_is_versioned from db.codelicense;
create view kind with (security_barrier) as select kind_id,kind_description,sanction_ordinal,sanction_is_default from db.kind natural join db.sanction where community_id=get_community_id();
--
create view one with (security_barrier) as
select account_id,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense,account_license_name,account_codelicense_name,account_has_codelicense
      ,community_id,community_name,community_display_name,community_code_language,community_my_power,community_tables_are_monospace
      ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
      ,sesite_url
      ,question_id,question_title,question_markdown,question_license_name,question_se_question_id
      ,question_is_deleted,question_answered_by_me
      ,question_when,question_account_id,question_account_name,question_account_is_imported
      ,question_license_href,question_has_codelicense,question_codelicense_name,question_permit_later_license,question_permit_later_codelicense
      ,kind_allows_question_multivotes
     , question_account_id is not distinct from account_id question_account_is_me
     , coalesce(account_is_dev,false) account_is_dev
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from db.community
     natural join api._community
     natural left join (select account_id,account_is_dev,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense,account_license_name,account_codelicense_name
                             , account_codelicense_id<>1 and account_codelicense_name<>account_license_name account_has_codelicense
                        from db.account
                             natural join (select license_id account_license_id, license_name account_license_name from db.license) l
                             natural join (select codelicense_id account_codelicense_id, codelicense_name account_codelicense_name from db.codelicense) c
                        where account_id=get_account_id()) a
     natural left join db.communicant
     natural left join (select question_id,question_title,question_markdown,question_votes,question_se_question_id,question_crew_flags,question_active_flags,kind_allows_question_multivotes
                              ,question_permit_later_license,question_permit_later_codelicense
                             , sesite_url
                             , license_name||(case when question_permit_later_license then ' or later' else '' end) question_license_name
                             , license_href question_license_href
                             , codelicense_name||(case when question_permit_later_codelicense then ' or later' else '' end) question_codelicense_name
                             , account_id question_account_id
                             , account_name question_account_name
                             , account_is_imported question_account_is_imported
                             , exists(select 1 from db.answer a natural join db.login where login_uuid=get_login_uuid() and a.question_id=q.question_id) question_answered_by_me
                             , question_crew_flags>0 question_is_deleted
                             , codelicense_id<>1 and codelicense_name<>license_name question_has_codelicense
                             , extract('epoch' from current_timestamp-question_at) question_when
                        from db.question q natural join db.kind natural join db.account natural join db.community natural join db.license natural join db.codelicense natural join db.communicant
                             natural left join (select sesite_id question_sesite_id, sesite_url from db.sesite) s
                        where question_id=get_question_id()) q
where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
--
--
revoke all on all functions in schema question from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='question' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='question' and proname!~'^_' );
end$$;
--
--
create function _new_question_tag(aid integer, qid integer, tid integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where not exists (select 1 from question where question_id=qid and community_id=get_community_id());
  select _error('invalid tag') where not exists (select 1 from tag where tag_id=tid and community_id=get_community_id());
  --
  select _ensure_communicant(get_account_id(),community_id) from question where question_id=qid;;
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
create function new_tag(id integer) returns void language sql security definer set search_path=db,api,question,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error(429,'rate limit') where exists (select 1
                                                from question_tag_x_history
                                                where question_tag_x_history_added_by_account_id=get_account_id() and question_tag_x_added_at>current_timestamp-'1s'::interval);
  select _new_question_tag(get_account_id(),get_question_id(),id);
$$;
--
create function remove_tag(tid integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('invalid tag') where not exists (select 1 from tag where tag_id=tid and community_id=get_community_id());
  select _error(429,'rate limit') where exists (select 1
                                                from question_tag_x_history
                                                where question_tag_x_history_removed_by_account_id=get_account_id() and question_tag_x_removed_at>current_timestamp-'1s'::interval);
  --
  update question set question_poll_minor_id = default where question_id=get_question_id();
  --
  select question.remove_tag(tag_implies_id)
  from question_tag_x natural join tag t natural join (select tag_id tag_implies_id, tag_name parent_name from tag) z
  where question_id=get_question_id() and tag_id=tid and tag_name like parent_name||'%' and not exists(select 1 from question_tag_x natural join tag where question_id=get_question_id() and tag_id<>tid and tag_implies_id=t.tag_implies_id);
  --
  insert into question_tag_x_history(question_id,tag_id,community_id,question_tag_x_history_added_by_account_id,question_tag_x_history_removed_by_account_id,question_tag_x_added_at)
  select question_id,tid,community_id,account_id,get_account_id(),question_tag_x_at from question_tag_x where question_id=get_question_id() and tag_id=tid;
  --
  delete from question_tag_x where question_id=get_question_id() and tag_id=tid;
  update tag set tag_question_count = tag_question_count-1 where tag_id=tid;
$$;
--
create function new(knd integer, title text, markdown text, lic integer, lic_orlater boolean, codelic integer, codelic_orlater boolean) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid community') where not exists (select 1 from community where community_id=get_community_id());
  select _error('"or later" not allowed for '||license_name) from license where license_id=lic and lic_orlater and not license_is_versioned;
  select _error('"or later" not allowed for '||codelicense_name) from codelicense where codelicense_id=codelic and codelic_orlater and not codelicense_is_versioned;
  select _error(429,'rate limit') where (select count(*) from question where account_id=get_account_id() and question_at>current_timestamp-'10m'::interval)>3;
  select _ensure_communicant(get_account_id(),get_community_id());
  select _ensure_communicant(kind_account_id,get_community_id()) from kind where kind_id=knd and kind_account_id is not null;
  --
  with r as (insert into room(community_id) values(get_community_id()) returning room_id,community_id)
     , l as (insert into listener(account_id,room_id) select get_account_id(),room_id from r)
     , q as (insert into question(community_id,account_id,kind_id,question_title,question_markdown,question_room_id,license_id,codelicense_id,question_permit_later_license,question_permit_later_codelicense)
             select community_id,coalesce(kind_account_id,get_account_id()),knd,title,markdown,room_id,lic,codelic,lic_orlater,codelic_orlater
             from r cross join (select kind_id,kind_account_id from kind where kind_id=knd) k
             returning question_id)
     , h as (insert into question_history(question_id,account_id,question_history_title,question_history_markdown)
             select question_id,get_account_id(),title,markdown from q)
     , s as (insert into subscription(account_id,question_id) select get_account_id(),question_id from q)
  select question_id from q;
$$;
--
create function change(title text, markdown text) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('only author can edit this post kind') where exists (select 1 from question natural join kind where question_id=get_question_id() and not kind_can_all_edit and account_id<>get_account_id());
  select _error(429,'rate limit') where (select count(*)
                                         from question_history natural join (select question_id from question where account_id<>get_account_id()) z
                                         where account_id=get_account_id() and question_history_at>current_timestamp-'5m'::interval)>10;
  --
  with h as (insert into question_history(question_id,account_id,question_history_title,question_history_markdown) values(get_question_id(),get_account_id(),title,markdown) returning question_id,question_history_id)
    , nn as (select question_history_id,account_id from h natural join (select question_id,account_id from question) z where account_id<>get_account_id()
             union
             select question_history_id,account_id from h natural join subscription where account_id<>get_account_id())
     , n as (insert into notification(account_id) select account_id from nn returning *)
    , qn as (insert into question_notification(notification_id,question_history_id) select notification_id,question_history_id from nn natural join n)
  update account set account_notification_id = default where account_id in (select account_id from nn);
  --
  update question set question_title = title, question_markdown = markdown, question_change_at = default, question_poll_major_id = default where question_id=get_question_id();
  update answer set answer_summary = _markdownsummary(answer_markdown) where question_id=get_question_id() and answer_summary<>_markdownsummary(answer_markdown);
$$;
--
create function vote(votes integer) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('invalid number of votes cast') from question.one where votes<0 or votes>(case when kind_allows_question_multivotes then community_my_power else 1 end);
  select _error('cant vote on own question') from question.one where account_id=question_account_id;
  select _error('cant vote on this question type') from question natural join kind where question_id = get_question_id() and not kind_has_question_votes;
  select _error(429,'rate limit') where (select count(1) from question_vote where account_id=get_account_id() and question_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(1) from question_vote_history where account_id=get_account_id() and question_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_communicant(get_account_id(),get_community_id());
  update question set question_poll_minor_id = default where question_id=get_question_id();
  --
  with d as (delete from question_vote where question_id=get_question_id() and account_id=get_account_id() returning *)
     , r as (select question_id,community_id,q.account_id,question_vote_votes from d join question q using(question_id))
     , q as (update question set question_votes = question_votes-question_vote_votes from d where question.question_id=get_question_id())
     , a as (insert into communicant(account_id,community_id,communicant_votes,communicant_regular_font_id,communicant_monospace_font_id)
             select account_id,community_id,-question_vote_votes,community_regular_font_id,community_monospace_font_id from r natural join community
             on conflict on constraint communicant_pkey do update set communicant_votes = communicant.communicant_votes+excluded.communicant_votes)
  insert into question_vote_history(question_id,account_id,question_vote_history_at,question_vote_history_votes)
  select question_id,account_id,question_vote_at,question_vote_votes from d;
  --
  with i as (insert into question_vote(question_id,account_id,question_vote_votes) values(get_question_id(),get_account_id(),votes) returning *)
     , c as (insert into communicant(account_id,community_id,communicant_votes,communicant_regular_font_id,communicant_monospace_font_id)
             select account_id,community_id,question_vote_votes,community_regular_font_id,community_monospace_font_id
             from (select question_id,community_id,q.account_id,question_vote_votes from i join question q using(question_id)) z natural join community
             on conflict on constraint communicant_pkey do update set communicant_votes = communicant.communicant_votes+excluded.communicant_votes
             returning account_id,community_id,communicant_votes)
     , n as (insert into notification(account_id) select account_id from c where trunc(log(greatest(communicant_votes,1)))>trunc(log(greatest(communicant_votes-votes,1))) returning *)
    , sn as (insert into system_notification(notification_id,system_notification_message,system_notification_community_id)
             select notification_id
                  , 'Congratulations! You have reached the '||pow(10,trunc(log(greatest(communicant_votes,1))))||' star threshold, and can now award '||(1+trunc(log(greatest(communicant_votes,1))))||' stars on '
                       ||community_display_name||'.'
                   ,community_id
             from n natural join c natural join community)
  update question set question_votes = question_votes+question_vote_votes from i where question.question_id=get_question_id() returning question_votes;
$$;
--
create function subscribe() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('already subscribed') where exists(select 1 from subscription where account_id=get_account_id() and question_id=get_question_id());
  insert into subscription(account_id,question_id) values(get_account_id(),get_question_id());
$$;
--
create function unsubscribe() returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('not subscribed') where not exists(select 1 from subscription where account_id=get_account_id() and question_id=get_question_id());
  delete from subscription where account_id=get_account_id() and question_id=get_question_id();
$$;
--
create function flag(direction integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('invalid flag direction') where direction not in(-1,0,1);
  select _error(429,'rate limit') where (select count(1) from question_flag_history where account_id=get_account_id() and question_flag_history_at>current_timestamp-'1m'::interval)>6;
  --
  select _ensure_communicant(get_account_id(),get_community_id());
  --
  with d as (delete from question_flag where question_id=get_question_id() and account_id=get_account_id() returning *)
     , q as (update question set question_active_flags = question_active_flags-abs(d.question_flag_direction)
                               , question_flags = question_flags-(case when d.question_flag_is_crew then 0 else d.question_flag_direction end)
                               , question_crew_flags = question_crew_flags-(case when d.question_flag_is_crew then d.question_flag_direction else 0 end)
             from d
             where question.question_id=get_question_id())
  select question_id,account_id,question_flag_at,question_flag_direction,question_flag_is_crew from d;
  --
  with i as (insert into question_flag(question_id,account_id,question_flag_direction,question_flag_is_crew)
             select get_question_id(),account_id,direction,communicant_is_post_flag_crew
             from db.communicant
             where account_id=get_account_id() and community_id=get_community_id()
             returning *)
     , u as (update question set question_active_flags = question_active_flags+abs(i.question_flag_direction)
                               , question_flags = question_flags+(case when i.question_flag_is_crew then 0 else i.question_flag_direction end)
                               , question_crew_flags = question_crew_flags+(case when i.question_flag_is_crew then i.question_flag_direction else 0 end)
             from i
             where question.question_id=get_question_id())
     , h as (insert into question_flag_history(question_id,account_id,question_flag_history_direction,question_flag_history_is_crew)
             select question_id,account_id,question_flag_direction,question_flag_is_crew from i
             returning question_flag_history_id,question_flag_history_direction)
    , nn as (select question_flag_history_id,account_id
             from h cross join (select account_id from communicant where community_id=get_community_id() and communicant_is_post_flag_crew and account_id<>get_account_id()) c
             where question_flag_history_direction>0)
     , n as (insert into notification(account_id) select account_id from nn returning *)
   , qfn as (insert into question_flag_notification(notification_id,question_flag_history_id) select notification_id,question_flag_history_id from nn natural join n)
     , l as (insert into listener(account_id,room_id,listener_latest_read_chat_id)
             select get_account_id(),get_room_id(),max(chat_id) from chat where room_id=get_room_id()
             on conflict on constraint listener_pkey do update set listener_latest_read_chat_id=excluded.listener_latest_read_chat_id)
  update account set account_notification_id = default where account_id in (select account_id from n);
$$;
--
--
revoke all on all functions in schema community from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='question' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='question' and proname!~'^_' );
end$$;
