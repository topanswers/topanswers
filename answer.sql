create schema answer;
grant usage on schema answer to get,post;
set local search_path to answer,api,pg_temp;
--
--
create view license with (security_barrier) as select license_id,license_name,license_href,license_is_versioned from db.license;
create view codelicense with (security_barrier) as select codelicense_id,codelicense_name,codelicense_is_versioned from db.codelicense;
--
create view one with (security_barrier) as
select *
     , (select font_name from db.font where font_id=coalesce(communicant_regular_font_id,community_regular_font_id)) my_community_regular_font_name
     , (select font_name from db.font where font_id=coalesce(communicant_monospace_font_id,community_monospace_font_id)) my_community_monospace_font_name
from (select account_id,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense from db.account where account_id=get_account_id()) ac
     cross join (select community_id,question_id,question_title,question_markdown,kind_allows_answer_multivotes from db.question natural join db.kind where question_id=get_question_id()) q
     natural join (select community_id,community_name,community_code_language
                         ,community_regular_font_id,community_monospace_font_id,community_my_power,community_tables_are_monospace
                         ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   from db.community natural join api._community) c
     natural left join (select account_id,community_id,communicant_regular_font_id,communicant_monospace_font_id,communicant_votes from db.communicant) co
     natural left join (select question_id,answer_id,answer_markdown
                             , license_name||(case when answer_permit_later_license then ' or later' else '' end)||(case when codelicense_id<>1 then ' + '||codelicense_name||(case when answer_permit_later_codelicense then ' or later' else '' end) else '' end) answer_license
                             , account_id answer_account_id
                        from db.answer natural join db.license natural join db.codelicense
                        where answer_id=get_answer_id()) a;
