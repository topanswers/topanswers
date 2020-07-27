<?
include '../config.php';
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
if(($_COOKIE['environment']??'prod')==='prod'){
  db("set search_path to error,pg_temp");
  ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied');
  db("select new($1,$2)",$_SERVER['HTTP_USER_AGENT']??'',file_get_contents('php://input'));
}
