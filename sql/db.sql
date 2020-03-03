drop schema public;

create schema db;

create schema x_uuid_ossp;
create extension "uuid-ossp" schema x_uuid_ossp;

create schema x_pg_trgm;
create extension "pg_trgm" schema x_pg_trgm;

create schema x_btree_gin;
create extension "btree_gin" schema x_btree_gin;

create schema x_pgcrypto;
create extension "pgcrypto" schema x_pgcrypto;

create schema x_pg_stat_statements;
create extension "pg_stat_statements" schema x_pg_stat_statements;

alter database postgres set search_path to '$user',db,x_pgcrypto,x_pg_trgm,x_btree_gin;
set search_path to '$user',db,x_pgcrypto,x_pg_trgm,x_btree_gin;

create table one(
  one_encryption_key bytea default x_pgcrypto.gen_random_bytes(32) not null
, one_stackapps_secret text default '' not null
);
create unique index one_only_ind on one((1));

create table sesite(
  sesite_id integer generated always as identity primary key
, sesite_url text not null unique
);

create table font(
  font_id integer generated always as identity primary key
, font_name text not null
, font_is_monospace boolean not null
);

create type community_type_enum as enum ('public','private');

create table community(
  community_id integer generated always as identity primary key
, community_name text not null
, community_room_id integer not null
, community_dark_shade bytea not null default decode('4d7ebb','hex') check(length(community_dark_shade)=3)
, community_mid_shade bytea not null default decode('d4dfec','hex') check(length(community_mid_shade)=3)
, community_light_shade bytea not null default decode('e7edf4','hex') check(length(community_light_shade)=3)
, community_highlight_color bytea not null default decode('f79804','hex') check(length(community_highlight_color)=3)
, community_code_language text 
, community_regular_font_id integer default 3 not null references font
, community_monospace_font_id integer default 2 not null references font
, community_display_name text not null
, community_type community_type_enum not null default 'private'
, community_warning_color bytea not null default decode('990000','hex') check(length(community_warning_color)=3)
, community_regular_font_is_locked boolean default false not null
, community_monospace_font_is_locked boolean default false not null
, community_tables_are_monospace boolean default false not null
, community_is_coming_soon boolean default false not null
, community_ordinal integer
, community_about_question_id integer
, community_image bytea check(length(community_image)>0)
, community_ask_button_text text default 'Ask' not null
, community_banner_markdown text default '' not null
);

create table source(
  community_id integer references community
, sesite_id integer references sesite
, source_is_default boolean default true not null
, primary key (community_id,sesite_id)
);
create unique index source_default_ind on source(community_id) where source_is_default;

create type room_type_enum as enum ('public','gallery','private');

create table room(
  room_id integer generated always as identity primary key
, community_id integer not null references community deferrable initially deferred
, room_type room_type_enum not null default 'public'
, room_name text
, room_image bytea check(length(room_image)>0)
, unique (community_id,room_id)
);

alter table community add foreign key(community_room_id) references room deferrable initially deferred;

create table license(
  license_id integer generated always as identity primary key
, license_name text unique not null
, license_href text
, license_is_versioned boolean default false not null
, license_description text unique not null
);

create table codelicense(
  codelicense_id integer generated always as identity primary key
, codelicense_name text unique not null
, codelicense_is_versioned boolean default false not null
, codelicense_description text unique not null
);

create table account(
  account_id integer generated always as identity primary key
, account_name text check (account_name~'^[-'' ._0-9[:alpha:]]{1,40}$')
, account_create_at timestamptz not null default current_timestamp
, account_change_at timestamptz not null default current_timestamp
, account_image bytea check(length(account_image)>0)
, account_change_id bigint generated always as identity unique
, account_uuid uuid not null default x_uuid_ossp.uuid_generate_v4()
, account_is_dev boolean default false not null
, account_license_id integer references license default 4 not null
, account_codelicense_id integer references codelicense default 1 not null
, account_notification_id integer generated always as identity unique
, account_is_imported boolean default false not null
, account_encryption_key bytea default x_pgcrypto.gen_random_bytes(32) not null
, account_permit_later_license boolean default false not null
, account_permit_later_codelicense boolean default false not null
);
create unique index account_rate_limit_ind on account(account_create_at);

