<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to navigation,pg_temp");
$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
$environment = $_COOKIE['environment']??'prod';
extract(cdb("select community_id,community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light,account_is_dev from one"));
$dev = $account_is_dev;
$_GET['community']===$community_name || fail(400,'invalid community');
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<div class="container">
  <script>
    $(function(){
      $('#environment').change(function(){
        var v = $(this).val();
        if(v==='prod'){
          Cookies.remove('environment',{ secure: true, domain: '.topanswers.xyz' });
        }else{
          Cookies.set('environment',v,{ secure: true, domain: '.topanswers.xyz' });
        }
        $(this).attr('disabled',true);
        window.location.reload(true);
      });
      $('.select>div:first-child').click(function(e){ $(this).parent().toggleClass('open'); e.stopPropagation(); });
      $('.select>div:last-child a').click(function(e){ e.stopPropagation(); return true; });
      $('.select>div:last-child').click(function(e){ return false; });
      $('body').click(function(){ $('.select').removeClass('open'); });
    });
  </script>
  <a class="frame" style="background: white;" href="/" title="home"><img class="icon" src="/image?hash=cb8fe8c88f6b7326bcca667501eaf8b1f1e2ef46af1bc0c37eeb71daa477e1be"></a>
  <a class="frame" href="/<?=$community_name?>" title="<?=$community_display_name?> home"><img class="icon" src="/communityicon?community=<?=$community_name?>"></a>
  <div class="select element">
    <div accesskey="t">
      <span class="wideonly">TopAnswers&nbsp;</span>
      <span><?=$community_display_name?></span>
      <i class="fa fa-chevron-down"></i>
    </div>
    <div>
      <div>
        <?foreach(db("select community_name,community_room_id,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light
                      from community
                      order by community_my_votes desc nulls last, community_ordinal, community_name") as $r){ extract($r,EXTR_PREFIX_ALL,'s');?>
          <div data-community="<?=$s_community_name?>" style="--rgb-dark: <?=$s_community_rgb_dark?>; --rgb-mid: <?=$s_community_rgb_mid?>; --rgb-light: <?=$s_community_rgb_light?>;">
            <a href="/<?=$s_community_name?>">
              <div class="frame"><img class="icon" src="/communityicon?community=<?=$s_community_name?>"></div>
              <?=$s_community_display_name?>
            </a>
            <?if($s_community_about_question_id){?><a href="/<?=$s_community_name?>?q=<?=$s_community_about_question_id?>">about</a><?}?>
          </div>
        <?}?>
        <?if($dev){?>
          <select id="environment" class="element" style="margin: 6px;">
            <?foreach(db("select environment_name from environment") as $r){ extract($r);?>
              <option<?=($environment===$environment_name)?' selected':''?>><?=$environment_name?></option>
            <?}?>
          </select>
        <?}?>
      </div>
    </div>
  </div>
</div>
<?ob_end_flush();
