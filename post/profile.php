<?
include '../config.php';
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
    extract(cdb("select community_name from one"));
    switch($_POST['action']){
      case 'font':
        if(isset($_POST['regular'])) db("select change_regular_font($1)",$_POST['regular']);
        if(isset($_POST['mono'])) db("select change_monospace_font($1)",$_POST['mono']);
        header('Location: '.$_POST['location']);
        exit;
      case 'feeds':
        db("select change_syndications(('{'||$1||'}')::integer[])",isset($_POST['feed'])?implode(',',$_POST['feed']):'');
        header('Location: '.$_POST['location']);
        exit;
      case 'se':
        isset($_POST['sesite']) || fail(400,'must have an "sesite" parameter');
        extract(cdb("select sesite_url from sesite where sesite_id=$1",$_POST['sesite']));
        $ch = curl_init('https://api.stackexchange.com/2.2/me?key=fQPamdsPO4Okt9r*OKEp)g((&site='.explode('.',substr($sesite_url,8))[0].'&access_token='.$_POST['token']);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_ENCODING,'');
        $id = json_decode(curl_exec($ch),true)['items'][0]["user_id"];
        curl_close($ch);
        if($id) db("select set_se_user_id($1,$2)",$_POST['sesite'],$id);
        header('Location: '.$_POST['location']);
        exit;
      default: fail(400,'unrecognized action for authenticated user with community set');
    }
  }else{
    switch($_POST['action']){
      case 'name': db("select change_name(nullif($1,''))",$_POST['name']); header('Location: '.$_POST['location']); exit;
      case 'remove-image': db("select change_image(null,null)"); header('Location: '.$_POST['location']); exit;
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
        $image = ob_get_contents();
        ob_end_clean();
        $hash = hash('sha256',$image);
        $path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
        $fname = $path.'/'.$hash;
        is_dir($path) || mkdir($path,0777,true);
        if(!file_exists($fname)) file_put_contents($fname,$image);
        db("select change_image($1,decode($2,'hex'))",pg_escape_bytea($image),$hash);
        header('Location: '.$_POST['location']);
        exit;
      case 'pin': db("select authenticate_pin($1)",$_POST['pin']); exit;
      case 'regenerate': db("select regenerate_account_uuid()"); header('Location: '.$_POST['location']); exit;
      case 'license': db("select change_license($1,$2)",$_POST['license'],isset($_POST['orlater'])?'t':'f'); header('Location: '.$_POST['location']); exit;
      case 'codelicense': db("select change_codelicense($1,$2)",$_POST['codelicense'],isset($_POST['orlater'])?'t':'f'); header('Location: '.$_POST['location']); exit;
      case 'resizer': exit(ccdb("select change_resizer($1)",$_POST['position']));
      case 'chat_resizer': exit(ccdb("select change_chat_resizer($1)",$_POST['position']));
      default: fail(400,'unrecognized action for authenticated user with community not set');
    }
  }
}else{
  $uuid = exec('uuidgen');
  setcookie("uuid",$uuid,['expires'=>2147483647,'path'=>'/','domain'=>'.'.config("SITE_DOMAIN"),'secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
  switch($_POST['action']){
    case 'link':
      if(is_numeric($_POST['link'])) db('select link($1,$2::bigint)',$uuid,$_POST['link']);
      else db('select link($1,$2::uuid)',$uuid,$_POST['link']);
      exit;
    case 'new': exit(ccdb('select new($1)',$uuid));
    default: fail(400,'unrecognized action for unauthenticated user');
  }
}
