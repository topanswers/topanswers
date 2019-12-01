<?    
include 'db.php';
include 'nocache.php';
isset($_COOKIE['uuid']) or die('Not registered');
$uuid = $_COOKIE['uuid'];
$pin = str_pad(rand(0,pow(10,12)-1),12,'0',STR_PAD_LEFT);
if($uuid) ccdb("select login($1)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['action'])){
    switch($_POST['action']) {
      case 'regen': db("select regenerate_account_uuid()"); header('Location: /profile'); exit;
      case 'font': db("select change_fonts((select community_id from community where community_name=$1),$2,$3)",$_POST['community'],$_POST['regular'],$_POST['mono']); header('Location: /profile'); exit;
      default: fail(400,'unrecognized action');
    }
  }
  if(isset($_POST['name'])){
    db("select change_account_name(nullif($1,''))",$_POST['name']);
    header("Location: /profile");
    exit;
  }
  if(isset($_POST['image'])){
    db("select change_account_image(null)");
    header("Location: /profile");
    exit;
  }
  if(isset($_FILES['image'])){
    switch(getimagesize($_FILES['image']['tmp_name'])[2]){
      case IMAGETYPE_JPEG:
        $image = imagecreatefromjpeg($_FILES['image']['tmp_name']);
        break;
      case IMAGETYPE_GIF:
        $image = imagecreatefromgif($_FILES['image']['tmp_name']);
        break;
      case IMAGETYPE_PNG:
        $image = imagecreatefrompng($_FILES['image']['tmp_name']);
        break;
      default:
        exit('wrong image format: need gif, png or jpeg');
    }
    ob_start();
    imagejpeg(imagescale($image,32,32,IMG_BICUBIC));
    db("select change_account_image($1)",pg_escape_bytea(ob_get_contents()));
    ob_end_clean();
    header("Location: /profile");
    exit;
  }
  if(isset($_POST['license'])){
    db("select change_account_license_id($1)",$_POST['license']);
    header("Location: /profile");
    exit;
  }
  if(isset($_POST['codelicense'])){
    db("select change_account_codelicense_id($1)",$_POST['codelicense']);
    header("Location: /profile");
    exit;
  }
}
if(isset($_GET['pin'])){
  db("select authenticate_pin($1)",$_GET['pin']);
  exit;
}
if(isset($_GET['uuid'])){
  exit(ccdb("select account_uuid from my_account"));
}
extract(cdb("select account_name,account_license_id,account_codelicense_id, account_image is not null account_has_image from my_account"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'quattrocento', serif; font-size: smaller;">
<head>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="/fonts/quattrocento.css">
  <style>
    *:not(hr) { box-sizing: inherit; }
    fieldset { margin-bottom: 1rem; }
  </style>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
      $('#pin').click(function(){ $(this).prop('disabled',true); $.get('/profile?pin=<?=$pin?>').done(function(){ $('#pin').replaceWith('<code><?=$pin?></code>'); }); });
      $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span>'+r+'</span>'); }); });
      $('[name]').on('change input',function(){ console.log('boo'); $(this).parents('fieldset').siblings('fieldset').find('[name],input').prop('disabled', true); });
    });
  </script>
  <title>Profile | TopAnswers</title>
</head>
<body>
  <fieldset>
    <legend>display name</legend>
    <form action="/profile" method="post">
      <input type="text" name="name" placeholder="name" value="<?=$account_name?>" autocomplete="off" autofocus>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>picture</legend>
    <img src="/identicon?id=<?=ccdb("select account_id from my_account")?>">
    <?if($account_has_image==='t'){?>
      <form action="/profile" method="post">
        <input type="hidden" name="image">
        <input type="submit" value="Remove">
      </form>
    <?}?>
    <hr>
    <form action="/profile" method="post" enctype="multipart/form-data">
      <input type="file" name="image" accept=".png,.gif,.jpg,.jpeg">
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>default license for new posts</legend>
    <form action="/profile" method="post">
      <select name="license">
        <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
          <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
        <?}?>
      </select>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>default additional license for code in new posts</legend>
    <form action="/profile" method="post">
      <select name="codelicense">
        <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
          <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
        <?}?>
      </select>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>link another device/browser to this account</legend>
    <ol>
      <li>Go to https://topanswers.xyz on the other device and click 'log in'</li>
      <li>Enter this PIN (within 1 minute of generation): <input id="pin" type="button" value="generate PIN"></li>
    </ol>
  </fieldset>
  <fieldset>
    <legend>account recovery</legend>
    <ul>
      <li>Your account 'login key' should be kept safe, and confidential, just like a password</li>
      <li>It can be used in the same way as a PIN, but does not expire</li>
      <li><input id="uuid" type="button" value="show key"></li>
      <li>If you suspect your 'key' has been discovered, you should regenerate it</li>
      <li><form action="/profile" method="POST"><input type="hidden" name="action" value="regen"><input type="submit" value="generate new key"></form></li>
    </ul>
  </fieldset>
  <?foreach(db("select community_name,account_community_regular_font_id,account_community_monospace_font_id
                from account_community natural join my_account natural join community natural join (select community_id,account_community_regular_font_id,account_community_monospace_font_id from my_account_community) z
                order by account_community_votes desc, community_id") as $r){extract($r);?>
    <fieldset>
      <legend><?=$community_name?></legend>
      <fieldset>
        <legend>fonts</legend>
        <form action="/profile" method="post">
          <input type="hidden" name="action" value="font">
          <input type="hidden" name="community" value="<?=$community_name?>">
          <label>regular
            <select name="regular">
              <?foreach(db("select font_id,font_name from font where not font_is_monospace") as $r){ extract($r);?>
                <option value="<?=$font_id?>"<?=($font_id===$account_community_regular_font_id)?' selected':''?>><?=$font_name?></option>
              <?}?>
            </select>
          </label>
          <label>monospace
            <select name="mono">
              <?foreach(db("select font_id,font_name from font where font_is_monospace") as $r){ extract($r);?>
                <option value="<?=$font_id?>"<?=($font_id===$account_community_monospace_font_id)?' selected':''?>><?=$font_name?></option>
              <?}?>
            </select>
          </label>
          <input type="submit" value="Save">
        </form>
      </fieldset>
    </fieldset>
  <?}?>
</body>   
</html>   
