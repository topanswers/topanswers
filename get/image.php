<?    
header('X-Powered-By: ');
header('Cache-Control: max-age=31536000');
header("Content-type: image");
$hash = $_GET['hash'] ?? false;
$hash || exit('missing hash');
preg_match('/^[a-f0-9]{64}$/',$hash) || exit('invalid hash');
readfile('/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2).'/'.$hash);
