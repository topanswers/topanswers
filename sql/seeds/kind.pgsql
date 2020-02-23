--
-- Data for Name: kind; Type: TABLE DATA; Schema: db; Owner: postgres
--

COPY "db".kind (kind_id, kind_description, kind_short_description, kind_can_all_edit, kind_has_answers, kind_has_question_votes, kind_has_answer_votes, kind_minimum_votes_to_answer, kind_allows_question_multivotes, kind_allows_answer_multivotes, kind_show_answer_summary_toc, kind_account_id) FROM stdin;
1	Question		t	t	f	t	0	t	t	f	\N
2	Meta Question	Meta	t	t	t	t	0	t	t	f	\N
3	Blog Post	Blog	f	f	t	f	0	t	t	f	\N
4	Code Golf	Code Golf	t	t	t	t	2	t	f	t	\N
5	Tips	Tips	t	t	t	t	0	f	f	t	\N
7	Contest	Contest	t	t	t	t	5	t	t	f	\N
8	Wiki Post	Wiki	t	f	t	f	0	t	f	f	901
6	Least Operations	Least Operations	t	t	t	t	2	t	f	t	\N
9	Speed Challenge	Speed Challenge	t	t	t	t	2	t	f	t	\N
10	Other Competition	Other Competition	t	t	t	t	2	t	f	t	\N
\.