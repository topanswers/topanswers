<?
include 'db.php';
include 'nocache.php';
isset($_COOKIE['uuid']) || fail(403,'not logged in');
ccdb("select login($1)",$_COOKIE['uuid']);
$dev = (ccdb("select account_is_dev from my_account")==='t');
$community = $_GET['community'];
ccdb("select count(*) from community where community_name=$1",$community)==='1' or fail('invalid community');
isset($_GET['id']) || fail('missing id');
extract(cdb("select sesite_url from community left join sesite on sesite_id=community_sesite_id where community_name=$1",$community));
readfile($sesite_url.'/questions/'.$_GET['id']);
