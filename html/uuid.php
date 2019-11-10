<?    
include '../db.php';
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = exec('uuidgen');
if(isset($_GET['pin'])){
  if(is_numeric($_GET['pin'])) db('select link_account($1,$2)',$uuid,$_GET['pin']);
  else db('select recover_account($1,$2)',$uuid,$_GET['pin']);
}else{
  db('select new_account($1)',$uuid);
}
setcookie("uuid",$uuid,2147483647,'/',null,null,true);
?>
