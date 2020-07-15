<?
include '../config.php';
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_GET['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to notification,pg_temp");
ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');

switch($_GET['action']) {
  case 'dismiss': exit(ccdb("select dismiss($1)",$_GET['id']));
  case 'dismiss-all': exit(ccdb("select dismiss_all()"));
  default: fail(400,'unrecognized action');
}
