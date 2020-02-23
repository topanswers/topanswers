--
-- PostgreSQL database dump
--

-- Dumped from database version 11.5 (Debian 11.5-1+deb10u1)
-- Dumped by pg_dump version 11.5 (Debian 11.5-1+deb10u1)
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

DROP TABLE IF EXISTS "db".tag;
DROP TABLE IF EXISTS "db".sesite;
DROP TABLE IF EXISTS "db".sanction;
DROP TABLE IF EXISTS "db".room;
DROP TABLE IF EXISTS "db".question_tag_x;
DROP TABLE IF EXISTS "db".question;
DROP TABLE IF EXISTS "db".license;
DROP TABLE IF EXISTS "db".kind;
DROP TABLE IF EXISTS "db".font;
DROP TABLE IF EXISTS "db".community;
DROP TABLE IF EXISTS "db".communicant;
DROP TABLE IF EXISTS "db".codelicense;
DROP TABLE IF EXISTS "db".chat;
DROP TABLE IF EXISTS "db".answer;
DROP TABLE IF EXISTS "db".account;
DROP SCHEMA IF EXISTS "db";

--
-- Name: out; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA "db";
ALTER SCHEMA "db" OWNER TO postgres;
SET default_tablespace = '';
SET default_with_oids = false;

--
-- Name: account; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".account (
    account_id integer,
    account_name text,
    account_image bytea,
    account_is_imported boolean
);

ALTER TABLE "db".account OWNER TO postgres;

--
-- Name: answer; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".answer (
    answer_id integer,
    question_id integer,
    account_id integer,
    answer_at timestamp with time zone,
    answer_markdown text,
    answer_change_at timestamp with time zone,
    answer_votes integer,
    license_id integer,
    codelicense_id integer,
    answer_se_answer_id integer,
    answer_flags integer,
    answer_crew_flags integer,
    answer_active_flags integer,
    answer_se_imported_at timestamp with time zone,
    answer_proposed_answer_id integer,
    answer_summary text,
    answer_permit_later_license boolean,
    answer_permit_later_codelicense boolean
);


ALTER TABLE "db".answer OWNER TO postgres;

--
-- Name: chat; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".chat (
    community_id integer,
    room_id integer,
    chat_id bigint,
    account_id integer,
    chat_reply_id integer,
    chat_change_id bigint,
    chat_at timestamp with time zone,
    chat_change_at timestamp with time zone,
    chat_markdown text
);


ALTER TABLE "db".chat OWNER TO postgres;

--
-- Name: codelicense; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".codelicense (
    codelicense_id integer,
    codelicense_name text,
    codelicense_is_versioned boolean,
    codelicense_description text
);


ALTER TABLE "db".codelicense OWNER TO postgres;

--
-- Name: communicant; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".communicant (
    account_id integer,
    community_id integer,
    communicant_votes integer
);


ALTER TABLE "db".communicant OWNER TO postgres;

--
-- Name: community; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".community (
    community_id integer,
    community_name text,
    community_room_id integer,
    community_dark_shade bytea,
    community_mid_shade bytea,
    community_light_shade bytea,
    community_highlight_color bytea,
    community_sesite_id integer,
    community_code_language text,
    community_regular_font_id integer,
    community_monospace_font_id integer,
    community_display_name text,
    community_type db.community_type_enum,
    community_warning_color bytea,
    community_regular_font_is_locked boolean,
    community_monospace_font_is_locked boolean,
    community_tables_are_monospace boolean,
    community_is_coming_soon boolean,
    community_ordinal integer,
    community_about_question_id integer,
    community_image bytea
);


ALTER TABLE "db".community OWNER TO postgres;

--
-- Name: font; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".font (
    font_id integer,
    font_name text,
    font_is_monospace boolean
);


ALTER TABLE "db".font OWNER TO postgres;

--
-- Name: kind; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".kind (
    kind_id integer,
    kind_description text,
    kind_short_description text,
    kind_can_all_edit boolean,
    kind_has_answers boolean,
    kind_has_question_votes boolean,
    kind_has_answer_votes boolean,
    kind_minimum_votes_to_answer integer,
    kind_allows_question_multivotes boolean,
    kind_allows_answer_multivotes boolean,
    kind_show_answer_summary_toc boolean,
    kind_account_id integer
);


ALTER TABLE "db".kind OWNER TO postgres;

--
-- Name: license; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".license (
    license_id integer,
    license_name text,
    license_href text,
    license_is_versioned boolean,
    license_description text
);


ALTER TABLE "db".license OWNER TO postgres;

--
-- Name: question; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".question (
    community_id integer,
    account_id integer,
    question_id integer,
    question_at timestamp with time zone,
    question_title text,
    question_markdown text,
    question_room_id integer,
    question_change_at timestamp with time zone,
    question_votes integer,
    license_id integer,
    codelicense_id integer,
    question_poll_id bigint,
    question_poll_major_id bigint,
    question_poll_minor_id bigint,
    question_se_question_id integer,
    question_flags integer,
    question_crew_flags integer,
    question_active_flags integer,
    question_se_imported_at timestamp with time zone,
    question_permit_later_license boolean,
    question_permit_later_codelicense boolean,
    kind_id integer,
    question_sesite_id integer
);


ALTER TABLE "db".question OWNER TO postgres;

--
-- Name: question_tag_x; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".question_tag_x (
    question_id integer,
    tag_id integer,
    community_id integer,
    account_id integer,
    question_tag_x_at timestamp with time zone
);


ALTER TABLE "db".question_tag_x OWNER TO postgres;

--
-- Name: room; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".room (
    community_id integer,
    room_id integer,
    room_type db.room_type_enum,
    room_name text,
    room_image bytea
);


ALTER TABLE "db".room OWNER TO postgres;

--
-- Name: sanction; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".sanction (
    kind_id integer,
    community_id integer,
    sanction_ordinal integer
);


ALTER TABLE "db".sanction OWNER TO postgres;

--
-- Name: sesite; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".sesite (
    sesite_id integer,
    sesite_url text
);


ALTER TABLE "db".sesite OWNER TO postgres;

--
-- Name: tag; Type: TABLE; Schema: db; Owner: postgres
--

CREATE TABLE "db".tag (
    tag_id integer,
    community_id integer,
    tag_at timestamp with time zone,
    tag_name text,
    tag_description text,
    tag_implies_id integer,
    tag_question_count integer
);


ALTER TABLE "db".tag OWNER TO postgres;
