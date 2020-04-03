<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to questions,pg_temp");
$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
$search = $_GET['search']??'';
extract(cdb("select account_id,account_is_dev,community_name,community_code_language,my_community_regular_font_name,my_community_monospace_font_name,community_my_power,num_questions from one"));
$_GET['community']===$community_name || fail(400,'invalid community');
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(question_id,question_poll_minor_id)),'[]')::json from question where question_poll_minor_id>$1",$_GET['fromid']));
if($search){
  db("select set_config('pg_trgm.strict_word_similarity_threshold','0.5',false)");
  $results = db("select question_id,question_at,question_change_at,question_change,question_is_answered,question_title,question_votes,question_votes_from_me,question_account_id,question_account_name
                       ,question_poll_major_id,question_poll_minor_id
                       ,question_is_deleted,question_communicant_votes
                       ,kind_short_description
                      , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                      , to_char(question_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_change_at_iso
                      , extract('epoch' from current_timestamp-question_at)::bigint question_when
                      , extract('epoch' from current_timestamp-question_change_at)::bigint question_change_when
                      , question_account_id=$2 account_is_me
                 from search($1) natural join question
                 order by rn",strtolower($_GET['search']),$account_id);
}else{
  if(isset($_GET['page'])){
    extract(cdb("select startid,endid from recent($1)",$_GET['page']));
  }elseif(isset($_GET['one'])){
    $startid = ccdb("select question_poll_major_id from question where question_id=$1",$_GET['id']);
    $endid = $startid;
  }else{
    $startid = intval($_GET['id'])+1;
    $endid = '';
  }
  $results = db("select question_id,question_change,question_is_answered,question_title,question_votes,question_votes_from_me,question_poll_major_id,question_poll_minor_id
                       ,question_account_id,question_account_name,question_is_deleted,question_communicant_votes
                       ,kind_short_description
                      , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                      , to_char(question_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_change_at_iso
                      , extract('epoch' from current_timestamp-question_at)::bigint question_when
                      , extract('epoch' from current_timestamp-question_change_at)::bigint question_change_when
                      , question_account_id=$3 account_is_me
                 from question where question_poll_major_id>=$1 and ($2='' or question_poll_major_id<=nullif($2,'')::integer)
                 order by question_poll_major_id desc",$startid,$endid,$account_id);
}
?>
<?foreach($results as $r){ extract($r);?>
  <div id="q<?=$question_id?>" class="question post<?=$question_is_deleted?' deleted':''?>" data-id="<?=$question_id?>" data-poll-major-id="<?=$question_poll_major_id?>" data-poll-minor-id="<?=$question_poll_minor_id?>" data-of="<?=$num_questions?>">
    <div class="title">
      <?if($kind_short_description){?><div><?=$kind_short_description?></div><?}?>
      <a href="/<?=$community_name?>?q=<?=$question_id?>" title="<?=$question_title?>"><?=$question_title?></a>
    </div>
    <div class="bar">
      <div>
        <a href="/user?id=<?=$question_account_id?>&community=<?=$community_name?>">
          <img title="Stars: <?=$question_communicant_votes?>" class="icon" data-name="<?=explode(' ',$question_account_name)[0]?>" src="/identicon?id=<?=$question_account_id?>">
        </a>
        <span class="element"><?=$question_account_name?></span>
        <?if($question_votes){?>
          <span class="element">
            <i class="fa fa-star<?=(($question_account_id!==$account_id)&&($question_votes_from_me<$community_my_power))?'-o':''?><?=$question_votes_from_me?' highlight':''?>" data-count="<?=$question_votes?>"></i>
          </span>
        <?}?>
        <span class="when element" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"><?=$question_account_name?></span>
        <?if($question_change!=='asked'){?>
          <span class="element hover wideonly when" data-prefix="(<?=$question_change?>, " data-postfix=")" data-seconds="<?=$question_change_when?>" data-at="<?=$question_change_at_iso?>"></span>
        <?}?>
      </div>
      <div class="element container">
        <?foreach(db("select question_id,tag_id,tag_name from tag where question_id=$1 order by tag_question_count",$question_id) as $r){ extract($r);?>
          <span class="tag element" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
        <?}?>
      </div>
    </div>
    <?if($question_is_answered){?>
      <div class="answers">
        <?foreach(db("select answer_id,answer_change,answer_markdown,answer_account_id,answer_votes,answer_votes_from_me,answer_account_name,answer_is_deleted,answer_communicant_votes,answer_summary
                           , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                           , to_char(answer_change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_change_at_iso
                           , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
                           , extract('epoch' from current_timestamp-answer_change_at)::bigint answer_change_when
                      from answer
                      where question_id=$1
                      order by answer_votes desc, answer_communicant_votes desc, answer_id desc",$question_id) as $i=>$r){ extract($r);?>
          <div class="bar<?=$answer_is_deleted?' deleted':''?>">
            <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" class="element summary shrink"><?=($i===0)?'Top ':''?>Answer<?=($i>0)?' #'.($i+1):''?>: <span data-markdown="<?=$answer_summary?>"><?=$answer_summary?></span></a>
            <div>
              <?if($answer_change!=='answered'){?>
                <span class="element hover when" data-prefix="(<?=$answer_change?>, " data-postfix=")" data-seconds="<?=$answer_change_when?>" data-at="<?=$answer_change_at_iso?>"></span>
              <?}?>
              <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
              <?if($answer_votes){?>
                <span class="element">
                  <i class="fa fa-star<?=(($answer_account_id!==$account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>" data-count="<?=$answer_votes?>"></i>
                </span>
              <?}?>
              <span class="element"><?=$answer_account_name?></span>
              <a href="/user?id=<?=$answer_account_id?>&community=<?=$community_name?>">
                <img title="Stars: <?=$answer_communicant_votes?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="/identicon?id=<?=$answer_account_id?>">
              </a>
            </div>
          </div>
        <?}?>
      </div>
    <?}?>
  </div>
<?}?>
