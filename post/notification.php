<?    
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to notification,pg_temp");
ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');

switch($_POST['action']) {
  case 'dismiss-question': exit(ccdb("select dismiss_question($1)",$_POST['id']));
  case 'dismiss-question-flag': exit(ccdb("select dismiss_question_flag($1)",$_POST['id']));
  case 'dismiss-answer': exit(ccdb("select dismiss_answer($1)",$_POST['id']));
  case 'dismiss-answer-flag': exit(ccdb("select dismiss_answer_flag($1)",$_POST['id']));
  default: fail(400,'unrecognized action');
}
