<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to room,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']??'') || fail(403,'access denied');
extract(cdb("select account_id,account_image_url,room_id,room_name,room_has_image,room_image_url,community_name,my_community_regular_font_name,my_community_monospace_font_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,community_image_url
             from one"));
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/fouc.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/page/room/room.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title>Room Settings - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/room/room.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div>
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a>
    </div>
  </header>
  <main>
    <fieldset>
      <legend>name</legend>
      <form action="//post.topanswers.xyz/room" method="post">
        <input type="hidden" name="id" value="<?=$room_id?>">
        <input type="text" name="name" placeholder="name" value="<?=$room_name?>" autocomplete="off" autofocus>
        <input type="submit" value="Save">
      </form>
    </fieldset>
    <fieldset>
      <legend>picture</legend>
      <div class="frame"><img class="icon" src="<?=$room_image_url?>"></div>
      <?if($room_has_image){?>
        <form action="//post.topanswers.xyz/room" method="post">
          <input type="hidden" name="id" value="<?=$room_id?>">
          <input type="hidden" name="image">
          <input type="submit" value="Remove">
        </form>
      <?}?>
      <hr>
      <form action="//post.topanswers.xyz/room" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?=$room_id?>">
        <input type="file" name="image" accept=".png,.gif,.jpg,.jpeg">
        <input type="submit" value="Save">
      </form>
    </fieldset>
  </main>
</body>   
</html>   
