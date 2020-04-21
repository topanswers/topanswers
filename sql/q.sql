create schema q;
set local search_path to q,api,pg_temp;
--
create view community as select community_id,community_name,community_display_name,community_code_language from db.community;
--
create view kind as
select kind_id,kind_description,kind_short_description, (select array_agg(community_name) from db.community natural join db.sanction s where s.kind_id=k.kind_id) communities
from db.kind k;
