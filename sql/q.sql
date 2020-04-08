create schema q;
set local search_path to q,api,pg_temp;
--
create view community as select community_id,community_name,community_display_name,community_code_language from db.community;