create table member(
  account_id integer references account
, community_id integer references community
, primary key (account_id,community_id)
);

create table communicant(
  account_id integer references account
, community_id integer references community
, communicant_votes integer default 0 not null
, communicant_can_import boolean default false not null
, communicant_regular_font_id integer not null references font
, communicant_monospace_font_id integer not null references font
, communicant_is_post_flag_crew boolean default false not null
, primary key (account_id,community_id)
);

create table selink(
  account_id integer
, community_id integer
, sesite_id integer references sesite
, selink_user_id integer not null
, primary key (account_id,community_id,sesite_id)
, foreign key (account_id,community_id) references communicant
, foreign key (community_id,sesite_id) references source
);
create unique index selink_user_id_ind on selink(community_id,sesite_id,selink_user_id);

create table account_history(
  account_history_id integer generated always as identity primary key
, account_id integer not null references account
, account_history_at timestamptz not null
, account_history_name text not null default ''
);

create table login(
  login_uuid uuid primary key
, account_id integer not null references account
, login_resizer_percent integer default 70 not null check (login_resizer_percent between 0 and 100)
, login_chat_resizer_percent integer default 30 not null check (login_chat_resizer_percent between 0 and 100)
);

create table pin(
  pin_number bigint
, account_id integer references account
, pin_at timestamptz default current_timestamp not null
, primary key (pin_number,account_id)
);

create table chat(
  chat_id bigint generated always as identity primary key
, community_id integer not null references community
, room_id integer not null references room
, account_id integer not null references account
, chat_reply_id integer references chat
, chat_change_id bigint generated always as identity unique
, chat_at timestamptz not null default current_timestamp
, chat_change_at timestamptz not null default current_timestamp
, chat_markdown text not null check (length(chat_markdown) between 1 and 5500)
, unique (room_id,chat_id)
, foreign key (community_id,room_id) references room(community_id,room_id)
, foreign key (room_id,chat_reply_id) references chat(room_id,chat_id)
);
create index chat_latest_ind on chat(room_id,chat_at);
create index chat_search_ind on chat using gin (room_id, chat_markdown gin_trgm_ops);
create index chat_room_id_chat_id_fk_ind on chat(room_id,chat_id);

create table ping(
  chat_id bigint references chat
, account_id integer not null references account
, primary key (chat_id,account_id)
);

create table chat_history(
  chat_history_id bigint generated always as identity primary key
, chat_id bigint not null references chat
, chat_history_at timestamptz default current_timestamp not null
, chat_history_markdown text not null
);
create index chat_history_chat_id on chat_history (chat_id);

create table writer(
  account_id integer references account
, room_id integer references room
, primary key (account_id,room_id)
);

create table participant(
  room_id integer references room
, account_id integer references account
, participant_latest_chat_at timestamptz not null default current_timestamp
, participant_latest_read_chat_id bigint not null
, participant_chat_count integer default 0 not null
, primary key (room_id,account_id)
);
create index participant_latest on participant(room_id,participant_latest_chat_at);

create table chat_flag(
  chat_id bigint references chat
, account_id integer references account
, chat_flag_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
);

create table chat_star(
  chat_id bigint
, account_id integer references account
, room_id integer not null
, chat_star_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
, foreign key (room_id,chat_id) references chat(room_id,chat_id)
);
create index chat_star_latest on chat_star(room_id,chat_star_at);

create table chat_year(
  room_id integer references room
, chat_year integer
, chat_year_count integer not null
, primary key (room_id,chat_year)
);

