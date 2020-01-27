<?
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['id']) || fail(400,'id must be set');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to duplicate,pg_temp");
db("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
ccdb("select count(1) from answer where answer_id=$1",$_GET['id'])===1 || fail(400,'not a valid id');
ccdb("select count(1) from answer where answer_id=$1 and community_name=$2",$_GET['id'],$_GET['community'])===1 || fail(400,'wrong community');
$auth = ccdb("select login_answer(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['id']);
extract(cdb("select account_id
                   ,community_name
                   ,question_id,question_title,question_votes,question_account_id,question_account_name,question_communicant_votes,question_votes_from_me,kind_short_description
                  , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                  , extract('epoch' from current_timestamp-question_at)::bigint question_when
                   ,answer_id,answer_markdown,answer_account_id,answer_account_name,answer_votes,answer_votes_from_me,answer_communicant_votes
                  , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                  , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
             from one"));
?>
<div class="question post" data-id="<?=$question_id?>" data-poll-major-id="<?=$question_poll_major_id?>" data-poll-minor-id="<?=$question_poll_minor_id?>" data-of="<?=$num_questions?>">
  <div class="title">
    <?if($kind_short_description){?><div><?=$kind_short_description?></div><?}?>
    <a href="/<?=$community_name?>?q=<?=$question_id?>" title="<?=$question_title?>"><?=$question_title?></a>
  </div>
  <div class="bar">
    <div>
      <img title="Stars: <?=$question_communicant_votes?>" class="icon" data-name="<?=explode(' ',$question_account_name)[0]?>" src="/identicon?id=<?=$question_account_id?>">
      <span class="element"><?=$question_account_name?></span>
      <?if($question_votes){?>
        <span class="element">
          <i class="fa fa-star<?=(($question_account_id!==$account_id)&&($question_votes_from_me<$community_my_power))?'-o':''?><?=$question_votes_from_me?' highlight':''?>" data-count="<?=$question_votes?>"></i>
        </span>
      <?}?>
      <span class="when element" data-seconds="<?=$question_when?>" data-at="<?=$question_at_iso?>"></span>
    </div>
    <div class="element container">
      <?foreach(db("select tag_id,tag_name from tag order by tag_question_count") as $r){ extract($r);?>
        <span class="tag element" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
      <?}?>
    </div>
  </div>
  <div class="answers">
    <div class="bar">
      <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" class="element summary shrink">Answer: <span data-markdown="<?=strtok($answer_markdown,"\n\r");?>"><?=strtok($answer_markdown,"\n\r");?></span></a>
      <div>
        <span class="when element" data-seconds="<?=$answer_when?>" data-at="<?=$answer_at_iso?>"></span>
        <?if($answer_votes){?>
          <span class="element">
            <i class="fa fa-star<?=(($answer_account_id!==$account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>" data-count="<?=$answer_votes?>"></i>
          </span>
        <?}?>
        <span class="element"><?=$answer_account_name?></span>
        <img title="Stars: <?=$answer_communicant_votes?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="/identicon?id=<?=$answer_account_id?>">
      </div>
    </div>
  </div>
</div>
