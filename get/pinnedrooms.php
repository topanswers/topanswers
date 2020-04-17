<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['room']) || fail(400,'room must be set');
db("set search_path to pinnedrooms,pg_temp");
ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');
$rooms = ccdb("select jsonb_agg(z order by participant_chat_count, participant_latest_chat_at, room_question_id desc nulls last, room_id desc)
               from (select room_id,room_derived_name,room_question_id,community_display_name,community_name,community_rgb_light,participant_chat_count,participant_latest_chat_at from room) z");
?>
<?foreach($rooms as $r){ extract($r);?>
  <a class="frame<?=($room_id===intval($_GET['room']))?' this':''?>"
     style="--rgb-light: <?=$community_rgb_light?>"
     href="/<?=$community_name?>?<?=$room_question_id?'q='.$room_question_id:'room='.$room_id?>"
     data-id="<?=$room_id?>">
    <img title="<?=($community_name===$_GET['community'])?'':$community_display_name.': '?><?=$room_derived_name?>" class="icon roomicon" data-id="<?=$room_id?>" data-name="<?=$room_derived_name?>" src="/roomicon?id=<?=$room_id?>">
  </a>
<?}?>
