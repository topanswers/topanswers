<?
include 'db.php';
include 'nocache.php';
if(!isset($_COOKIE['uuid'])) fail(403,'not logged in');
db("select login($1)",$_COOKIE['uuid']);
if(!isset($_GET['room'])) fail(400,'missing room parameter');
ccdb("select count(*) from room where room_id=$1",$_GET['room'])==='1' or die('invalid room');
$community_id = ccdb("select community_id from room natural join community where room_id=$1",$_GET['room']);
exit(ccdb("select json_build_object('c',coalesce((select max(chat_id) from chat where room_id=$1),0)
                                   ,'cc',coalesce((select max(chat_change_id) from chat where room_id=$1),0)
                                   ,'n',coalesce((select max(chat_notification_at)::text from chat_notification natural join chat),'')
                                   ,'q',coalesce((select max(question_id) from question where community_id=$2),0)
                                   ,'qc',coalesce((select max(question_poll_id) from question where community_id=$2),0)
                                   )",$_GET['room'],$community_id));
