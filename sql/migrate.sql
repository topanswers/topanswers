create extension postgres_fdw;
create server rds foreign data wrapper postgres_fdw options (host 'cluster1.cluster-c8l1itv3i2dg.eu-west-2.rds.amazonaws.com', dbname 'ta', sslmode 'require');
create user mapping for postgres server rds options (user 'postgres', password '');
create schema rds;
import foreign schema db from server rds into rds;

--ON RDS
alter table notification disable trigger trigger_setemail_notifications;
alter table notification disable trigger trigger_trim_notifications;
alter table community drop constraint community_community_import_sanction_id_fkey;
alter table community drop constraint community_community_about_question_id_fkey;
alter table community drop constraint community_community_id_community_about_question_id_fkey;
alter table community drop constraint community_community_room_id_fkey;
alter table question drop constraint question_question_room_id_fkey;
alter table question drop constraint question_community_id_question_room_id_fkey;
--END ON RDS

insert into rds.one select * from db.one;
insert into rds.license select * from db.license;
insert into rds.codelicense select * from db.codelicense;
insert into rds.account select * from db.account;
insert into rds.font select * from db.font;
insert into rds.community select * from db.community;
insert into rds.communicant select * from db.communicant;
insert into rds.environment select * from db.environment;
--insert into rds.error select * from db.error;
insert into rds.sesite select * from db.sesite;
insert into rds.import(import_id,import_at,account_id,community_id,sesite_id,import_qid,import_aids) select import_id,import_at,account_id,community_id,sesite_id,import_qid,import_aids from db.import;
insert into rds.kind select * from db.kind;
insert into rds.label select * from db.label;
insert into rds.login select * from db.login;
insert into rds.member select * from db.member;
--insert into rds.pin select * from db.pin;
--
insert into rds.sanction(sanction_id,kind_id,sanction_description,sanction_short_description,community_id,sanction_ordinal,sanction_is_default,sanction_label_called,sanction_label_is_mandatory,sanction_default_label_id)
select sanction_id,kind_id,sanction_description,sanction_short_description,community_id,sanction_ordinal,sanction_is_default,sanction_label_called,sanction_label_is_mandatory,sanction_default_label_id from db.sanction;
--
insert into rds.question(community_id,account_id,question_id,question_at,question_title,question_markdown,question_room_id,question_change_at,question_votes,license_id,codelicense_id,question_poll_id,question_poll_major_id,question_poll_minor_id
           ,question_se_question_id,question_flags,question_crew_flags,question_active_flags,question_se_imported_at,question_permit_later_license,question_permit_later_codelicense,kind_id,question_sesite_id,question_tag_ids,sanction_id,question_published_at
           ,question_is_public_visible)
select community_id,account_id,question_id,question_at,question_title,question_markdown,question_room_id,question_change_at,question_votes,license_id,codelicense_id,question_poll_id,question_poll_major_id,question_poll_minor_id
           ,question_se_question_id,question_flags,question_crew_flags,question_active_flags,question_se_imported_at,question_permit_later_license,question_permit_later_codelicense,kind_id,question_sesite_id,question_tag_ids,sanction_id,question_published_at
           ,question_is_public_visible
from db.question;
--
insert into rds.room(community_id,room_id,room_type,room_name,room_can_listen,room_latest_chat_id,room_question_id,room_image_hash,room_chat_count,room_flagged_chat_count,room_deleted_chat_count)
select community_id,room_id,room_type,room_name,room_can_listen,room_latest_chat_id,room_question_id,room_image_hash,room_chat_count,room_flagged_chat_count,room_deleted_chat_count from db.room;
--
insert into rds.source select * from db.source;
insert into rds.selink select * from db.selink;
insert into rds.subscription select * from db.subscription;
insert into rds.syndicate select * from db.syndicate;
insert into rds.syndication select * from db.syndication;
insert into rds.tag select * from db.tag order by tag_implies_id nulls first;
insert into rds.writer select * from db.writer;
--
insert into rds.answer(answer_id,question_id,account_id,answer_at,answer_markdown,answer_change_at,answer_votes,license_id,codelicense_id,answer_se_answer_id,answer_flags,answer_crew_flags,answer_active_flags,answer_se_imported_at,answer_proposed_answer_id
                      ,answer_summary,answer_permit_later_license,answer_permit_later_codelicense,community_id,kind_id,sanction_id,label_id)
