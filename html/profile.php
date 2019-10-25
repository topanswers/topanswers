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
if($uuid) ccdb("select set_config('custom.uuid',$1,false)",$uuid);
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
$custompic = (ccdb("select account_image is null from login natural join account where login_is_me")==='f');
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
  </style>
  <script src="/jquery.js"></script>
  <script>
    $(function(){ });
  </script>
  <title>Profile | TopAnswers</title>
</head>
<body>
  <fieldset>
    <legend>display name</legend>
    <form action="/profile" method="post">
      <input type="text" name="name" placeholder="name" value="<?=ccdb("select account_name from login natural join account  where login_is_me")?>" autocomplete="off" autofocus>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>picture</legend>
    <img src="/identicon.php?id=<?=ccdb("select account_id from login where login_is_me")?>">
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
</body>   
</html>   
