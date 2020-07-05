<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to tags,pg_temp");
$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
extract(cdb("select account_image_url
                   ,community_name,community_display_name,community_language,community_my_regular_font_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight
                  , (select coalesce(jsonb_agg(z order by tag_name),'[]'::jsonb)
                     from (select tag_id,tag_name,tag_question_count,tag_code_language,tag_implies_id,tag_implies_name
                           from tag natural left join (select tag_id tag_implies_id, tag_name tag_implies_name from tag) z) z) tags
             from one"));
include '../../../lang/tags.'.$community_language.'.php';
$cookies = 'Cookie: '.(isset($_COOKIE['uuid'])?'uuid='.$_COOKIE['uuid'].'; ':'').(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':'');
?>
<!doctype html>
<html style="--rgb-dark:<?=$community_rgb_dark?>;
             --rgb-mid:<?=$community_rgb_mid?>;
             --rgb-light:<?=$community_rgb_light?>;
             --rgb-highlight:<?=$community_rgb_highlight?>;
             --rgb-white:255,255,255;
             --rgb-black:0,0,0;
             --font-regular:<?=$community_my_regular_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$community_my_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/page/tags/tags.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title>Tags - <?=$community_display_name?> - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/tags/tags.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div>
      <?if($auth){?>
        <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a>
      <?}?>
    </div>
  </header>
  <main>
    <table>
      <thead>
        <th><?=$l_name?></th>
        <th><?=$l_questions?></th>
        <th><?=$l_implies?></th>
        <th><?=$l_language?></th>
      </thead>
      <tbody>
        <?foreach($tags as $r){ extract($r,EXTR_PREFIX_ALL,'o');?>
          <tr id="t<?=$o_tag_id?>">
            <td><?=$o_tag_name?></td>
            <td><a href="/<?=$community_name?>?search=[<?=$o_tag_name?>]"><?=$o_tag_question_count?></a></td>
            <td><a href="#t<?=$o_tag_implies_id?>"><?=$o_tag_implies_name?></a></td>
            <td><?=$o_tag_code_language?></td>
          </tr>
        <?}?>
      </tbody>
    </table>
  </main>
</body>   
</html>   