create table chat_month(
  room_id integer
, chat_year integer
, chat_month integer
, chat_month_count integer not null
, primary key (room_id,chat_year,chat_month)
, foreign key (room_id,chat_year) references chat_year
);

create table chat_day(
  room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_day_count integer not null
, primary key (room_id,chat_year,chat_month,chat_day)
, foreign key (room_id,chat_year,chat_month) references chat_month
);

create table chat_hour(
  room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_hour integer
, chat_hour_count integer not null
, primary key (room_id,chat_year,chat_month,chat_day,chat_hour)
, foreign key (room_id,chat_year,chat_month,chat_day) references chat_day
);

create table kind(
  kind_id integer generated always as identity primary key
, kind_description text not null
, kind_short_description text default '' not null
, kind_can_all_edit boolean default true not null
, kind_has_answers boolean default true not null
, kind_has_question_votes boolean default false not null
, kind_has_answer_votes boolean default true not null
, kind_minimum_votes_to_answer integer default 0 not null
, kind_allows_question_multivotes boolean default true not null
, kind_allows_answer_multivotes boolean default true not null
, kind_show_answer_summary_toc boolean default false not null
, kind_account_id integer
);

create table sanction(
  kind_id integer references kind not null
, community_id integer not null references community
, sanction_ordinal integer not null
, sanction_is_default boolean default false
, primary key (kind_id,community_id)
);
create unique index sanction_ind on sanction(community_id,sanction_ordinal);
create unique index sanction_default_ind on sanction(community_id) where sanction_is_default;

create table question(
  question_id integer generated always as identity primary key
, community_id integer not null references community
, account_id integer not null references account
, question_at timestamptz not null default current_timestamp
, question_title text not null check (length(question_title) between 5 and 200)
, question_markdown text not null check (length(question_markdown) between 1 and 50000)
, question_room_id integer not null references room deferrable initially deferred
, question_change_at timestamptz not null default current_timestamp
, question_votes integer default 0 not null
, license_id integer references license not null
, codelicense_id integer references codelicense not null
, question_poll_id bigint generated always as identity unique
, question_poll_major_id bigint generated always as identity unique
, question_poll_minor_id bigint generated always as identity unique
, question_se_question_id integer
, question_flags integer default 0 not null
, question_crew_flags integer default 0 not null
, question_active_flags integer default 0 not null
, question_se_imported_at timestamptz
, question_permit_later_license boolean default false not null
, question_permit_later_codelicense boolean default false not null
, kind_id integer references kind not null
, question_sesite_id integer references sesite
, unique (community_id,question_id)
, unique (community_id,question_se_question_id)
, foreign key (community_id,question_room_id) references room(community_id,room_id)
);
create unique index question_rate_limit_ind on question(account_id,question_at);
create unique index question_se_question_id_ind on question(community_id,question_sesite_id,question_se_question_id);
create unique index question_poll_major_id_ind on question(community_id,question_poll_major_id);
create index question_search_title_ind on question using gin (community_id, question_title gin_trgm_ops);
create index question_search_markdown_ind on question using gin (community_id, question_markdown gin_trgm_ops);
create index question_room_id_fk_ind on question(question_room_id);

alter table community add foreign key (community_about_question_id) references question;
alter table community add foreign key (community_id,community_about_question_id) references question(community_id,question_id);

create table question_history(
  question_history_id bigint generated always as identity primary key
, question_id integer not null references question
, account_id integer not null references account
, question_history_at timestamptz default current_timestamp not null
, question_history_title text not null
, question_history_markdown text not null
);
create unique index question_history_rate_limit_ind on question_history(account_id,question_history_at);

