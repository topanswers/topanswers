<?
include '../config.php';
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['id']) || fail(400,'id must be set');
db("set search_path to duplicate,pg_temp");
db("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
ccdb("select count(1) from answer where answer_id=$1",$_GET['id'])===1 || fail(400,'not a valid id');
$auth = ccdb("select login_answer(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['id']);
extract(cdb("select account_id
                   ,community_name,community_language,community_my_power,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   ,question_id,question_title,question_votes,question_account_id,question_account_name,question_communicant_votes,question_votes_from_me,sanction_short_description
                  , to_char(question_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') question_at_iso
                  , extract('epoch' from current_timestamp-question_at)::bigint question_when
                   ,answer_id,answer_summary,answer_account_id,answer_account_name,answer_votes,answer_votes_from_me,answer_communicant_votes
                  , to_char(answer_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') answer_at_iso
                  , extract('epoch' from current_timestamp-answer_at)::bigint answer_when
             from one"));
include '../lang/duplicate.'.$community_language.'.php';
?>
<div class="question post" data-id="<?=$question_id?>" style="--rgb-dark: <?=$community_rgb_dark?>; --rgb-mid: <?=$community_rgb_mid?>; --rgb-light: <?=$community_rgb_light?>; --rgb-highlight: <?=$community_rgb_highlight?>; --rgb-warning: <?=$community_rgb_warning?>;">
  <div class="title">
    <a href="/<?=$community_name?>?q=<?=$question_id?>" title="<?=$question_title?>"><?=$question_title?></a>
    <?if($question_votes){?>
      <span class="stars" title="<?=$l_stars?>: <?=$l_num($question_votes)?>">
        <span><?=$l_num($question_votes)?></span>
        <i class="element stars fa fa-star<?=(($question_account_id!==$account_id)&&($question_votes_from_me<$community_my_power))?'-o':''?><?=$question_votes_from_me?' highlight':''?>"></i>
      </span>
    <?}?>
  </div>
  <div class="bar">
    <div class="element container shrink">
      <?if($sanction_short_description){?><span class="kind element"><?=$sanction_short_description?></span><?}?>
      <?foreach(db("select tag_id,tag_name from tag order by tag_question_count") as $r){ extract($r);?>
        <span class="tag element" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
      <?}?>
    </div>
    <div>
      <span class="element"><?=$question_account_name?></span>
      <a href="/user?id=<?=$question_account_id?>&community=<?=$community_name?>">
        <img title="<?=$l_stars?>: <?=$l_num($question_communicant_votes)?>" class="icon" data-name="<?=explode(' ',$question_account_name)[0]?>" src="/identicon?id=<?=$question_account_id?>">
      </a>
    </div>
  </div>
  <div class="answers">
    <div class="bar">
      <div class="element summary shrink">
        <span class="stars" title="<?=$l_stars?>: <?=$l_num($answer_votes)?>">
          <i class="fa fa-star<?=(($answer_account_id!==$account_id)&&($answer_votes_from_me<$community_my_power))?'-o':''?><?=$answer_votes_from_me?' highlight':''?>"></i>
          <span><?=$l_num($answer_votes)?></span>
        </span>
        <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>" class="element shrink"><span data-markdown="<?=$answer_summary?>"><?=$answer_summary?></span></a>
      </div>
      <div>
        <span class="element"><?=$answer_account_name?></span>
        <a href="/user?id=<?=$answer_account_id?>&community=<?=$community_name?>">
          <img title="<?=$l_stars?>: <?=$l_num($answer_communicant_votes)?>" class="icon" data-name="<?=explode(' ',$answer_account_name)[0]?>" src="/identicon?id=<?=$answer_account_id?>">
        </a>
      </div>
    </div>
  </div>
</div>
