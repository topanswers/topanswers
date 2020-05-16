create schema import;
grant usage on schema import to get,post;
set local search_path to import,api,pg_temp;
--
--
create view sesite with (security_barrier) as
select sesite_id,sesite_url,selink_user_id from db.source natural join db.sesite natural left join (select * from db.selink where account_id=get_account_id()) s where community_id=get_community_id();
--
--
create function login_community(uuid,text) returns boolean language sql security definer as $$select api.login_community($1,(select community_id from db.community where community_name=$2));$$;
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
--
--
create function _markdownsummary(text) returns text language sql immutable security definer set search_path=db,api,pg_temp as $$
  with recursive
     m as (select regexp_replace(r[1],'([!$()*+.:<=>?[\\\]^{|}-])', '\\\1', 'g') str_from, trim(trailing chr(13) from r[2]) str_to, (row_number() over ())::integer rn 
           from regexp_matches($1,'^(\[[^\]]+]): ?(.*)$','ng') r)
   , w(markdown) as (select split_part(trim(leading chr(13) from $1),chr(13),1), 1 rn
                     union all
                     select regexp_replace(
                              regexp_replace(markdown,'(?<=\[[^\]]+])'||str_from,'('||str_to||')')
                             ,'(?<=(?<!\])'||str_from||')(?!\()'
                             ,'('||str_to||')')
                            ,rn+1  from w join m using(rn))
  select trim(both ' #' from markdown) from w order by rn desc limit 1;
$$;
--
create function _ensure_seuser(sesid integer, seuid integer, seuname text) returns integer language plpgsql security definer set search_path=db,api,pg_temp as $$
declare
  id integer;
begin
  if exists(select 1 from communicant natural join selink where community_id=get_community_id() and sesite_id=sesid and selink_user_id=seuid) then
    select account_id from communicant natural join selink where community_id=get_community_id() and sesite_id=sesid and selink_user_id=seuid into id;
  else
    insert into account(account_name,account_license_id,account_codelicense_id,account_is_imported) values(trim(regexp_replace(seuname,'-|\.',' ','g')),4,1,true) returning account_id into id;
    --
    insert into communicant(account_id,community_id,communicant_regular_font_id,communicant_monospace_font_id)
    select id,community_id,community_regular_font_id,community_monospace_font_id from community where community_id=get_community_id();
    --
    insert into selink(account_id,community_id,sesite_id,selink_user_id) values(id,get_community_id(),sesid,seuid);
  end if;
  return id;
end;
$$;
--
create function _new_question(aid integer, title text, markdown text, tags text, sesid integer, seqid integer, seat timestamptz) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error(400,'already imported') where exists (select 1 from question where community_id=get_community_id() and question_se_question_id=seqid);
  select _ensure_communicant(aid,get_community_id());
  --
  with r as (insert into room(community_id,room_question_id) values(get_community_id(),-1) returning community_id,room_id)
     , q as (insert into question(community_id,account_id,kind_id,sanction_id,question_at,question_title,question_markdown,question_room_id,license_id,codelicense_id,question_se_question_id
                                 ,question_se_imported_at,question_sesite_id)
             select community_id,aid,kind_id,community_import_sanction_id,seat,title,markdown,room_id,4,1,seqid,current_timestamp,sesid
             from r natural join community natural join (select sanction_id community_import_sanction_id, kind_id from sanction) s
             returning question_id)
     , h as (insert into question_history(question_id,account_id,question_history_title,question_history_markdown)
             select question_id,get_account_id(),title,markdown from q)
     , t as (with recursive w(tag_id,next_id,path,cycle) as (select tag_id,tag_implies_id,array[tag_id],false from tag natural join (select * from regexp_split_to_table(tags,' ') tag_name) z where community_id=get_community_id()
                                                             union all
                                                             select tag.tag_id,tag.tag_implies_id,path||tag.tag_id,tag.tag_id=any(w.path) from w join tag on tag.tag_id=w.next_id where not cycle)
             select (select question_id from q),tag_id,community_id,aid from w natural join tag where tag_id not in (select tag_id from question_tag_x where question_id=(select question_id from q)))
     , i as (insert into question_tag_x(question_id,tag_id,community_id,account_id) select distinct question_id,tag_id,community_id,aid from t returning tag_id)
     , u as (update tag set tag_question_count = tag_question_count+1 where tag_id in (select tag_id from i))
  select null;
  --
  update room set room_question_id = (select question_id from question where question_room_id=room_id) where room_question_id=-1 returning room_question_id;
$$;
--
create function new_question(title text, markdown text, tags text, sesid integer, seqid integer, seuid integer, seuname text, seat timestamptz) returns integer language sql security definer set search_path=db,api,import,pg_temp as $$
  with u as (select _ensure_seuser(sesid,seuid,seuname) uid) select _new_question(uid,title,markdown,tags,sesid,seqid,seat) qid from u;
$$;
--
create function new_questionanon(title text, markdown text, tags text, sesid integer, seqid integer, seat timestamptz) returns integer language sql security definer set search_path=db,api,import,pg_temp as $$
  with u as (select account_id from communicant natural join selink where community_id=get_community_id() and sesite_id=sesid and selink_user_id=0) select _new_question(account_id,title,markdown,tags,sesid,seqid,seat) question_id from u;
$$;
--
create function _new_answer(aid integer, markdown text, seaid integer, seat timestamptz) returns integer language sql security definer set search_path=db,api,import,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error(400,'already imported') where exists (select 1 from answer where question_id=get_question_id() and answer_se_answer_id=seaid);
  select _ensure_communicant(aid,get_community_id());
  --
  update question set question_poll_major_id = default where question_id=get_question_id();
  --
  with i as (insert into answer(question_id,community_id,kind_id,sanction_id,account_id,answer_at,answer_markdown,answer_summary,license_id,codelicense_id,answer_se_answer_id,answer_se_imported_at)
             select question_id,community_id,kind_id,sanction_id,aid,seat,markdown,_markdownsummary(markdown),4,1,seaid,current_timestamp from question where question_id=get_question_id()
             returning answer_id)
  insert into answer_history(answer_id,account_id,answer_history_markdown) select answer_id,get_account_id(),markdown from i returning answer_id;
$$;
--
create function new_answer(markdown text, sesid integer, seaid integer, seuid integer, seuname text, seat timestamptz) returns integer language sql security definer set search_path=db,api,import,pg_temp as $$
  select _new_answer(_ensure_seuser(sesid,seuid,seuname),markdown,seaid,seat);
$$;
--
create function new_answeranon(markdown text, sesid integer, seaid integer, seat timestamptz) returns integer language sql security definer set search_path=db,api,import,pg_temp as $$
  select _new_answer((select account_id from communicant natural join selink where community_id=get_community_id() and selink_user_id=0),markdown,seaid,seat);
$$;
--
create function new_import(sid integer, qid text) returns void language sql security definer set search_path=db,api,pg_temp as $$
  insert into import(account_id,community_id,sesite_id,import_qid) values(get_account_id(),get_community_id(),sid,coalesce(qid,''));
$$;
--
create function get_question(id integer) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select question_id from question where community_id=get_community_id() and question_se_question_id=id;
$$;
--
create function get_answer(id integer) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select answer_id from answer where question_id=get_question_id() and answer_se_answer_id=id;
$$;
--
--
revoke all on all functions in schema import from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='import' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='import' and proname!~'^_' );
end$$;