create table answer(
  answer_id integer generated always as identity primary key
, question_id integer not null references question
, account_id integer not null references account
, answer_at timestamptz not null default current_timestamp
, answer_markdown text not null check (length(answer_markdown) between 1 and 50000)
, answer_change_at timestamptz not null default current_timestamp
, answer_votes integer default 0 not null
, answer_se_answer_id integer
, answer_flags integer default 0 not null
, answer_crew_flags integer default 0 not null
, answer_active_flags integer default 0 not null
, license_id integer references license not null
, codelicense_id integer references codelicense not null
, answer_se_imported_at timestamptz
, answer_proposed_answer_id integer
, answer_summary text not null
, answer_permit_later_license boolean default false not null
, answer_permit_later_codelicense boolean default false not null
);
create unique index answer_rate_limit_ind on answer(account_id,answer_at);
create index answer_question_id_ind on answer(question_id);

create table answer_history(
  answer_history_id bigint generated always as identity primary key
, answer_id integer not null references answer
, account_id integer not null references account
, answer_history_at timestamptz default current_timestamp not null
, answer_history_markdown text not null
);
create unique index answer_history_rate_limit_ind on answer_history(account_id,answer_history_at);

create table tag(
  tag_id integer generated always as identity primary key
, community_id integer not null references community
, tag_at timestamptz not null default current_timestamp
, tag_name text not null check (tag_name~'^[a-z][-.0-9a-z]{1,18}[0-9a-z]$')
, tag_description text default '' not null check (length(tag_description)<101)
, tag_implies_id integer
, tag_question_count integer default 0 not null
, unique (community_id,tag_id)
, unique (community_id,tag_name)
, foreign key (community_id,tag_implies_id) references tag (community_id,tag_id)
);
create index tag_implies_id_fk_ind on tag(tag_implies_id);

create table question_tag_x(
  question_id integer
, tag_id integer
, community_id integer not null
, account_id integer not null references account
, question_tag_x_at timestamptz not null default current_timestamp
, primary key (question_id,tag_id)
, foreign key (community_id,question_id) references question (community_id,question_id)
, foreign key (community_id,tag_id) references tag (community_id,tag_id)
);
create index question_tag_x_tag_id_fk_ind on question_tag_x(tag_id,question_id);

create table question_tag_x_history(
  question_tag_x_history_id integer generated always as identity primary key
, question_id integer
, tag_id integer
, community_id integer not null
, question_tag_x_history_added_by_account_id integer not null references account
, question_tag_x_history_removed_by_account_id integer not null references account
, question_tag_x_added_at timestamptz not null
, question_tag_x_removed_at timestamptz not null default current_timestamp
, foreign key (community_id,question_id) references question (community_id,question_id)
, foreign key (community_id,tag_id) references tag (community_id,tag_id)
);
create index question_tag_x_history_question_id_fk_ind on question_tag_x_history(question_id,tag_id);
create index question_tag_x_history_tag_id_fk_ind on question_tag_x_history(tag_id,question_id);

create table question_vote(
  question_id integer references question
, account_id integer references account
, question_vote_at timestamptz default current_timestamp not null
, question_vote_votes integer not null check (question_vote_votes>=0)
, primary key (question_id,account_id)
, unique (account_id,question_id)
);

create table question_vote_history(
  question_vote_history_id integer generated always as identity primary key
, question_id integer not null
, account_id integer not null
, question_vote_history_at timestamptz not null
, question_vote_history_votes integer not null check (question_vote_history_votes>=0)
, foreign key(question_id,account_id) references question_vote deferrable initially deferred
);

create table question_flag(
  question_id integer references question
, account_id integer references account
, question_flag_at timestamptz default current_timestamp not null
, question_flag_direction integer not null check (question_flag_direction in (-1,0,1))
, question_flag_is_crew boolean default false not null
, primary key (question_id,account_id)
, unique (account_id,question_id)
);

create table question_flag_history(
  question_flag_history_id integer generated always as identity primary key
, question_id integer not null
, account_id integer not null
, question_flag_history_at timestamptz default current_timestamp not null
, question_flag_history_direction integer not null check (question_flag_history_direction in (-1,0,1))
, question_flag_history_is_crew boolean default false not null
, foreign key(question_id,account_id) references question_flag deferrable initially deferred
);

