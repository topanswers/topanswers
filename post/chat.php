<?
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');

switch($_POST['action']){
  case 'new': exit(ccdb("select new_chat($1,$2,nullif($3,'')::integer,('{'||$4||'}')::integer[])",$_POST['room'],$_POST['msg'],$_POST['replyid']??'',isset($_POST['pings'])?implode(',',$_POST['pings']):''));
  case 'edit': exit(ccdb("select change_chat($1,$2)",$_POST['editid'],$_POST['msg']));
  case 'flag': exit(ccdb("select set_chat_flag($1)",$_POST['id']));
  case 'unflag': exit(ccdb("select remove_chat_flag($1)",$_POST['id']));
  case 'star': exit(ccdb("select set_chat_star($1)",$_POST['id']));
  case 'unstar': exit(ccdb("select remove_chat_star($1)",$_POST['id']));
  case 'dismiss': exit(ccdb("select dismiss_chat_notification($1)",$_POST['id']));
  default: fail(400,'unrecognized action');
}
