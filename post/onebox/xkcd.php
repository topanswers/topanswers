<?
include '../../config.php';
include '../../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
$data = json_decode(file_get_contents('https://xkcd.com/'.$_POST['id'].'/info.0.json'),true);
$thumb = file_get_contents($data['img']);
if(!getimagesizefromstring($thumb)) fail(400,'no image found');
$hash = hash('sha256',$thumb);
$path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
$fname = $path.'/'.$hash;
is_dir($path) || mkdir($path,0777,true);
if(!file_exists($fname)) file_put_contents($fname,$thumb);
exit('@@@ xkcd '.$_POST['id'].' '.$hash.' "'.htmlspecialchars($data['title']).'" "'.htmlspecialchars($data['alt']).'"');
