<?    
include '../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
include '../db.php';
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');
isset($_POST['id']) || fail(400,'room id not set');
ccdb("select count(*) from get.room where room_id=$1",$_POST['id'])===1 || fail(400,'invalid room');

if(isset($_POST['action'])){
  switch($_POST['action']) {
    case 'switch':
      db("select read_room($1), read_room($2)",$_POST['from-id'],$_POST['id']);
      header('Location: //topanswers.xyz/'.ccdb("select community_name||'?'||(case when question_id is null then 'room='||room_id else 'q='||question_id end)
                                                 from get.room natural join get.community natural left join (select question_id, question_room_id room_id from get.question) q
                                                 where room_id=$1",$_POST['id']));
      exit;
    default: fail(400,'unrecognized action');
  }
}
if(isset($_POST['name'])){
  db("select change_room_name($1,nullif($2,''))",$_POST['id'],$_POST['name']);
  header('Location: //topanswers.xyz/room?id='.$_POST['id']);
  exit;
}
if(isset($_POST['image'])){
  db("select change_room_image($1,null)",$_POST['id']);
  header('Location: //topanswers.xyz/room?id='.$_POST['id']);
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
  db("select change_room_image($1,$2)",$_POST['id'],pg_escape_bytea(ob_get_contents()));
  ob_end_clean();
  header('Location: //topanswers.xyz/room?id='.$_POST['id']);
  exit;
}