select answer_id,question_id,account_id,answer_at,answer_markdown,answer_change_at,answer_votes,license_id,codelicense_id,answer_se_answer_id,answer_flags,answer_crew_flags,answer_active_flags,answer_se_imported_at,answer_proposed_answer_id
      ,answer_summary,answer_permit_later_license,answer_permit_later_codelicense,community_id,kind_id,sanction_id,label_id
from db.answer;
--
insert into rds.chat(community_id,room_id,chat_id,account_id,chat_reply_id,chat_change_id,chat_at,chat_change_at,chat_markdown,chat_flags,chat_crew_flags,chat_active_flags)
select community_id,room_id,chat_id,account_id,chat_reply_id,chat_change_id,chat_at,chat_change_at,chat_markdown,chat_flags,chat_crew_flags,chat_active_flags from db.chat order by chat_id;
--
insert into rds.listener select * from db.listener;
insert into rds.participant select * from db.participant;
insert into rds.ping select * from db.ping;
insert into rds.mark select * from db.mark;
insert into rds.mark_history select * from db.mark_history;
insert into rds.pinner select * from db.pinner;
insert into rds.thread select * from db.thread;
insert into rds.question_history select * from db.question_history;
insert into rds.question_vote select * from db.question_vote;
insert into rds.question_vote_history select * from db.question_vote_history;
insert into rds.question_flag select * from db.question_flag;
insert into rds.question_flag_history select * from db.question_flag_history;
insert into rds.answer_history select * from db.answer_history;
insert into rds.answer_flag select * from db.answer_flag;
insert into rds.answer_flag_history select * from db.answer_flag_history;
insert into rds.answer_vote select * from db.answer_vote;
insert into rds.answer_vote_history select * from db.answer_vote_history;
insert into rds.chat_history select * from db.chat_history;
insert into rds.chat_flag select * from db.chat_flag;
insert into rds.chat_flag_history select * from db.chat_flag_history;
insert into rds.chat_star(chat_id,account_id,chat_star_at,room_id) select chat_id,account_id,chat_star_at,room_id from db.chat_star;
insert into rds.chat_year select * from db.chat_year;
insert into rds.chat_month select * from db.chat_month;
insert into rds.chat_day select * from db.chat_day;
insert into rds.chat_hour select * from db.chat_hour;
insert into rds.notification select * from db.notification;
insert into rds.answer_flag_notification(notification_id,answer_flag_history_id) select notification_id,answer_flag_history_id from db.answer_flag_notification;
insert into rds.answer_notification(answer_history_id,notification_id) select answer_history_id,notification_id from db.answer_notification;
insert into rds.system_notification(system_notification_message,system_notification_community_id,notification_id) select system_notification_message,system_notification_community_id,notification_id from db.system_notification;
insert into rds.question_notification(notification_id,question_history_id) select notification_id,question_history_id from db.question_notification;
insert into rds.question_flag_notification(notification_id,question_flag_history_id) select notification_id,question_flag_history_id from db.question_flag_notification;
insert into rds.chat_notification(notification_id,chat_id) select notification_id,chat_id from db.chat_notification;

