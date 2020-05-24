<?    
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to room,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']??'') || fail(403,'access denied');
extract(cdb("select account_id,room_id,room_name,room_has_image,room_image_url,community_name,my_community_regular_font_name,my_community_monospace_font_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_image_url
             from one"));
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
?>
<!doctype html>
<html style="--rgb-dark: <?=$community_rgb_dark?>;
             --rgb-mid: <?=$community_rgb_mid?>;
             --rgb-light: <?=$community_rgb_light?>;
             --rgb-highlight: <?=$community_rgb_highlight?>;
             --rgb-warning: <?=$community_rgb_warning?>;
             --rgb-white: 255, 255, 255;
             --rgb-black: 0, 0, 0;
             --regular-font-family: '<?=$my_community_regular_font_name?>', serif;
             --monospace-font-family: '<?=$my_community_monospace_font_name?>', monospace;
             ">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: rgb(var(--rgb-mid)); }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { display: flex; flex-direction: column; align-items: flex-start; overflow: auto; scroll-behavior: smooth; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }

    fieldset { display: inline-block; margin: 16px; border: 1px solid rgb(var(--rgb-dark)); background: rgb(var(--rgb-white)); border-radius: 3px; }
    legend { background: rgb(var(--rgb-white)); border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; padding: 2px 4px; }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
    });
  </script>
  <title>Room Settings - TopAnswers</title>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigationx?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div>
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
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
