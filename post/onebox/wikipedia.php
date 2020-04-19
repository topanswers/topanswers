<?
include '../../config.php';
include '../../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');

preg_match('|^(?:https?://)([a-z]+).wikipedia.org/wiki/(.+)$|',$_POST['url'],$matches);

$postdata = http_build_query(array('action'=>'shortenurl','format'=>'json','url'=>$_POST['url']));
$opts = array('http'=>array('method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\nContent-Length: '.strlen($postdata).'\r\n','content'=>$postdata));
$short = json_decode(file_get_contents('https://meta.wikimedia.org/w/api.php',false,stream_context_create($opts)),true)['shortenurl']['shorturl'];

$data = json_decode(file_get_contents('https://'.$matches[1].'.wikipedia.org/api/rest_v1/page/summary/'.$matches[2]),true);
$extract = $data['extract'];
if(strlen($extract)>300) $extract = trim(substr($extract,0,250)).'…';

if(isset($data['thumbnail'])){
  $thumb = file_get_contents($data['thumbnail']['source']);
  if(!getimagesizefromstring($thumb)) fail(400,'no image found');
  $hash = hash('sha256',$thumb);
  $path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
  $fname = $path.'/'.$hash;
  is_dir($path) || mkdir($path,0777,true);
  if(!file_exists($fname)) file_put_contents($fname,$thumb);
}else{
  $hash = '0000000000000000000000000000000000000000000000000000000000000000';
}
exit('@@@ wikipedia '.$hash.' '.substr($short,15).' "'.htmlspecialchars($data['title']).'" "'.htmlspecialchars($extract).'"');