--ON RDS
alter table community add constraint "community_community_import_sanction_id_fkey" FOREIGN KEY (community_import_sanction_id) REFERENCES sanction(sanction_id);
alter table community add constraint "community_community_about_question_id_fkey" FOREIGN KEY (community_about_question_id) REFERENCES question(question_id);
alter table community add constraint "community_community_id_community_about_question_id_fkey" FOREIGN KEY (community_id, community_about_question_id) REFERENCES question(community_id, question_id);
alter table community add constraint "community_community_room_id_fkey" FOREIGN KEY (community_room_id) REFERENCES room(room_id) DEFERRABLE INITIALLY DEFERRED;
alter table question add constraint "question_question_room_id_fkey" FOREIGN KEY (question_room_id) REFERENCES room(room_id) DEFERRABLE INITIALLY DEFERRED;
alter table question add constraint "question_community_id_question_room_id_fkey" FOREIGN KEY (community_id, question_room_id) REFERENCES room(community_id, room_id) DEFERRABLE INITIALLY DEFERRED;
alter table notification enable trigger trigger_setemail_notifications;
alter table notification enable trigger trigger_trim_notifications;
--
select setval(pg_get_serial_sequence('sesite', 'sesite_id'), (select max(sesite_id) from sesite));
select setval(pg_get_serial_sequence('font', 'font_id'), (select max(font_id) from font));
select setval(pg_get_serial_sequence('license', 'license_id'), (select max(license_id) from license));
select setval(pg_get_serial_sequence('codelicense', 'codelicense_id'), (select max(codelicense_id) from codelicense));
select setval(pg_get_serial_sequence('kind', 'kind_id'), (select max(kind_id) from kind));
select setval(pg_get_serial_sequence('label', 'label_id'), (select max(label_id) from label));
select setval(pg_get_serial_sequence('account', 'account_id'), (select max(account_id) from account));
select setval(pg_get_serial_sequence('account', 'account_change_id'), (select max(account_change_id) from account));
select setval(pg_get_serial_sequence('account', 'account_notification_id'), (select max(account_notification_id) from account));
select setval(pg_get_serial_sequence('community', 'community_id'), (select max(community_id) from community));
select setval(pg_get_serial_sequence('room', 'room_id'), (select max(room_id) from room));
select setval(pg_get_serial_sequence('notification', 'notification_id'), (select max(notification_id) from notification));
select setval(pg_get_serial_sequence('account_history', 'account_history_id'), (select max(account_history_id) from account_history));
select setval(pg_get_serial_sequence('chat', 'chat_id'), (select max(chat_id) from chat));
select setval(pg_get_serial_sequence('chat', 'chat_change_id'), (select max(chat_change_id) from chat));
select setval(pg_get_serial_sequence('chat_history', 'chat_history_id'), (select max(chat_history_id) from chat_history));
select setval(pg_get_serial_sequence('chat_flag_history', 'chat_flag_history_id'), (select max(chat_flag_history_id) from chat_flag_history));
select setval(pg_get_serial_sequence('sanction', 'sanction_id'), (select max(sanction_id) from sanction));
select setval(pg_get_serial_sequence('question', 'question_id'), (select max(question_id) from question));
select setval(pg_get_serial_sequence('question', 'question_poll_id'), (select max(question_poll_id) from question));
select setval(pg_get_serial_sequence('question', 'question_poll_major_id'), (select max(question_poll_major_id) from question));
select setval(pg_get_serial_sequence('question', 'question_poll_minor_id'), (select max(question_poll_minor_id) from question));
select setval(pg_get_serial_sequence('question_history', 'question_history_id'), (select max(question_history_id) from question_history));
select setval(pg_get_serial_sequence('answer', 'answer_id'), (select max(answer_id) from answer));
select setval(pg_get_serial_sequence('answer_history', 'answer_history_id'), (select max(answer_history_id) from answer_history));
select setval(pg_get_serial_sequence('tag', 'tag_id'), (select max(tag_id) from tag));
select setval(pg_get_serial_sequence('mark_history', 'mark_history_id'), (select max(mark_history_id) from mark_history));
select setval(pg_get_serial_sequence('question_vote_history', 'question_vote_history_id'), (select max(question_vote_history_id) from question_vote_history));
select setval(pg_get_serial_sequence('question_flag_history', 'question_flag_history_id'), (select max(question_flag_history_id) from question_flag_history));
select setval(pg_get_serial_sequence('answer_vote_history', 'answer_vote_history_id'), (select max(answer_vote_history_id) from answer_vote_history));
select setval(pg_get_serial_sequence('answer_flag_history', 'answer_flag_history_id'), (select max(answer_flag_history_id) from answer_flag_history));
select setval(pg_get_serial_sequence('import', 'import_id'), (select max(import_id) from import));
--
analyze verbose;
--END ON RDS

--select table_schema, string_agg(column_name,',' order by ordinal_position) from information_schema.columns where table_schema in('db','rds') and table_name='chat_notification' group by table_schema;
