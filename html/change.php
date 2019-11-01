<?    
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = $_COOKIE['uuid'] ?? false;
if($uuid) ccdb("select login($1)",$uuid);
$room = $_GET['room'] or die('Missing room_id');
echo ccdb("select (select json_build_object('question',1,'chat',coalesce(room_latest_change_id,0),'notification',coalesce((select max(chat_id) from chat_notification natural join chat),0)) from room where room_id=$1)",$room);
?>
