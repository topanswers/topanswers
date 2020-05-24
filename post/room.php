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
    case 'listen':
      db("select listen()");
      exit;
    case 'mute':
      db("select mute()");
      exit;
    case 'pin':
      db("select pin()");
      exit;
    case 'unpin':
      db("select unpin()");
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
  $image = ob_get_contents();
  ob_end_clean();
  $hash = hash('sha256',$image);
  $path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
  $fname = $path.'/'.$hash;
  is_dir($path) || mkdir($path,0777,true);
  if(!file_exists($fname)) file_put_contents($fname,$image);
  db("select change_image(decode($1,'hex'))",$hash);
  header('Location: //topanswers.xyz/room?id='.$room_id);
  exit;
}
