<?    
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to question,pg_temp");
if(isset($_POST['id'])){
  ccdb("select login_question($1::uuid,$2::integer)",$_COOKIE['uuid'],$_POST['id']) || fail(403,'access denied');
}else{
  $auth = ccdb("select login_community($1::uuid,$2)",$_COOKIE['uuid'],$_POST['community']??'');
  $auth||(($_GET['community']==='databases')&&isset($_GET['rdbms'])&&isset($_GET['fiddle'])) || fail(403,'need to be logged in to visit this page unless from a fiddle');
}
extract(cdb("select community_id,community_name from one"));

switch($_POST['action']) {
  case 'new-tag': exit(ccdb("select new_tag($1)",$_POST['tagid']));
  case 'remove-tag': exit(ccdb("select remove_tag($1)",$_POST['tagid']));
  case 'vote': exit(ccdb("select vote($1)",$_POST['votes']));
  case 'subscribe': exit(ccdb("select subscribe()"));
  case 'unsubscribe': exit(ccdb("select unsubscribe()"));
  case 'flag': exit(ccdb("select flag($1)",$_POST['direction']));
  case 'change':
    db("select change($1,$2)",$_POST['title'],$_POST['markdown']);
    header('Location: //topanswers.xyz/'.$community_name.'?q='.$_POST['id']);
    exit;
  case 'new':
    $id=ccdb("select new($1::integer,$2,$3,$4,$5)",$_POST['kind'],$_POST['title'],$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
    if($id){
      setcookie('clearlocal',$community_name.'.ask',0,'/','topanswers.xyz',true,true);
      header('Location: //topanswers.xyz/'.$community_name.'?q='.$id);
    }
    exit;
  default: fail(400,'unrecognized action');
}
