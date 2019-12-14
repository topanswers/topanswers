<?    
include '../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
include '../db.php';
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');

switch($_POST['action']) {
  case 'vote': exit(ccdb("select vote_answer($1,$2)",$_POST['id'],$_POST['votes']));
  case 'dismiss': exit(ccdb("select dismiss_answer_notification($1)",$_POST['id']));
  case 'dismiss-flag': exit(ccdb("select dismiss_answer_flag_notification($1)",$_POST['id']));
  case 'flag': exit(ccdb("select flag_answer($1,$2)",$_POST['id'],$_POST['direction']));
  case 'new':
    $id=ccdb("select new_answer($1,$2,$3,$4)",$_POST['question'],$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
    if($id){
      setcookie('clearlocal',$_POST['community'].'.answer.'.$_POST['question'],0,'/','topanswers.xyz',true,true);
      header('Location: //topanswers.xyz/'.$_POST['community'].'?q='.$_POST['question'].'#a'.$id);
    }
    exit;
  case 'change':
    db("select change_answer($1,$2)",$_POST['id'],$_POST['markdown']);
    extract(cdb("select community_name,question_id from get.answer natural join (select question_id,community_id from get.question) z natural join get.community where answer_id=$1",$_POST['id']));
    header('Location: //topanswers.xyz/'.$community_name.'?q='.$question_id);
    exit;
  default: fail(400,'unrecognized action');
}
