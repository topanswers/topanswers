<?
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
include '../nocache.php';
header("Location: /meta");
?>
