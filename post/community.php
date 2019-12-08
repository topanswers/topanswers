<?
include '../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
include '../db.php';
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');

switch($_POST['action']){
  case 'resizer': exit(ccdb("select change_resizer($1)",$_POST['position']));
  default: fail(400,'unrecognized action');
}
