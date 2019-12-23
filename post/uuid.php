<?    
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');

$uuid = exec('uuidgen');
if(isset($_POST['pin'])){
  if(is_numeric($_POST['pin'])) db('select link_account($1,$2)',$uuid,$_POST['pin']);
  else db('select recover_account($1,$2)',$uuid,$_POST['pin']);
}else{
  db('select new_account($1)',$uuid);
}
setcookie("uuid",$uuid,2147483647,'/','.topanswers.xyz',true,true);
