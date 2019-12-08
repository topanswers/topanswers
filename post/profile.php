<?    
include '../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
include '../db.php';
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');

if(isset($_POST['action'])){
  switch($_POST['action']) {
    case 'authenticate-pin': db("select authenticate_pin($1)",$_POST['pin']); exit;
    case 'regen': db("select regenerate_account_uuid()"); header('Location: //topanswers.xyz//profile'); exit;
    case 'font': db("select change_fonts((select community_id from get.community where community_name=$1),$2,$3)",$_POST['community'],$_POST['regular'],$_POST['mono']); header('Location: //topanswers.xyz/profile'); exit;
    default: fail(400,'unrecognized action');
  }
}
if(isset($_POST['name'])){
  db("select change_account_name(nullif($1,''))",$_POST['name']);
  header("Location: //topanswers.xyz/profile");
  exit;
}
if(isset($_POST['image'])){
  db("select change_account_image(null)");
  header("Location: //topanswers.xyz/profile");
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
  header("Location: //topanswers.xyz/profile");
  exit;
}
if(isset($_POST['license'])){
  db("select change_account_license_id($1)",$_POST['license']);
  header("Location: //topanswers.xyz/profile");
  exit;
}
if(isset($_POST['codelicense'])){
  db("select change_account_codelicense_id($1)",$_POST['codelicense']);
  header("Location: //topanswers.xyz/profile");
  exit;
}
