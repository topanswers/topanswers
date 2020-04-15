<?
include '../config.php';
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
db("set search_path to room,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_POST['id']??'') || fail(403,'access denied');
extract(cdb("select room_id,community_name,question_id from one"));
if(isset($_POST['action'])){
  switch($_POST['action']) {
    case 'mute':
      ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_POST['from-id']??'') || fail(403,'access denied');
      db("select mute()");
      exit;
    default: fail(400,'unrecognized action');
  }
}
if(isset($_POST['name'])){
  db("select change_name(nullif($1,''))",$_POST['name']);
  header('Location: //topanswers.xyz/room?id='.$room_id);
  exit;
}
if(isset($_POST['image'])){
  db("select change_image(null)");
  header('Location: //topanswers.xyz/room?id='.$room_id);
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
  imagepng(imagescale($image,64,64,IMG_BICUBIC));
  db("select change_image($1)",pg_escape_bytea(ob_get_contents()));
  ob_end_clean();
  header('Location: //topanswers.xyz/room?id='.$room_id);
  exit;
}
