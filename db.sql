create table community(
  community_id integer generated always as identity primary key
, community_name text not null
, community_room_id integer not null
, community_dark_shade bytea not null default decode('4d7ebb','hex') check(length(community_dark_shade)=3)
, community_mid_shade bytea not null default decode('d4dfec','hex') check(length(community_mid_shade)=3)
, community_light_shade bytea not null default decode('e7edf4','hex') check(length(community_light_shade)=3)
, community_highlight_color bytea not null default decode('f79804','hex') check(length(community_highlight_color)=3)
, foreign key (community_id,community_room_id) references room deferrable initially deferred
);

create type room_type_enum as enum ('public','gallery','private');

create table room(
  community_id integer references community on delete cascade deferrable initially deferred
, room_id integer generated always as identity
, room_type room_type_enum not null default 'public'
, room_name text
, primary key (community_id,room_id)
);

create table account(
  account_id integer generated always as identity primary key
, account_name text check (account_name~'^[A-Za-zÀ-ÖØ-öø-ÿ][ 0-9A-Za-zÀ-ÖØ-öø-ÿ]{1,25}[0-9A-Za-zÀ-ÖØ-öø-ÿ]$')
, account_create_at timestamptz not null default current_timestamp
, account_change_at timestamptz not null default current_timestamp
, account_image bytea
);

create table account_history(
  account_id integer references account
, account_history_id integer generated always as identity
, account_change_at timestamptz not null
, account_history_name text not null default ''
, primary key(account_id,account_history_id)
);

create table login(
  login_uuid uuid primary key
, account_id integer not null references account
);

create table pin(
  pin_number bigint
, account_id integer references account
, pin_at timestamptz default current_timestamp not null
, primary key (pin_number,account_id)
);

create table account_community_x(
  account_id integer references account
, community_id integer references community
, account_community_x_pinned_room_id integer
, foreign key (community_id,account_community_x_pinned_room_id) references room
);

create table room_account_x(
  community_id integer
, room_id integer
, account_id integer references account on delete cascade
, foreign key (community_id,room_id) references room on delete cascade
);

create table chat(
  community_id integer
, room_id integer
, chat_id bigint generated always as identity unique
, account_id integer not null references account
, chat_reply_id integer
, chat_change_id bigint generated always as identity unique
, chat_at timestamptz not null default current_timestamp
, chat_change_at timestamptz not null default current_timestamp
, chat_markdown text not null
, primary key (community_id,room_id,chat_id)
, foreign key (community_id,room_id,chat_reply_id) references chat
);

create table chat_notification(
  community_id integer
, room_id integer
, chat_id bigint
, account_id integer references account
, chat_notification_at timestamptz not null default current_timestamp
, primary key (community_id,room_id,chat_id,account_id)
, foreign key (community_id,room_id,chat_id) references chat
);

create table chat_history(
  community_id integer
, room_id integer
, chat_id bigint
, chat_history_id bigint unique
, account_id integer not null references account
, chat_reply_id integer
, chat_history_at timestamptz not null
, chat_history_markdown text not null
, primary key (community_id,room_id,chat_id,chat_history_id)
, foreign key (community_id,room_id,chat_id) references chat
);

create table chat_flag(
  community_id integer
, room_id integer
, chat_id bigint
, account_id integer references account
, chat_flag_at timestamptz not null default current_timestamp
, primary key (community_id,room_id,chat_id,account_id)
, foreign key (community_id,room_id,chat_id) references chat
);

create table chat_star(
  community_id integer
, room_id integer
, chat_id bigint
, account_id integer references account
, chat_star_at timestamptz not null default current_timestamp
, primary key (community_id,room_id,chat_id,account_id)
, foreign key (community_id,room_id,chat_id) references chat
);

create table chat_year(
  community_id integer
, room_id integer
, chat_year integer
, chat_year_count integer not null
, primary key (community_id,room_id,chat_year)
);

create table chat_month(
  community_id integer
, room_id integer
, chat_year integer
, chat_month integer
, chat_month_count integer not null
, primary key (community_id,room_id,chat_year,chat_month)
, foreign key (community_id,room_id,chat_year) references chat_year
);

create table chat_day(
  community_id integer
, room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_day_count integer not null
, primary key (community_id,room_id,chat_year,chat_month,chat_day)
, foreign key (community_id,room_id,chat_year,chat_month) references chat_month
);

create table chat_hour(
  community_id integer
, room_id integer
, chat_year integer
, chat_month integer
, chat_day integer
, chat_hour integer
, chat_hour_count integer not null
, primary key (community_id,room_id,chat_year,chat_month,chat_day,chat_hour)
, foreign key (community_id,room_id,chat_year,chat_month,chat_day) references chat_day
);

