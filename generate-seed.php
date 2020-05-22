#!/bin/bash
psql postgres postgres -c "\copy (select community_id
                                        ,community_name
                                        ,community_room_id
                                        ,community_dark_shade
                                        ,community_mid_shade
                                        ,community_light_shade
                                        ,community_highlight_color
                                        ,community_code_language
                                        ,community_regular_font_id
                                        ,community_monospace_font_id
                                        ,community_display_name
                                        ,community_type
                                        ,community_warning_color
                                        ,community_regular_font_is_locked
                                        ,community_monospace_font_is_locked
                                        ,community_tables_are_monospace
                                        ,community_ordinal
                                        ,community_about_question_id
                                        ,community_image
                                        ,community_ask_button_text
                                        ,community_banner_markdown
                                        ,community_wiki_account_id
                                        ,community_tio_language
                                        ,community_import_sanction_id
                                  from community where community_type='public') TO '~/git/sql/seed/community'"


