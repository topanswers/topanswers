<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to navigation,pg_temp");
if(isset($_GET['community'])){
  db("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
}else{
  db("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
}
$environment = $_COOKIE['environment']??'prod';
extract(cdb("select one_image_url,community_id,community_name,community_language,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_image_url
             from one"));
include '../lang/navigation.'.$community_language.'.php';
?>
<div class="container">
  <a class="frame" href="/" title="home"><img class="icon" src="<?=$one_image_url?>"></a>
  <?if(isset($_GET['community'])){?><a class="frame" href="/<?=$community_name?>" title="<?=$community_display_name?> home"><img class="icon" src="<?=$community_image_url?>"></a><?}?>
  <div id="mainnav" class="select element">
    <div accesskey="t">
      <span class="wideonly"><?=$l_topanswers?>&nbsp;</span>
      <span><?=$community_display_name?></span>
      <?include '../icons/chevron-down-light.html';?>
    </div>
    <div>
      <div>
        <?foreach(db("select community_name,community_room_id,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,community_about_question_id,community_image_url
                      from community
                      order by community_my_votes desc nulls last, community_ordinal, community_name") as $r){ extract($r,EXTR_PREFIX_ALL,'s');?>
          <div data-community="<?=$s_community_name?>" style="--rgb-dark: <?=$s_community_rgb_dark?>; --rgb-mid: <?=$s_community_rgb_mid?>; --rgb-light: <?=$s_community_rgb_light?>;">
            <a href="/<?=$s_community_name?>">
              <div class="frame"><img class="icon" src="<?=$s_community_image_url?>"></div>
              <?=$s_community_display_name?>
            </a>
          </div>
        <?}?>
      </div>
    </div>
  </div>
</div>