create table question_flag_notification(
  question_flag_history_id integer references question_flag_history
, account_id integer references account
, question_flag_notification_at timestamptz not null default current_timestamp
, primary key (question_flag_history_id,account_id)
);
create index question_flag_notification_latest_ind on question_flag_notification(account_id,question_flag_notification_at);

create table answer_vote(
  answer_id integer references answer
, account_id integer references account
, answer_vote_at timestamptz default current_timestamp not null
, answer_vote_direction integer not null check (answer_vote_direction in(-1,0,1))
, answer_vote_repute integer not null check (answer_vote_repute>=0)
, answer_vote_votes integer not null check (answer_vote_votes>=0)
, primary key (answer_id,account_id)
, unique (account_id,answer_id)
);

create table answer_vote_history(
  answer_vote_history_id integer generated always as identity primary key
, answer_id integer not null
, account_id integer not null
, answer_vote_history_at timestamptz not null
, answer_vote_history_direction integer not null check (answer_vote_history_direction in(-1,0,1))
, answer_vote_history_repute integer not null check (answer_vote_history_repute>=0)
, answer_vote_history_votes integer not null check (answer_vote_history_votes>=0)
, foreign key(answer_id,account_id) references answer_vote deferrable initially deferred
);

create table answer_flag(
  answer_id integer references answer
, account_id integer references account
, answer_flag_at timestamptz default current_timestamp not null
, answer_flag_direction integer not null check (answer_flag_direction in (-1,0,1))
, answer_flag_is_crew boolean default false not null
, primary key (answer_id,account_id)
, unique (account_id,answer_id)
);

create table answer_flag_history(
  answer_flag_history_id integer generated always as identity primary key
, answer_id integer not null
, account_id integer not null
, answer_flag_history_at timestamptz default current_timestamp not null
, answer_flag_history_direction integer not null check (answer_flag_history_direction in (-1,0,1))
, answer_flag_history_is_crew boolean default false not null
, foreign key(answer_id,account_id) references answer_flag deferrable initially deferred
);

create table answer_flag_notification(
  answer_flag_history_id integer references answer_flag_history
, account_id integer references account
, answer_flag_notification_at timestamptz not null default current_timestamp
, primary key (answer_flag_history_id,account_id)
);
create index answer_flag_notification_latest_ind on answer_flag_notification(account_id,answer_flag_notification_at);

create table chat_notification(
  chat_id bigint references chat
, account_id integer references account
, chat_notification_at timestamptz not null default current_timestamp
, primary key (chat_id,account_id)
);
create index chat_notification_latest_ind on chat_notification(account_id,chat_notification_at);

create table question_notification(
  question_history_id integer references question_history
, account_id integer references account
, question_notification_at timestamptz not null default current_timestamp
, primary key (question_history_id,account_id)
);
create index question_notification_latest_ind on question_notification(account_id,question_notification_at);

create table answer_notification(
  answer_history_id integer references answer_history
, account_id integer references account
, answer_notification_at timestamptz not null default current_timestamp
, primary key (answer_history_id,account_id)
);
create index answer_notification_latest_ind on answer_notification(account_id,answer_notification_at);

create table system_notification(
  system_notification_id integer generated always as identity primary key
, account_id integer references account
, system_notification_at timestamptz not null default current_timestamp
, system_notification_dismissed_at timestamptz
, system_notification_message text not null
, system_notification_community_id integer references community
, unique (account_id,system_notification_id)
);
create index system_notification_latest_ind on system_notification(account_id,system_notification_at) where system_notification_dismissed_at is null;

create table subscription(
  account_id integer references account
, question_id integer references question
, primary key (account_id,question_id)
);

create table import(
  import_id integer generated always as identity primary key
, import_at timestamptz not null default current_timestamp
, account_id integer references account
, community_id integer references community
, sesite_id integer references sesite
, import_qid text not null
, import_aids text default '' not null
);

create table environment(
  environment_name text primary key
);
