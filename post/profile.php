<?
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
db("set search_path to profile,pg_temp");
if(isset($_POST['community'])){
  $auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid'],$_POST['community']);
}else{
  $auth = ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']);
}
if($auth){
  if(isset($_POST['community'])){
    extract(cdb("select community_name,sesite_url from one"));
    switch($_POST['action']){
      case 'font': db("select change_fonts($1,$2)",$_POST['regular'],$_POST['mono']); header('Location: '.$_POST['location']); exit;
      case 'se':
        $ch = curl_init('https://api.stackexchange.com/2.2/me?key=fQPamdsPO4Okt9r*OKEp)g((&site='.explode('.',substr($sesite_url,8))[0].'&access_token='.$_POST['token']);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_ENCODING,'');
        $id = json_decode(curl_exec($ch),true)['items'][0]["user_id"];
        curl_close($ch);
        if($id) db("select set_se_user_id($1)",$id);
        header('Location: '.$_POST['location']);
        exit;
      default: fail(400,'unrecognized action for authenticated user with community set');
    }
  }else{
    switch($_POST['action']){
      case 'name': db("select change_name(nullif($1,''))",$_POST['name']); header('Location: '.$_POST['location']); exit;
      case 'remove-image': db("select change_image(null)"); header('Location: '.$_POST['location']); exit;
      case 'image':
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
        db("select change_image($1)",pg_escape_bytea(ob_get_contents()));
        ob_end_clean();
        header('Location: '.$_POST['location']);
        exit;
      case 'pin': db("select authenticate_pin($1)",$_POST['pin']); exit;
      case 'regenerate': db("select regenerate_account_uuid()"); header('Location: '.$_POST['location']); exit;
      case 'license': db("select change_license($1)",$_POST['license']); header('Location: '.$_POST['location']); exit;
      case 'codelicense': db("select change_codelicense($1)",$_POST['codelicense']); header('Location: '.$_POST['location']); exit;
      case 'resizer': exit(ccdb("select change_resizer($1)",$_POST['position']));
      case 'chat_resizer': exit(ccdb("select change_chat_resizer($1)",$_POST['position']));
      default: fail(400,'unrecognized action for authenticated user with community not set');
    }
  }
}else{
  $uuid = exec('uuidgen');
  setcookie("uuid",$uuid,2147483647,'/','.topanswers.xyz',true,true);
  switch($_POST['action']){
    case 'link':
      if(is_numeric($_POST['link'])) db('select link($1,$2::bigint)',$uuid,$_POST['link']);
      else db('select link($1,$2::uuid)',$uuid,$_POST['link']);
      exit;
    case 'new': exit(ccdb('select new($1)',$uuid));
    default: fail(400,'unrecognized action for unauthenticated user');
  }
}
