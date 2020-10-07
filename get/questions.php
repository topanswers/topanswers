<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to questions,pg_temp");
if(isset($_GET['one'])&&!isset($_GET['id'])) fail(400,'if "one" is set, id must be set too');
if(isset($_GET['one'])&&!isset($_GET['community'])){
  $auth = ccdb("select login_question(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['id']);
}else{
  $auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
}
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(question_id,question_poll_minor_id)),'[]')::json from question where community_id=api.get_community_id() and question_poll_minor_id>$1",$_GET['from']));
$search = $_GET['search']??'';
$type = 'simple';
if($search && trim(preg_replace('/\[[^\]]+]|{[^}]*}|\([^\)]*\)/','',$search),' !+@')) $type = 'fuzzy';
if(isset($_GET['one'])) $type = 'one';
$page = $_GET['page']??'1';
$pagesize = $_COOKIE['pagesize']??'10';
extract(cdb("select account_id,community_name,community_language
                  , (select coalesce(jsonb_agg(z order by question_ordinal),'[]'::jsonb)
                     from (select question_id,question_ordinal,question_count,question_at,question_change_at,question_change,question_is_answered,question_title,question_votes,question_votes_from_me
                                 ,question_account_id,question_account_name,question_poll_major_id,question_poll_minor_id,question_is_deleted,question_communicant_votes,question_is_imported
                                 ,question_account_image_url,question_visible_chat_count,question_is_published
                                 ,community_id,community_name,community_display_name,community_my_power,community_image_url
                                 ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                                 ,sanction_short_description
                                , to_char(coalesce(question_published_at,question_at),'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                                , to_char(question_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_change_at_iso
                                , extract('epoch' from current_timestamp-coalesce(question_published_at,question_at))::bigint question_when
                                , extract('epoch' from current_timestamp-question_change_at)::bigint question_change_when
                                , (select coalesce(jsonb_agg(z),'[]'::jsonb) from (select tag_id,tag_name,tag_description from tag t where t.question_id=q.question_id order by tag_question_count desc, tag_name) z) tags
                           from (select question_id, 1 question_ordinal, 1 question_count from question where $1='one' and question_id=$2::integer
                                 union all
                                 select question_id,question_ordinal,question_count from simple_recent($3,$4::integer,$5::integer) where $1='simple'
                                 union all
                                 select question_id,question_ordinal,question_count from fuzzy_closest($3,$4::integer,$5::integer) where $1='fuzzy') q
                                natural join question) z) questions
             from one",$type,$_GET['id']??'0',$search,$page,$pagesize),EXTR_PREFIX_ALL,'o');
include '../lang/questions.'.$o_community_language.'.php';
?>
<?foreach($o_questions as $r){ extract($r);?>
  <div id="q<?=$question_id?>"
       class="question post<?=$question_is_deleted?' deleted':''?><?=($community_name!==$o_community_name)?' foreign':''?><?=$question_is_imported?' imported':''?>"
       style="--rgb-dark: <?=$community_rgb_dark?>;
              --rgb-mid: <?=$community_rgb_mid?>;
              --rgb-light: <?=$community_rgb_light?>;
              --rgb-highlight: <?=$community_rgb_highlight?>;
              --rgb-warning: <?=$community_rgb_warning?>;
              "
       data-id="<?=$question_id?>"
       data-poll-major-id="<?=$question_poll_major_id?>"
       data-poll-minor-id="<?=$question_poll_minor_id?>"
       data-of="<?=$question_count?>">
    <div class="title">
      <a href="/<?=$community_name?>?q=<?=$question_id?>"><span title="<?=$question_title?>"><?=$question_is_published?'':'DRAFT: '?><?=$question_title?></span></a>
      <?if($question_visible_chat_count){?>
        <span class="corner" title="<?=$l_comments?>: <?=$l_num($question_visible_chat_count)?>">
          <span><?=$l_num($question_visible_chat_count)?></span><i class="element fa fa-comments"></i>
        </span>
      <?}?>
      <?if($question_votes){?>
        <span class="corner" title="<?=$l_stars?>: <?=$l_num($question_votes)?>">
          <span><?=$l_num($question_votes)?></span><i class="element fa fa-star<?=(($question_account_id!==$o_account_id)&&($question_votes_from_me<$community_my_power))?'-o':''?><?=$question_votes_from_me?' highlight':''?>"></i>
        </span>
      <?}?>
    </div>
    <div class="bar">
      <div class="element container shrink">
        <?if($community_name!==$o_community_name){?>
          <a class="community element" href="/<?=$community_name?>" title="<?=$community_display_name?>"><img src="<?=$community_image_url?>"></a>
        <?}?>
        <?if($sanction_short_description){?><span class="kind element"><?=$sanction_short_description?></span><?}?>
        <div class="tags">
          <?foreach($tags as $r){ extract($r);?>
            <a href="/tags?community=<?=$community_name?>#t<?=$tag_id?>" class="tag element" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>" title="<?=$tag_description?>"><?=$tag_name?></a>
          <?}?>
        </div>
      </div>
      <div>
        <?if($question_change!=='asked'){?>
          <span class="element hover wideonly when" data-prefix="(<?=$question_change?>, " data-postfix=")" data-seconds="<?=$question_change_when?>" data-at="<?=$question_change_at_iso?>"></span>
        <?}?>
        <span class="when element hover" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"><?=$question_account_name?></span>
        <span class="element"><?=$question_account_name?></span>
        <a href="/user?id=<?=$question_account_id?>&community=<?=$community_name?>">
          <img title="<?=$l_stars?>: <?=$l_num($question_communicant_votes)?>" class="icon" data-name="<?=explode(' ',$question_account_name)[0]?>" src="<?=$question_account_image_url?>">
        </a>
      </div>
    </div>
    <?if($question_is_answered){?>
      <div class="answers">
        <?foreach(db("with l as (select unnest(label_ids) label_id from (select label_ids from questions.parse($1) limit 1) z)
                      select answer_id,answer_change,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_account_name,answer_is_deleted,answer_communicant_votes,answer_summary
                            ,answer_account_image_url
                            ,label_name,label_url
                           , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                           , to_char(answer_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_change_at_iso
                           , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                           , extract('epoch' from current_timestamp-answer_change_at)::bigint answer_change_when
                           , count(*) over () answer_count
                      from answer
                      where question_id=$2 and (label_id in (select label_id from l) or not exists (select 1 from l))
                      order by answer_votes desc, answer_communicant_votes desc, answer_id desc",$search,$question_id) as $i=>$r){ extract($r);?>
          <div class="bar<?=$answer_is_deleted?' deleted':''?>">
            <div class="element summary shrink grow">
              <span class="stars" title="<?=$l_stars?>: <?=$l_num($answer_votes)?>">
                <i class="fa fa-star<?=(($answer_account_id!==$o_account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>"></i>
                <span><?=$l_num($answer_votes)?></span>
              </span>
              <?if($label_name){?>
                <?if($label_url){?>
                  <a href="<?=$label_url?>" class="label element"><?=$label_name?></a>
                <?}else{?>
                  <span class="label element"><?=$label_name?></span>
                <?}?>
              <?}?>
              <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" class="element shrink grow"><span data-markdown="<?=$answer_summary?>"><?=$answer_summary?></span></a>
            </div>
            <div>
              <?if($answer_change!=='answered'){?>
                <span class="element hover when" data-prefix="(<?=$answer_change?>, " data-postfix=")" data-seconds="<?=$answer_change_when?>" data-at="<?=$answer_change_at_iso?>"></span>
              <?}?>
              <span class="when element hover" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
              <span class="element"><?=$answer_account_name?></span>
              <a href="/user?id=<?=$answer_account_id?>&community=<?=$community_name?>">
                <img title="<?=$l_stars?>: <?=$l_num($answer_communicant_votes)?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="<?=$answer_account_image_url?>">
              </a>
            </div>
          </div>
        <?}?>
      </div>
    <?}?>
  </div>
<?}?>
