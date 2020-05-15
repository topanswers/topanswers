create schema q;
set local search_path to q,api,pg_temp;
--
create view community as select community_id,community_name,community_display_name,community_code_language from db.community;
--
create view kind as
select kind_id
     , kind_can_all_edit can_all_edit
     , kind_has_answers has_answers
     , kind_has_question_votes has_question_votes
     , kind_minimum_votes_to_answer minimum_votes_to_answer
     , kind_show_answer_summary_toc toc
     , kind_questions_by_community questions_by_community
     , kind_answers_by_community answers_by_community
     , (select array_agg(community_name) from db.community natural join db.sanction s where s.kind_id=k.kind_id) communities
from db.kind k;
