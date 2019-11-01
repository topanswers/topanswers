<?    
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
isset($_COOKIE['uuid']) or die('Not registered');
$uuid = $_COOKIE['uuid'];
$pin = str_pad(rand(0,pow(10,12)-1),12,'0',STR_PAD_LEFT);
if($uuid) ccdb("select login($1)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
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
}
if(isset($_GET['pin'])){
  db("select authenticate_pin($1)",$_GET['pin']);
  exit;
}
if(isset($_GET['uuid'])){
  exit(ccdb("select account_uuid from my_account"));
}
$custompic = (ccdb("select account_image is null from my_account")==='f');
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
  </style>
  <script src="/jquery.js"></script>
  <script>
    $(function(){
      $('#pin').click(function(){ $(this).prop('disabled',true); $.get('/profile?pin=<?=$pin?>').done(function(){ $('#pin').replaceWith('<code><?=$pin?></code>'); }); });
      $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span>'+r+'</span>'); }); });
    });
  </script>
  <title>Profile | TopAnswers</title>
</head>
<body>
  <fieldset>
    <legend>display name</legend>
    <form action="/profile" method="post">
      <input type="text" name="name" placeholder="name" value="<?=ccdb("select account_name from my_account")?>" autocomplete="off" autofocus>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>picture</legend>
    <img src="/identicon.php?id=<?=ccdb("select account_id from my_account")?>">
    <?if($custompic){?>
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
    <legend>link another device/browser to this account</legend>
    <ol>
      <li>Go to https://topanswers.xyz on the other device and click 'link'</li>
      <li>Enter this PIN (within 1 minute of generation): <input id="pin" type="button" value="generate PIN"></li>
    </ol>
  </fieldset>
  <fieldset>
    <legend>account recovery</legend>
    <ul>
      <li>Your account recovery token should be kept confidential like a password</li>
      <li>It can be used in the same way as a PIN, but does not expire</li>
      <li><input id="uuid" type="button" value="show token"></li>
    </ul>
  </fieldset>
</body>   
</html>   
