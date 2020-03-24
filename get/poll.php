<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to poll,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']??'') || fail(403,'access denied');
exit(ccdb("select json_build_object('n',account_notification_id
                                   ,'c',coalesce(chat_max_id,0)
                                   ,'cc',coalesce(chat_max_change_id,0)
                                   ,'Q',coalesce(question_max_poll_major_id,0)
                                   ,'q',coalesce(question_max_poll_minor_id,0)
                                   ,'a',coalesce(chat_active_room_max_id,0)
                                   ) from one"));
