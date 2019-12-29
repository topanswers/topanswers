<?
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
db("set search_path to upload,pg_temp");
ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');

isset($_FILES['image']) || exit('no file uploaded');
$hash = hash_file('sha256',$_FILES['image']['tmp_name']);
$path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
$fname = $path.'/'.$hash;
is_dir($path) || mkdir($path,0777,true);
if(!file_exists($fname)) move_uploaded_file($_FILES['image']['tmp_name'],$fname);
exit($hash);
