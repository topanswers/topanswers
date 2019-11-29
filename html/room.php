<?    
include 'db.php';
include 'nocache.php';
isset($_COOKIE['uuid']) or fail(403,'Not registered');
$uuid = $_COOKIE['uuid'];
if($uuid) ccdb("select login($1)",$uuid);
$id = $_GET['id'] ?? $_POST['id'] ?? 0;
$id or fail(400,'room id not set');
ccdb("select count(*) from room where room_id=$1",$id)==='1' or fail(400,'invalid room');
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['name'])){
    db("select change_room_name($1,nullif($1,''))",$id,$_POST['name']);
    header('Location: /room?id='.$id);
    exit;
  }
  if(isset($_POST['image'])){
    db("select change_room_image($1,null)",$id);
    header('Location: /room?id='.$id);
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
    db("select change_room_image($1,$2)",$id,pg_escape_bytea(ob_get_contents()));
    ob_end_clean();
    header('Location: /room?id='.$id);
    exit;
  }
}
extract(cdb("select room_name, room_image is not null room_has_image from room where room_id=$1",$id));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/fonts/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/fonts/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    fieldset { margin-bottom: 1rem; }
  </style>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
    });
  </script>
  <title>Room | TopAnswers</title>
</head>
<body>
  <fieldset>
    <legend>name</legend>
    <form action="/room" method="post">
      <input type="hidden" name="id" value="<?=$id?>">
      <input type="text" name="name" placeholder="name" value="<?=$room_name?>" autocomplete="off" autofocus>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>picture</legend>
    <img src="/roomicon?id=<?=$id?>">
    <?if($room_has_image==='t'){?>
      <form action="/room" method="post">
        <input type="hidden" name="id" value="<?=$id?>">
        <input type="hidden" name="image">
        <input type="submit" value="Remove">
      </form>
    <?}?>
    <hr>
    <form action="/room" method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?=$id?>">
      <input type="file" name="image" accept=".png,.gif,.jpg,.jpeg">
      <input type="submit" value="Save">
    </form>
  </fieldset>
</body>   
</html>   
