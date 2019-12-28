<?    
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to answer,pg_temp");
if(isset($_POST['id'])){
  ccdb("select login_answer(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_POST['id']) || fail(403,'access denied');
}else{
  ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_POST['question']??'') || fail(403,'access denied');
}

switch($_POST['action']) {
  case 'vote': exit(ccdb("select vote($1)",$_POST['votes']));
  case 'flag': exit(ccdb("select flag($1)",$_POST['direction']));
  case 'new':
    $id=ccdb("select new($1,$2,$3)",$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
    setcookie('clearlocal',$_POST['community'].'.answer.'.$_POST['question'],0,'/','topanswers.xyz',true,true);
    header('Location: //topanswers.xyz/'.$_POST['community'].'?q='.$_POST['question'].'#a'.$id);
    exit;
  case 'change':
    db("select change($1)",$_POST['markdown']);
    extract(cdb("select community_name,question_id from answer.one"));
    header('Location: //topanswers.xyz/'.$community_name.'?q='.$question_id.'#a'.$_POST['id']);
    exit;
  default: fail(400,'unrecognized action');
}
