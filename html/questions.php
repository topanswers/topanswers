<?
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid'] ?? false;
if($uuid) ccdb("select login($1)",$uuid);
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
extract(cdb("select community_id
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(question_id,question_poll_minor_id)),'[]') from question where community_id=$1 and question_poll_minor_id>$2",$community_id,$_GET['fromid']));
$id = $_GET['id']??ccdb("select greatest(min(question_poll_major_id)-1,0) from (select question_poll_major_id from question where community_id=$1 order by question_poll_major_id desc limit 50) z",$community_id);
?>
<?foreach(db("select question_id,question_at,question_title,question_votes,question_have_voted,question_poll_major_id,question_poll_minor_id,account_id,account_name,account_is_me
                   , coalesce(account_community_votes,0) account_community_votes
                   , case question_type when 'question' then '' when 'meta' then (case community_name when 'meta' then '' else 'Meta Question: ' end) when 'blog' then 'Blog Post: ' end question_type
                   , extract('epoch' from current_timestamp-question_at) question_when
                   , extract('epoch' from current_timestamp-greatest(question_change_at,question_answer_change_at,question_retag_at)) bump_when
                   , case when question_retag_at>greatest(question_change_at,question_answer_change_at) then 'tag edit'
                          else (case when question_answer_change_at>question_change_at then 'answer'||(case when question_answer_change_at>question_answer_at then ' edit' else '' end) else (case when question_change_at>question_at then 'edit' end) end) end question_bump_reason
              from question natural join account natural join community natural left join account_community
              where community_id=$1 and ".(isset($_GET['one'])?'question_id=':'question_poll_major_id'.(isset($_GET['older'])?'<':'>'))."$2
              order by question_poll_major_id desc limit 20",$community_id,$id) as $r){ extract($r);?>
  <div id="q<?=$question_id?>" class="question" data-id="<?=$question_id?>" data-poll-major-id="<?=$question_poll_major_id?>" data-poll-minor-id="<?=$question_poll_minor_id?>">
    <a href="/<?=$community?>?q=<?=$question_id?>"><?=$question_type.$question_title?></a>
    <div class="bar">
      <div>
        <img title="Stars: <?=$account_community_votes?>" class="identicon" data-name="<?=explode(' ',$account_name)[0]?>" src="/identicon?id=<?=$account_id?>">
        <span><span class="when" data-seconds="<?=$question_when?>"></span>, by <?=htmlspecialchars($account_name)?></span>
        <?if($question_bump_reason){?><span>(<?=$question_bump_reason?>, <span class="when" data-seconds="<?=$bump_when?>"></span>)</span><?}?>
        <?if($question_votes){?>
          <span class="score<?=($question_have_voted==='t')?' me':''?>">
            <i class="fa fa-star<?=(($account_is_me==='f')&&($question_have_voted==='f')&&$question_votes)?'-o':''?>"></i>
            <?=($question_votes>1)?$question_votes:''?>
          </span>
        <?}?>
      </div>
      <div class="tags">
        <?foreach(db("select tag_id,tag_name from question_tag_x_not_implied natural join tag where question_id=$1",$question_id) as $r){ extract($r);?>
          <span class="tag" data-question-id="<?=$question_id?>" data-tag-id="<?=$tag_id?>"><?=$tag_name?> <i class="fa fa-times-circle"></i></span>
        <?}?>
      </div>
    </div>
    <?foreach(db("select answer_id,answer_markdown,account_id,answer_votes,answer_have_voted,account_name,account_is_me
                       , coalesce(account_community_votes,0) account_community_votes
                       , extract('epoch' from current_timestamp-answer_at) answer_when
                  from answer natural join account natural join (select question_id,community_id from question) q natural left join account_community
                  where question_id=$1
                  order by answer_votes desc, account_community_votes desc, answer_id desc",$question_id) as $r){ extract($r);?>
      <div class="minibar">
        <a href="/<?=$community?>?q=<?=$question_id?>#a<?=$answer_id?>" class="summary">Answer: <span data-markdown="<?=htmlspecialchars(strtok($answer_markdown,"\n\r"));?>"></span></a>
        <div>
          <?if($answer_votes){?>
            <span class="score<?=($answer_have_voted==='t')?' me':''?>">
              <?=($answer_votes>1)?$answer_votes:''?>
              <i class="fa fa-star<?=(($account_is_me==='f')&&($answer_have_voted==='f')&&$answer_votes)?'-o':''?>"></i>
            </span>
          <?}?>
          <span><span class="when" data-seconds="<?=$answer_when?>"></span> by <?=htmlspecialchars($account_name)?></span>
          <img title="Stars: <?=$account_community_votes?>" class="identicon" data-name="<?=explode(' ',$account_name)[0]?>" src="/identicon?id=<?=$account_id?>">
        </div>
      </div>
    <?}?>
  </div>
<?}?>
