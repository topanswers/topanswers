<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to community,pg_temp");
if(isset($_COOKIE['uuid'])){ ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied'); }
$environment = $_COOKIE['environment']??'prod';
$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
if($auth) setcookie("uuid",$_COOKIE['uuid'],2147483647,'/','topanswers.xyz',null,true);
extract(cdb("select account_id
                   ,community_id,community_name,community_language,community_display_name,community_my_power,community_code_language,community_about_question_id,community_ask_button_text
                   ,community_banner_markdown,community_tables_are_monospace,community_image_url
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,my_community_regular_font_name,my_community_monospace_font_name
             from one"));
$_GET['community']===$community_name || fail(400,'invalid community');
include '../../../lang/community.'.$community_language.'.php';
$jslang = substr($community_language,0,1).substr(strtok($community_language,'-'),-1);
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/fouc.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/sample/sample.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/sample/sample.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <div id="qa">
    <div id="questions">
      <?$ch = curl_init('http://127.0.0.1/questions?community='.$community_name.'&search='.urlencode($_GET['search']??'')); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    </div>
  </div>
</body>
</html>
<?ob_end_flush();
