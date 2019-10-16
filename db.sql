create table community(
  community_id serial primary key
, community_name text not null
, community_room_id integer not null
, foreign key (community_id,community_room_id) references room deferrable initially deferred
);

create type room_type_enum as enum ('public','gallery','private');

create table room(
  community_id integer references community on delete cascade deferrable initially deferred
, room_id serial unique
, room_type room_type_enum not null default 'public'
, room_name text
, primary key (community_id,room_id)
);

create table account(
  account_id serial primary key
, account_name text not null default ''
);

create table login(
  login_uuid uuid primary key
, account_id integer not null references account
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
, account_id integer references account
, chat_id serial unique
, chat_at timestamptz not null default current_timestamp
, chat_markdown text not null
, primary key (community_id,room_id,account_id,chat_id)
);