--
--
create function login_question(uuid,integer) returns boolean language sql security definer as $$select api.login_question($1,$2);$$;
create function login_answer(uuid,integer) returns boolean language sql security definer as $$select api.login_answer($1,$2);$$;
--
--
revoke all on all functions in schema answer from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to get;', E'\n') from pg_views where schemaname='answer' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to get;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='answer' and proname!~'^_' );
end$$;
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
create function vote(votes integer) returns integer language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid answer') where get_answer_id() is null;
  select _error('invalid number of votes cast') from answer.one where votes<0 or votes>(case when kind_allows_answer_multivotes then community_my_power else 1 end);
  select _error('cant vote on own answer') from answer.one where account_id=answer_account_id;
  select _error(429,'rate limit') where (select count(*) from answer_vote where account_id=get_account_id() and answer_vote_at>current_timestamp-'1m'::interval)>4;
  select _error(429,'rate limit') where (select count(*) from answer_vote_history where account_id=get_account_id() and answer_vote_history_at>current_timestamp-'1m'::interval)>10;
  --
  select _ensure_communicant(get_account_id(),get_community_id());
  update question set question_poll_minor_id = default where question_id=(select question_id from answer where answer_id=get_answer_id());
  --
  with d as (delete from answer_vote where answer_id=get_answer_id() and account_id=get_account_id() returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from d join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , q as (update answer set answer_votes = answer_votes-answer_vote_votes from d where answer.answer_id=get_answer_id())
     , c as (update communicant set communicant_votes = communicant_votes-answer_vote_votes from r where communicant.account_id=r.account_id and communicant.community_id=r.community_id)
  insert into answer_vote_history(answer_id,account_id,answer_vote_history_at,answer_vote_history_votes)
  select answer_id,account_id,answer_vote_at,answer_vote_votes from d;
  --
  with i as (insert into answer_vote(answer_id,account_id,answer_vote_votes) values(get_answer_id(),get_account_id(),votes) returning *)
     , r as (select answer_id,community_id,a.account_id,answer_vote_votes from i join answer a using(answer_id) natural join (select question_id,community_id from question) q )
     , c as (update communicant set communicant_votes = communicant_votes+answer_vote_votes from r where communicant.account_id=r.account_id and communicant.community_id=r.community_id returning r.account_id,r.community_id,communicant_votes)
     , n as (insert into system_notification(account_id,system_notification_message,system_notification_community_id)
             select account_id,'Congratulations! You have reached the '||pow(10,community_my_power-1)||' star threshold, and can now award '||community_my_power||' stars on '||community_display_name||'.',community_id
             from c natural join api._community natural join community
             where trunc(log(greatest(communicant_votes,1)))>trunc(log(greatest(communicant_votes-votes,1))))
  update answer set answer_votes = answer_votes+answer_vote_votes from i where answer.answer_id=get_answer_id() returning answer_votes;
$$;
--
create function flag(direction integer) returns void language sql security definer set search_path=db,api,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid answer') where get_answer_id() is null;
  select _error('invalid flag direction') where direction not in(-1,0,1);
  select _error('cant flag own answer') from answer.one where account_id=answer_account_id;
  select _error(429,'rate limit') where (select count(1) from answer_flag_history where account_id=current_setting('custom.account_id',true)::integer and answer_flag_history_at>current_timestamp-'1m'::interval)>6;
  --
  select _ensure_communicant(get_account_id(),get_community_id());
  --
  with d as (delete from answer_flag where answer_id=get_answer_id() and account_id=get_account_id() returning *)
     , q as (update answer set answer_active_flags = answer_active_flags-abs(d.answer_flag_direction)
                             , answer_flags = answer_flags-(case when d.answer_flag_is_crew then 0 else d.answer_flag_direction end)
                             , answer_crew_flags = answer_crew_flags-(case when d.answer_flag_is_crew then d.answer_flag_direction else 0 end)
             from d
             where answer.answer_id=get_answer_id())
  select answer_id,account_id,answer_flag_at,answer_flag_direction,answer_flag_is_crew from d;
  --
  with i as (insert into answer_flag(answer_id,account_id,answer_flag_direction,answer_flag_is_crew)
             select get_answer_id(),account_id,direction,communicant_is_post_flag_crew from db.communicant where account_id=get_account_id() and community_id=get_community_id()
             returning *)
     , u as (update answer set answer_active_flags = answer_active_flags+abs(i.answer_flag_direction)
                               , answer_flags = answer_flags+(case when i.answer_flag_is_crew then 0 else i.answer_flag_direction end)
                               , answer_crew_flags = answer_crew_flags+(case when i.answer_flag_is_crew then i.answer_flag_direction else 0 end)
             from i
             where answer.answer_id=get_answer_id())
     , h as (insert into answer_flag_history(answer_id,account_id,answer_flag_history_direction,answer_flag_history_is_crew)
             select answer_id,account_id,answer_flag_direction,answer_flag_is_crew from i
             returning answer_flag_history_id,answer_flag_history_direction)
   , qfn as (insert into answer_flag_notification(answer_flag_history_id,account_id)
             select answer_flag_history_id,account_id
             from h cross join (select account_id from communicant where community_id=get_community_id() and communicant_is_post_flag_crew and account_id<>get_account_id()) c
             where answer_flag_history_direction>0
             returning account_id)
  update account set account_notification_id = default where account_id in (select account_id from qfn);
$$;
--
create function change(markdown text) returns void language sql security definer set search_path=db,api,answer,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid answer') where get_answer_id() is null;
  select _error(429,'rate limit') where (select count(*)
                                         from answer_history natural join (select answer_id from answer where account_id<>get_account_id()) z
                                         where account_id=get_account_id() and answer_history_at>current_timestamp-'5m'::interval)>10;
  --
  update question set question_poll_major_id = default where question_id=get_question_id();
  --
  with h as (insert into answer_history(answer_id,account_id,answer_history_markdown) values(get_answer_id(),get_account_id(),markdown) returning answer_id,answer_history_id)
     , n as (insert into answer_notification(answer_history_id,account_id)
             select answer_history_id,account_id from h natural join (select answer_id,question_id,account_id from answer) z where account_id<>get_account_id()
             union
             select answer_history_id,account_id from h natural join (select answer_id,question_id from answer) z natural join subscription where account_id<>get_account_id()
             returning account_id)
  update account set account_notification_id = default where account_id in (select account_id from n);
  --
  update answer set answer_markdown = markdown, answer_summary = _markdownsummary(markdown), answer_change_at = default where answer_id=get_answer_id();
$$;
--
create function new(markdown text, lic integer, lic_orlater boolean, codelic integer, codelic_orlater boolean) returns integer language sql security definer set search_path=db,api,answer,pg_temp as $$
  select _error('access denied') where get_account_id() is null;
  select _error('invalid question') where get_question_id() is null;
  select _error('question needs more votes before answers are permitted') from question natural join kind where question_id=get_question_id() and question_votes<kind_minimum_votes_to_answer;
  select _error('"or later" not allowed for '||license_name) from license where license_id=lic and lic_orlater and not license_is_versioned;
  select _error('"or later" not allowed for '||codelicense_name) from codelicense where codelicense_id=codelic and codelic_orlater and not codelicense_is_versioned;
  select _error(429,'rate limit') where (select count(*) from answer where account_id=get_account_id() and answer_at>current_timestamp-'2m'::interval)>3;
  --
  select _ensure_communicant(get_account_id(),get_community_id());
  update question set question_poll_major_id = default where question_id=get_question_id();
  --
  with i as (insert into answer(question_id,account_id,answer_markdown,license_id,codelicense_id,answer_summary,answer_permit_later_license,answer_permit_later_codelicense)
             values(get_question_id(),get_account_id(),markdown,lic,codelic,_markdownsummary(markdown),lic_orlater,codelic_orlater)
             returning answer_id)
     , h as (insert into answer_history(answer_id,account_id,answer_history_markdown) select answer_id,get_account_id(),markdown from i returning answer_id,answer_history_id)
     , n as (insert into answer_notification(answer_history_id,account_id)
             select answer_history_id,account_id from h cross join (select account_id from subscription where question_id=get_question_id() and account_id<>get_account_id()) z
             returning account_id)
     , a as (update account set account_notification_id = default where account_id in (select account_id from n))
  select answer_id from i;
$$;
--
--
revoke all on all functions in schema community from public;
do $$
begin
  execute (select string_agg('grant select on '||viewname||' to post;', E'\n') from pg_views where schemaname='answer' and viewname!~'^_');
  execute ( select string_agg('grant execute on function '||p.oid::regproc||'('||pg_get_function_identity_arguments(p.oid)||') to post;', E'\n')
            from pg_proc p join pg_namespace n on p.pronamespace=n.oid
            where n.nspname='answer' and proname!~'^_' );
end$$;
