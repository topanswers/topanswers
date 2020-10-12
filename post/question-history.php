<?
include '../config.php';
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_POST['id']) || fail(400,'must have an "id" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to question_history,pg_temp");
ccdb("select login_question($1::uuid,$2::integer)",$_COOKIE['uuid'],$_POST['id']) || fail(403,'access denied');

switch($_POST['action']) {
  case 'purge':
    db("select remove_drafts()");
    header('Location: //topanswers.xyz/question-history?id='.$_POST['id']);
    exit;
  default: fail(400,'unrecognized action');
}
