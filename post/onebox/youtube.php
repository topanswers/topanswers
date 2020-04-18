<?
include '../../config.php';
include '../../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
$thumb = file_get_contents('https://img.youtube.com/vi/'.$_POST['id'].'/mqdefault.jpg');
if(!getimagesizefromstring($thumb)) fail(400,'no YouTube image found');
$hash = hash('sha256',$thumb);
$path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
$fname = $path.'/'.$hash;
is_dir($path) || mkdir($path,0777,true);
if(!file_exists($fname)) file_put_contents($fname,$thumb);
exit('@@@ youtube '.$_POST['id'].' '.$hash);
